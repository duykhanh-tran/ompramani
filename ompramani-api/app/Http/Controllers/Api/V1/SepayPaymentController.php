<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\OrderStatusHistory;
use App\Services\Shop\SepayPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SepayPaymentController extends Controller
{
    public function __construct(
        protected SepayPaymentService $sepayService
    ) {}

    /**
     * Handle incoming Instant Payment Notification (IPN) webhook from SePay Payment Gateway.
     * Route: POST /api/v1/shop/sepay/ipn (Public, no Sanctum auth).
     * Auth: Secret Key via X-Secret-Key header.
     */
    public function handleIpn(Request $request): JsonResponse
    {
        // 1. Verify Secret Key Header (Fail-closed)
        $incomingSecret = $request->header('X-Secret-Key') ?? $request->header('x-secret-key');
        if (! $this->sepayService->verifyIpnSecretKey($incomingSecret)) {
            Log::warning('SePay IPN rejected: Invalid or missing X-Secret-Key header.', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Invalid or missing secret key.',
            ], 401);
        }

        // 2. Parse IPN Payload
        $payload = $request->json()->all();
        if (empty($payload)) {
            $payload = $request->all();
        }

        $notificationType = (string) ($payload['notification_type'] ?? '');
        Log::info("SePay IPN received: type={$notificationType}", [
            'ip' => $request->ip(),
            'timestamp' => $payload['timestamp'] ?? null,
        ]);

        // Acknowledge non-payment notification types safely
        if ($notificationType !== 'ORDER_PAID') {
            return response()->json([
                'success' => true,
                'message' => "Acknowledged notification type '{$notificationType}'.",
            ], 200);
        }

        $orderData = $payload['order'] ?? [];
        $transactionData = $payload['transaction'] ?? [];

        $invoiceNumber = (string) ($orderData['order_invoice_number'] ?? '');
        if (empty($invoiceNumber)) {
            Log::warning('SePay IPN error: Missing order_invoice_number in payload.', ['payload' => $payload]);

            return response()->json([
                'success' => false,
                'message' => 'Missing order_invoice_number in order data.',
            ], 422);
        }

        $rawAmount = $transactionData['transaction_amount'] ?? $orderData['order_amount'] ?? null;
        $currency = strtoupper((string) ($transactionData['transaction_currency'] ?? $orderData['order_currency'] ?? 'VND'));
        $transactionId = (string) ($transactionData['transaction_id'] ?? $transactionData['id'] ?? $orderData['order_id'] ?? '');
        $paymentMethodCode = (string) ($transactionData['payment_method'] ?? 'VIETQR');

        if ($rawAmount === null) {
            return response()->json([
                'success' => false,
                'message' => 'Missing transaction_amount in payload.',
            ], 422);
        }

        $paidAmountVnd = (int) round((float) $rawAmount);

        // 3. Process Payment & Order Updates Atomically
        return DB::transaction(function () use ($invoiceNumber, $paidAmountVnd, $currency, $transactionId, $paymentMethodCode, $payload, $request): JsonResponse {
            /** @var OrderPayment|null $payment */
            $payment = OrderPayment::query()
                ->where('transaction_ref', $invoiceNumber)
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                Log::warning("SePay IPN error: No OrderPayment found for invoice '{$invoiceNumber}'.");

                return response()->json([
                    'success' => false,
                    'message' => "Order payment record not found for invoice '{$invoiceNumber}'.",
                ], 404);
            }

            /** @var Order $order */
            $order = Order::query()
                ->where('id', $payment->order_id)
                ->lockForUpdate()
                ->firstOrFail();

            // 4. Idempotency Check: Already processed
            if ($payment->status === PaymentStatus::Paid) {
                Log::info("SePay IPN idempotent: Payment #{$payment->id} (Invoice: {$invoiceNumber}) is already Paid.");

                return response()->json([
                    'success' => true,
                    'message' => 'IPN already processed (idempotent).',
                ], 200);
            }

            // 5. Currency Check
            if ($currency !== 'VND') {
                $payment->status = PaymentStatus::NeedsReconciliation;
                $payment->raw_payload = $payload;
                $payment->save();

                Log::error("SePay IPN currency mismatch: Expected VND, got {$currency} for Invoice {$invoiceNumber}.");

                return response()->json([
                    'success' => false,
                    'message' => "Currency mismatch: expected VND, received {$currency}.",
                ], 422);
            }

            // 6. Amount Check
            if ($paidAmountVnd !== (int) $payment->amount_vnd) {
                $payment->status = PaymentStatus::NeedsReconciliation;
                $payment->raw_payload = $payload;
                $payment->save();

                $order->payment_status = PaymentStatus::NeedsReconciliation;
                $order->save();

                OrderStatusHistory::create([
                    'order_id' => $order->id,
                    'from_status' => $order->order_status,
                    'to_status' => $order->order_status,
                    'actor_type' => 'system',
                    'actor_id' => null,
                    'reason' => "Cảnh báo sai lệch số tiền SePay: Kê khai={$payment->amount_vnd} ₫, Thực thu={$paidAmountVnd} ₫ (Mã GD: {$transactionId})",
                    'notes' => 'Chuyển trạng thái sang Cần đối soát',
                    'created_at' => now(),
                ]);

                Log::error("SePay IPN amount mismatch for Invoice {$invoiceNumber}: expected {$payment->amount_vnd}, received {$paidAmountVnd}.");

                return response()->json([
                    'success' => false,
                    'message' => "Amount mismatch: expected {$payment->amount_vnd}, received {$paidAmountVnd}.",
                ], 422);
            }

            // 7. Check if Order was previously Cancelled
            if ($order->order_status === OrderStatus::Cancelled) {
                $payment->status = PaymentStatus::NeedsReconciliation;
                $payment->bank_transaction_no = $transactionId;
                $payment->bank_code = $paymentMethodCode;
                $payment->raw_payload = $payload;
                $payment->paid_at = now();
                $payment->save();

                OrderStatusHistory::create([
                    'order_id' => $order->id,
                    'from_status' => OrderStatus::Cancelled,
                    'to_status' => OrderStatus::Cancelled,
                    'actor_type' => 'system',
                    'actor_id' => null,
                    'reason' => "Giao dịch SePay thành công {$paidAmountVnd} ₫ nhưng đơn hàng đã bị hủy trước đó (Mã GD: {$transactionId})",
                    'notes' => 'Cần bộ phận kế toán liên hệ khách hàng để đối soát và hoàn tiền',
                    'created_at' => now(),
                ]);

                Log::warning("SePay IPN: Payment captured for cancelled order #{$order->order_number} (Invoice: {$invoiceNumber}).");

                return response()->json([
                    'success' => true,
                    'message' => 'Payment recorded for cancelled order (marked for reconciliation).',
                ], 200);
            }

            // 8. Normal Success Flow: Mark Payment and Order as Paid
            $payment->status = PaymentStatus::Paid;
            $payment->bank_transaction_no = $transactionId;
            $payment->bank_code = $paymentMethodCode;
            $payment->raw_payload = $payload;
            $payment->ip_address = $request->ip();
            $payment->paid_at = now();
            $payment->save();

            $fromStatus = $order->order_status;
            $order->payment_status = PaymentStatus::Paid;
            $order->paid_at = now();

            // Auto-advance Pending order to Confirmed upon successful payment
            if ($order->order_status === OrderStatus::Pending) {
                $order->order_status = OrderStatus::Confirmed;
            }

            $order->save();

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'from_status' => $fromStatus,
                'to_status' => $order->order_status,
                'actor_type' => 'system',
                'actor_id' => null,
                'reason' => "Thanh toán SePay thành công qua IPN (Mã GD: {$transactionId}, Cổng: {$paymentMethodCode})",
                'notes' => null,
                'created_at' => now(),
            ]);

            Log::info("SePay IPN success: Order #{$order->order_number} marked Paid. Paid at: {$order->paid_at}");

            return response()->json([
                'success' => true,
                'message' => 'IPN processed successfully.',
            ], 200);
        });
    }

    /**
     * Check verified payment status of an order for frontend polling.
     * Route: GET /api/v1/shop/orders/{publicId}/payment-status (Auth Sanctum).
     */
    public function getPaymentStatus(Request $request, string $publicId): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        /** @var Order $order */
        $order = Order::query()
            ->where('public_id', $publicId)
            ->where('user_id', $user->id)
            ->with(['payments'])
            ->firstOrFail();

        /** @var OrderPayment|null $latestPayment */
        $latestPayment = $order->payments()->orderBy('attempt_number', 'desc')->first();

        // Optional on-demand check with SePay Gateway if payment is still pending and has an invoice
        if ($latestPayment && $latestPayment->status === PaymentStatus::Pending && $order->payment_status !== PaymentStatus::Paid) {
            $gatewayOrder = $this->sepayService->retrieveOrderFromGateway((string) $latestPayment->transaction_ref);
            if ($gatewayOrder && isset($gatewayOrder['order_status']) && in_array(strtoupper((string) $gatewayOrder['order_status']), ['CAPTURED', 'PAID', 'APPROVED'], true)) {
                // Reconcile atomically
                DB::transaction(function () use ($order, $latestPayment, $gatewayOrder): void {
                    $lockedPayment = OrderPayment::where('id', $latestPayment->id)->lockForUpdate()->first();
                    $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->first();

                    if ($lockedPayment && $lockedPayment->status !== PaymentStatus::Paid) {
                        $lockedPayment->status = PaymentStatus::Paid;
                        $lockedPayment->paid_at = now();
                        $lockedPayment->bank_transaction_no = (string) ($gatewayOrder['id'] ?? $gatewayOrder['order_id'] ?? '');
                        $lockedPayment->save();

                        $lockedOrder->payment_status = PaymentStatus::Paid;
                        $lockedOrder->paid_at = now();
                        if ($lockedOrder->order_status === OrderStatus::Pending) {
                            $lockedOrder->order_status = OrderStatus::Confirmed;
                        }
                        $lockedOrder->save();

                        OrderStatusHistory::create([
                            'order_id' => $lockedOrder->id,
                            'from_status' => OrderStatus::Pending,
                            'to_status' => $lockedOrder->order_status,
                            'actor_type' => 'customer',
                            'actor_id' => $lockedOrder->user_id,
                            'reason' => 'Xác nhận thanh toán SePay thành công qua kiểm tra trực tiếp cổng thanh toán',
                            'created_at' => now(),
                        ]);
                    }
                });

                $order->refresh();
                $latestPayment->refresh();
            }
        }

        return response()->json([
            'data' => [
                'public_id' => $order->public_id,
                'order_number' => $order->order_number,
                'order_status' => $order->order_status->value,
                'order_status_label' => $order->order_status->label(),
                'payment_method' => $order->payment_method->value,
                'payment_method_label' => $order->payment_method->label(),
                'payment_status' => $order->payment_status->value,
                'payment_status_label' => $order->payment_status->label(),
                'total_vnd' => (int) $order->total_vnd,
                'formatted_total' => $order->formattedTotal(),
                'paid_at' => $order->paid_at?->toIso8601String(),
                'can_switch_to_cod' => $order->payment_status !== PaymentStatus::Paid && $order->order_status !== OrderStatus::Cancelled,
                'can_retry_payment' => $order->payment_status !== PaymentStatus::Paid && $order->order_status !== OrderStatus::Cancelled,
                'latest_payment' => $latestPayment ? [
                    'attempt_number' => $latestPayment->attempt_number,
                    'invoice_number' => $latestPayment->transaction_ref,
                    'status' => $latestPayment->status->value,
                    'status_label' => $latestPayment->status->label(),
                    'created_at' => $latestPayment->created_at?->toIso8601String(),
                    'paid_at' => $latestPayment->paid_at?->toIso8601String(),
                ] : null,
            ],
        ]);
    }

    /**
     * Customer retries an unfulfilled SePay payment by generating a new attempt with a fresh invoice.
     * Route: POST /api/v1/shop/orders/{publicId}/sepay-retry (Auth Sanctum).
     */
    public function retryPayment(Request $request, string $publicId): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        /** @var Order $order */
        $order = Order::query()
            ->where('public_id', $publicId)
            ->where('user_id', $user->id)
            ->with('payments')
            ->firstOrFail();

        if ($order->payment_status === PaymentStatus::Paid) {
            throw ValidationException::withMessages([
                'payment_status' => ['Đơn hàng này đã được thanh toán thành công.'],
            ]);
        }

        if ($order->order_status === OrderStatus::Cancelled) {
            throw ValidationException::withMessages([
                'order_status' => ['Không thể tạo phiên thanh toán mới cho đơn hàng đã hủy.'],
            ]);
        }

        $paymentData = DB::transaction(function () use ($order, $request): array {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->payment_status === PaymentStatus::Paid) {
                throw ValidationException::withMessages([
                    'payment_status' => ['Đơn hàng này đã được thanh toán.'],
                ]);
            }

            // Cancel any previous pending payment attempts
            $lockedOrder->payments()->where('status', PaymentStatus::Pending)->update([
                'status' => PaymentStatus::Cancelled,
                'failed_at' => now(),
            ]);

            $maxAttempt = (int) $lockedOrder->payments()->max('attempt_number');
            $nextAttempt = $maxAttempt + 1;
            $newInvoice = $this->sepayService->generateInvoiceNumber($lockedOrder, $nextAttempt);

            /** @var OrderPayment $newPayment */
            $newPayment = $lockedOrder->payments()->create([
                'attempt_number' => $nextAttempt,
                'payment_method' => PaymentMethod::Sepay,
                'provider' => 'sepay',
                'amount_vnd' => (int) $lockedOrder->total_vnd,
                'currency' => 'VND',
                'status' => PaymentStatus::Pending,
                'transaction_ref' => $newInvoice,
                'ip_address' => $request->ip(),
            ]);

            $formData = $this->sepayService->generateCheckoutForm($lockedOrder, $newPayment);

            OrderStatusHistory::create([
                'order_id' => $lockedOrder->id,
                'from_status' => $lockedOrder->order_status,
                'to_status' => $lockedOrder->order_status,
                'actor_type' => 'customer',
                'actor_id' => $lockedOrder->user_id,
                'reason' => "Khách hàng tạo lại phiên thanh toán SePay lần #{$nextAttempt} (Hóa đơn: {$newInvoice})",
                'created_at' => now(),
            ]);

            return $formData;
        });

        return response()->json([
            'message' => 'Đã tạo phiên thanh toán SePay mới.',
            'payment_data' => $paymentData,
        ]);
    }
}
