<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\InventoryMode;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OrderDetailResource;
use App\Http\Resources\Api\V1\OrderSummaryResource;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Services\Shop\OrderWorkflowService;
use App\Services\Shop\SepayPaymentService;
use App\Services\Shop\ShippingFeePolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ShopOrderController extends Controller
{
    public function __construct(
        protected SepayPaymentService $sepayService
    ) {}

    /**
     * Preview checkout with live prices, available inventory, and shipping fee policy.
     * Strictly read-only: does not create orders or modify inventory.
     */
    public function checkoutPreview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $requestedItems = collect($validated['items']);
        $productIds = $requestedItems->pluck('product_id')->unique()->all();

        $numericIds = array_filter($productIds, fn ($id) => is_numeric($id));
        $slugs = array_filter($productIds, fn ($id) => ! is_numeric($id));

        $products = Product::query()
            ->where('is_active', true)
            ->where(function ($query) use ($numericIds, $slugs) {
                if (! empty($numericIds)) {
                    $query->orWhereIn('id', $numericIds);
                }
                if (! empty($slugs)) {
                    $query->orWhereIn('public_id', $slugs)
                        ->orWhereIn('slug', $slugs);
                }
            })
            ->get();

        $previewItems = [];
        $subtotalVnd = 0;
        $totalQuantity = 0;
        $allValid = true;

        foreach ($requestedItems as $requested) {
            $identifier = (string) $requested['product_id'];
            $qty = (int) $requested['quantity'];

            /** @var Product|null $product */
            $product = is_numeric($identifier)
                ? $products->firstWhere('id', (int) $identifier)
                : ($products->firstWhere('public_id', $identifier) ?? $products->firstWhere('slug', $identifier));

            if (! $product) {
                $previewItems[] = [
                    'product_id' => $identifier,
                    'product_name' => 'Sản phẩm không tồn tại hoặc đã ngừng kinh doanh',
                    'product_slug' => '',
                    'product_image' => null,
                    'price_vnd' => 0,
                    'formatted_price' => '0 ₫',
                    'quantity' => $qty,
                    'line_total_vnd' => 0,
                    'formatted_line_total' => '0 ₫',
                    'is_available' => false,
                    'inventory_mode' => 'tracked',
                    'available_stock' => 0,
                    'error_message' => 'Vật phẩm không còn khả dụng.',
                ];
                $allValid = false;

                continue;
            }

            $isAvailable = true;
            $errorMsg = null;

            if ($product->inventory_mode === InventoryMode::Tracked) {
                if ($product->stock_quantity <= 0) {
                    $isAvailable = false;
                    $errorMsg = 'Vật phẩm hiện đã hết hàng.';
                    $allValid = false;
                } elseif ($qty > $product->stock_quantity) {
                    $isAvailable = false;
                    $errorMsg = "Kho chỉ còn {$product->stock_quantity} vật phẩm (yêu cầu {$qty}).";
                    $allValid = false;
                }
            }

            $unitPrice = (int) $product->price_vnd;
            $lineTotal = $unitPrice * $qty;

            if ($isAvailable) {
                $subtotalVnd += $lineTotal;
                $totalQuantity += $qty;
            }

            $imageUrl = null;
            if (! empty($product->primary_image)) {
                $publicDisk = (string) config('filesystems.public_disk', 'public');
                $imageUrl = str_starts_with($product->primary_image, 'http')
                    ? $product->primary_image
                    : Storage::disk($publicDisk)->url($product->primary_image);
            }

            $previewItems[] = [
                'product_id' => (string) $product->id,
                'product_name' => $product->name,
                'product_slug' => $product->slug,
                'product_image' => $imageUrl,
                'price_vnd' => $unitPrice,
                'formatted_price' => number_format($unitPrice, 0, ',', '.').' ₫',
                'quantity' => $qty,
                'line_total_vnd' => $lineTotal,
                'formatted_line_total' => number_format($lineTotal, 0, ',', '.').' ₫',
                'is_available' => $isAvailable,
                'inventory_mode' => $product->inventory_mode->value,
                'available_stock' => (int) $product->stock_quantity,
                'error_message' => $errorMsg,
            ];
        }

        $shippingFeeVnd = ShippingFeePolicy::calculateFee($subtotalVnd);
        $totalVnd = $subtotalVnd + $shippingFeeVnd;

        return response()->json([
            'data' => [
                'items' => $previewItems,
                'subtotal_vnd' => $subtotalVnd,
                'formatted_subtotal' => number_format($subtotalVnd, 0, ',', '.').' ₫',
                'shipping_fee_vnd' => $shippingFeeVnd,
                'formatted_shipping_fee' => $shippingFeeVnd === 0 ? 'Miễn phí' : number_format($shippingFeeVnd, 0, ',', '.').' ₫',
                'total_vnd' => $totalVnd,
                'formatted_total' => number_format($totalVnd, 0, ',', '.').' ₫',
                'total_quantity' => $totalQuantity,
                'is_valid' => $allValid && count($previewItems) > 0,
                'available_payment_methods' => [
                    [
                        'code' => PaymentMethod::Cod->value,
                        'name' => PaymentMethod::Cod->label(),
                        'description' => 'Quý đạo hữu nhận hàng và kiểm tra trước khi gửi tiền cho nhân viên giao hàng.',
                    ],
                    [
                        'code' => PaymentMethod::Sepay->value,
                        'name' => PaymentMethod::Sepay->label(),
                        'description' => 'Quét mã VietQR chuyển khoản nhanh 24/7 qua cổng thanh toán bảo mật SePay.',
                    ],
                ],
            ],
        ]);
    }

    /**
     * Create a verified Order in database within an atomic transaction.
     * Accepts COD or SePay VietQR payment methods.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(401, 'Yêu cầu đăng nhập để tiến hành đặt hàng.');
        }

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'regex:/^[0-9+() -]{8,20}$/'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'shipping_address' => ['required', 'string', 'max:500'],
            'shipping_province' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['required', 'string', 'in:cod,sepay'],
            'idempotency_key' => ['nullable', 'string', 'max:64'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $idempotencyKey = $validated['idempotency_key'] ?? null;

        // Idempotency check: if order with this key already exists for user, return existing order
        if (! empty($idempotencyKey)) {
            $existing = Order::where('user_id', $user->id)
                ->where('idempotency_key', $idempotencyKey)
                ->with(['items', 'payments'])
                ->first();

            if ($existing) {
                $existingPaymentData = null;
                if ($existing->payment_method === PaymentMethod::Sepay && $existing->payment_status !== PaymentStatus::Paid) {
                    $pendingPayment = $existing->payments()->where('status', PaymentStatus::Pending)->latest('attempt_number')->first();
                    if ($pendingPayment) {
                        try {
                            $existingPaymentData = $this->sepayService->generateCheckoutForm($existing, $pendingPayment);
                        } catch (\Throwable $e) {
                            // Suppress exception on idempotent retry
                        }
                    }
                }

                return response()->json([
                    'data' => new OrderDetailResource($existing),
                    'payment_data' => $existingPaymentData,
                    'message' => 'Đơn hàng đã được tạo trước đó (Idempotent).',
                ], 200);
            }
        }

        $chosenMethod = PaymentMethod::from($validated['payment_method']);

        /** @var Order $order */
        $order = DB::transaction(function () use ($validated, $user, $idempotencyKey, $chosenMethod, $request) {
            $requestedItems = collect($validated['items']);
            $productIds = $requestedItems->pluck('product_id')->unique()->all();

            $numericIds = array_filter($productIds, fn ($id) => is_numeric($id));
            $slugs = array_filter($productIds, fn ($id) => ! is_numeric($id));

            // Select with lock for update to prevent race conditions on stock
            $products = Product::query()
                ->where('is_active', true)
                ->where(function ($query) use ($numericIds, $slugs) {
                    if (! empty($numericIds)) {
                        $query->orWhereIn('id', $numericIds);
                    }
                    if (! empty($slugs)) {
                        $query->orWhereIn('public_id', $slugs)
                            ->orWhereIn('slug', $slugs);
                    }
                })
                ->lockForUpdate()
                ->get();

            $subtotalVnd = 0;
            $orderItemsData = [];

            foreach ($requestedItems as $requested) {
                $identifier = (string) $requested['product_id'];
                $qty = (int) $requested['quantity'];

                /** @var Product|null $product */
                $product = is_numeric($identifier)
                    ? $products->firstWhere('id', (int) $identifier)
                    : ($products->firstWhere('public_id', $identifier) ?? $products->firstWhere('slug', $identifier));

                if (! $product) {
                    throw ValidationException::withMessages([
                        'items' => ["Vật phẩm ID/Slug '{$identifier}' không tồn tại hoặc đã ngừng kinh doanh."],
                    ]);
                }

                // Verify stock availability
                if ($product->inventory_mode === InventoryMode::Tracked) {
                    if ($product->stock_quantity < $qty) {
                        throw ValidationException::withMessages([
                            'items' => ["Vật phẩm '{$product->name}' không đủ số lượng trong kho (chỉ còn {$product->stock_quantity}, bạn yêu cầu {$qty})."],
                        ]);
                    }

                    // Decrement stock atomically
                    $product->decrement('stock_quantity', $qty);
                }

                $unitPrice = (int) $product->price_vnd;
                $lineTotal = $unitPrice * $qty;
                $subtotalVnd += $lineTotal;

                $orderItemsData[] = [
                    'product_id' => $product->id,
                    'product_public_id' => (string) ($product->public_id ?? Str::ulid()),
                    'product_name_snapshot' => $product->name,
                    'product_sku_snapshot' => $product->sku,
                    'product_image_snapshot' => $product->primary_image,
                    'unit_price_vnd' => $unitPrice,
                    'quantity' => $qty,
                    'line_total_vnd' => $lineTotal,
                ];
            }

            $shippingFeeVnd = ShippingFeePolicy::calculateFee($subtotalVnd);
            $totalVnd = $subtotalVnd + $shippingFeeVnd;

            $newOrder = Order::create([
                'user_id' => $user->id,
                'customer_name' => trim($validated['customer_name']),
                'customer_phone' => trim($validated['customer_phone']),
                'customer_email' => ! empty($validated['customer_email']) ? trim($validated['customer_email']) : $user->email,
                'shipping_address' => trim($validated['shipping_address']),
                'shipping_province' => trim($validated['shipping_province']),
                'shipping_fee_vnd' => $shippingFeeVnd,
                'subtotal_vnd' => $subtotalVnd,
                'total_vnd' => $totalVnd,
                'currency' => 'VND',
                'order_status' => OrderStatus::Pending,
                'payment_method' => $chosenMethod,
                'payment_status' => PaymentStatus::Unpaid,
                'notes' => ! empty($validated['notes']) ? strip_tags(trim($validated['notes'])) : null,
                'idempotency_key' => $idempotencyKey,
                'placed_at' => now(),
            ]);

            foreach ($orderItemsData as $itemData) {
                $newOrder->items()->create($itemData);

                if (! empty($itemData['product_id'])) {
                    /** @var Product|null $prod */
                    $prod = $products->firstWhere('id', $itemData['product_id']);
                    if ($prod && $prod->inventory_mode === InventoryMode::Tracked) {
                        $after = (int) $prod->stock_quantity;
                        $before = $after + (int) $itemData['quantity'];
                        InventoryMovement::create([
                            'product_id' => $prod->id,
                            'type' => 'order_placed',
                            'quantity_before' => $before,
                            'quantity_after' => $after,
                            'change' => - (int) $itemData['quantity'],
                            'reason' => "Đơn hàng mới #{$newOrder->order_number}",
                            'actor_type' => 'customer',
                            'actor_id' => $user->id,
                            'created_at' => now(),
                        ]);
                    }
                }
            }

            // Create initial payment attempt if SePay
            if ($chosenMethod === PaymentMethod::Sepay) {
                $invoiceNumber = $this->sepayService->generateInvoiceNumber($newOrder, 1);
                $newOrder->payments()->create([
                    'attempt_number' => 1,
                    'payment_method' => PaymentMethod::Sepay,
                    'provider' => 'sepay',
                    'amount_vnd' => $totalVnd,
                    'currency' => 'VND',
                    'status' => PaymentStatus::Pending,
                    'transaction_ref' => $invoiceNumber,
                    'ip_address' => $request->ip(),
                ]);
            }

            // Record initial history event
            OrderStatusHistory::create([
                'order_id' => $newOrder->id,
                'from_status' => null,
                'to_status' => OrderStatus::Pending,
                'actor_type' => 'customer',
                'actor_id' => $user->id,
                'reason' => 'Khởi tạo đơn hàng',
                'notes' => null,
                'created_at' => now(),
            ]);

            return $newOrder;
        });

        $paymentData = null;
        if ($order->payment_method === PaymentMethod::Sepay) {
            /** @var OrderPayment $payment */
            $payment = $order->payments()->where('attempt_number', 1)->firstOrFail();
            $paymentData = $this->sepayService->generateCheckoutForm($order, $payment);
        }

        $responseData = (new OrderDetailResource($order->load(['items', 'statusHistories', 'payments', 'latestPayment'])))->resolve();

        return response()->json([
            'data' => $responseData,
            'payment_data' => $paymentData,
            'message' => 'Đặt hàng thành công.',
        ], 201);
    }

    /**
     * Authenticated customer switches an unpaid order to Cash on Delivery (COD).
     */
    public function switchToCod(Request $request, string $publicId): JsonResponse
    {
        $user = $request->user();

        /** @var Order $order */
        $order = Order::where('public_id', $publicId)
            ->where('user_id', $user->id)
            ->with('payments')
            ->firstOrFail();

        if ($order->payment_status === PaymentStatus::Paid) {
            throw ValidationException::withMessages([
                'payment_status' => ['Đơn hàng này đã được thanh toán, không thể chuyển sang COD.'],
            ]);
        }

        if ($order->order_status === OrderStatus::Cancelled) {
            throw ValidationException::withMessages([
                'order_status' => ['Không thể chuyển đổi phương thức thanh toán cho đơn hàng đã hủy.'],
            ]);
        }

        DB::transaction(function () use ($order): void {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->payment_status === PaymentStatus::Paid) {
                throw ValidationException::withMessages([
                    'payment_status' => ['Đơn hàng này đã được thanh toán, không thể chuyển sang COD.'],
                ]);
            }

            // Cancel any pending payment attempts
            $lockedOrder->payments()->where('status', PaymentStatus::Pending)->update([
                'status' => PaymentStatus::Cancelled,
                'failed_at' => now(),
            ]);

            $lockedOrder->payment_method = PaymentMethod::Cod;
            $lockedOrder->payment_status = PaymentStatus::Unpaid;
            $lockedOrder->save();

            OrderStatusHistory::create([
                'order_id' => $lockedOrder->id,
                'from_status' => $lockedOrder->order_status,
                'to_status' => $lockedOrder->order_status,
                'actor_type' => 'customer',
                'actor_id' => $lockedOrder->user_id,
                'reason' => 'Khách hàng chuyển hình thức thanh toán sang COD',
                'notes' => 'Hủy các phiên thanh toán trực tuyến chưa hoàn tất',
                'created_at' => now(),
            ]);
        });

        $orderResource = new OrderDetailResource($order->fresh(['items', 'statusHistories', 'payments']));

        return response()->json([
            'message' => 'Đã chuyển hình thức thanh toán sang COD thành công.',
            'order' => $orderResource,
            'data' => $orderResource,
        ]);
    }

    /**
     * List authenticated customer's orders with pagination and optional status filter.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $perPage = (int) $request->input('per_page', 10);
        if ($perPage < 1 || $perPage > 50) {
            $perPage = 10;
        }

        $statusFilter = $request->input('status');

        $query = Order::query()
            ->where('user_id', $user->id)
            ->with(['items'])
            ->orderBy('placed_at', 'desc')
            ->orderBy('id', 'desc');

        if ($statusFilter && in_array($statusFilter, ['pending', 'confirmed', 'processing', 'completed', 'cancelled'], true)) {
            $query->where('order_status', $statusFilter);
        }

        $orders = $query->paginate($perPage);

        return response()->json([
            'data' => OrderSummaryResource::collection($orders),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    /**
     * Customer direct cancellation for eligible Pending COD orders.
     */
    public function cancel(Request $request, string $publicId, OrderWorkflowService $workflowService): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        /** @var Order|null $order */
        $order = Order::query()
            ->where('public_id', $publicId)
            ->where('user_id', $user->id)
            ->with(['items'])
            ->first();

        if (! $order) {
            return response()->json([
                'message' => 'Đơn hàng không tồn tại hoặc quý đạo hữu không có quyền thao tác trên đơn hàng này.',
            ], 404);
        }

        if (! $order->canBeCancelledByCustomer()) {
            return response()->json([
                'message' => 'Đơn hàng không còn ở trạng thái cho phép hủy trực tiếp (chỉ cho phép hủy đơn COD khi đang Chờ xác nhận).',
            ], 422);
        }

        $reason = ! empty($validated['reason']) ? strip_tags(trim($validated['reason'])) : 'Khách hàng yêu cầu hủy đơn';

        $cancelledOrder = $workflowService->cancel(
            $order,
            $user,
            'customer',
            $reason,
            'Hủy bởi khách hàng từ trang quản lý đơn hàng'
        );

        return response()->json([
            'message' => 'Hủy đơn hàng thành công.',
            'data' => new OrderDetailResource($cancelledOrder->fresh(['items', 'statusHistories'])),
        ]);
    }

    /**
     * Retrieve order confirmation details by public_id for authenticated owner.
     */
    public function show(Request $request, string $publicId): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $order = Order::query()
            ->where('public_id', $publicId)
            ->where('user_id', $user->id)
            ->with(['items', 'statusHistories', 'payments', 'latestPayment'])
            ->first();

        if (! $order) {
            return response()->json([
                'message' => 'Đơn hàng không tồn tại hoặc quý đạo hữu không có quyền xem đơn hàng này.',
            ], 404);
        }

        return response()->json([
            'data' => new OrderDetailResource($order),
        ]);
    }
}
