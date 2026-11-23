# SƠ ĐỒ TUẦN TỰ: THANH TOÁN SEPAY VIETQR & XỬ LÝ WEBHOOK IPN (M16.06, M16.07, M16.08)

```mermaid
sequenceDiagram
    autonumber
    actor KhachHang as Khách hàng
    participant Checkout as CheckoutClient.tsx
    participant ResultPage as PaymentResultClient.tsx
    participant SepaySvc as SepayPaymentService
    participant SepayGW as Cổng Thanh Toán SePay
    participant IPNCtrl as SepayPaymentController (handleIpn)
    participant OrderWf as OrderWorkflowService
    participant DB as PostgreSQL (orders, order_payments, order_status_histories)

    Note over Checkout, SepayGW: 1. Khởi tạo & Chuyển hướng thanh toán SePay
    Checkout->>SepaySvc: generateCheckoutForm($order, $payment)
    SepaySvc->>SepaySvc: Ký chữ ký số hóa đơn OPM-XXXX-A1 qua SePayClient SDK
    SepaySvc-->>Checkout: Trả về checkout_url & form fields có chữ ký
    Checkout->>Checkout: Tạo HTML form ẩn & Tự động Submit POST sang SePay Gateway
    Checkout->>SepayGW: Chuyển hướng trình duyệt sang SePay Checkout
    SepayGW-->>KhachHang: Hiển thị giao diện quét mã QR chuyển khoản VietQR

    KhachHang->>KhachHang: Dùng app Ngân hàng quét mã VietQR và xác nhận chuyển tiền
    SepayGW->>SepayGW: Ngân hàng khớp tiền vào tài khoản Merchant SePay

    Note over SepayGW, DB: 2. Webhook IPN xử lý bất đồng bộ từ SePay Server
    SepayGW->>IPNCtrl: POST /api/v1/shop/sepay/ipn (Header: X-Secret-Key)
    IPNCtrl->>SepaySvc: verifyIpnSecretKey(incomingKey)
    
    alt X-Secret-Key sai hoặc server chưa cấu hình secret
        SepaySvc-->>IPNCtrl: Xác thực thất bại (Fail-closed)
        IPNCtrl-->>SepayGW: HTTP 401 Unauthorized (Từ chối xử lý)
    else Chữ ký hợp lệ
        IPNCtrl->>DB: DB::transaction() + Khóa đơn hàng lockForUpdate()
        IPNCtrl->>DB: Tìm OrderPayment theo invoice_number
        IPNCtrl->>IPNCtrl: Đối soát số tiền nhận được == order->total_vnd
        
        alt Số tiền không khớp (Nghi vấn gian lận)
            IPNCtrl->>DB: Đánh dấu OrderPayment status = 'failed' / needs_reconciliation
        else Số tiền chính xác 100%
            IPNCtrl->>DB: Cập nhật OrderPayment status = 'paid', transaction_ref, paid_at
            IPNCtrl->>OrderWf: transition($order, OrderStatus::Processing, ...)
            OrderWf->>DB: Cập nhật Order: payment_status = 'paid', paid_at = now()
            OrderWf->>DB: Ghi log OrderStatusHistory (from: pending -> to: processing)
            DB-->>IPNCtrl: Giao dịch thành công
            IPNCtrl-->>SepayGW: HTTP 200 OK { success: true }
        end
    end

    Note over KhachHang, DB: 3. Phía Client kiểm tra và đón nhận kết quả
    SepayGW-->>KhachHang: Chuyển hướng người dùng về /don-hang/[publicId]/ket-qua
    KhachHang->>ResultPage: Mở trang kết quả thanh toán
    loop Polling chu kỳ 3 giây (Tối đa 10 lần)
        ResultPage->>IPNCtrl: GET /api/v1/shop/orders/{publicId}/payment-status
        IPNCtrl->>DB: Đọc trạng thái payment_status của đơn hàng
        DB-->>IPNCtrl: payment_status = 'paid'
        IPNCtrl-->>ResultPage: HTTP 200 OK { payment_status: 'paid' }
    end
    ResultPage->>ResultPage: Kích hoạt hiệu ứng pháo hoa FireworksCelebration!
    ResultPage-->>KhachHang: Hiển thị thông báo "Thanh toán thành công! Đơn hàng đang được chuẩn bị"
```

---

### Thuyết minh nghiệp vụ

**Tên chức năng:** Khởi tạo thanh toán VietQR qua SePay, tiếp nhận Webhook IPN và Polling trạng thái.

**Mã chức năng:** M16.06, M16.07, M16.08

**Mục đích:** Tự động hóa 100% quy trình thu tiền chuyển khoản ngân hàng qua mã VietQR động, đối chiếu tiền về tức thời và kích hoạt quy trình đóng gói đơn hàng.

**Tác nhân:** Khách hàng (Thanh toán), Hệ thống SePay (Bắn Webhook) và Máy chủ OmPraMani.

**Điều kiện tiên quyết:** Đơn hàng đã được tạo thành công với phương thức thanh toán `sepay`.

**Luồng xử lý chính:**
1. Khi đơn hàng được tạo, `SepayPaymentService::generateCheckoutForm` sử dụng SDK chính thức của SePay để ký số các trường thanh toán với mã hóa đơn duy nhất dạng `OPM-{order_number}-A{attempt_number}`.
2. Trình duyệt tự động tạo và submit một form POST ẩn điều hướng khách hàng sang cổng thanh toán SePay để quét mã VietQR.
3. Khi khách hàng chuyển khoản thành công từ app ngân hàng, máy chủ SePay gửi một gói tin Instant Payment Notification (IPN) đến endpoint `POST /api/v1/shop/sepay/ipn`.
4. `SepayPaymentController::handleIpn` thực thi nguyên tắc an ninh **Fail-closed**:
   - Bắt buộc kiểm tra header `X-Secret-Key` khớp với khóa bí mật `SEPAY_IPN_SECRET_KEY` đã cấu hình trên server. Nếu thiếu hoặc sai, từ chối ngay với mã lỗi 401.
5. Mở `DB::transaction()` và khóa bi quan bản ghi đơn hàng.
6. So sánh số tiền thực nhận trong IPN (`transaction_amount`) với tổng giá trị đơn hàng (`order->total_vnd`). Nếu khớp hoàn toàn:
   - Cập nhật bản ghi `order_payments` thành `paid`.
   - Cập nhật trạng thái đơn hàng sang `Processing` (Đang chuẩn bị hàng) và `payment_status = 'paid'`, lưu vết vào `order_status_histories`.
7. Trả về mã HTTP 200 xác nhận cho SePay không gửi lại webhook nữa.
8. Trình duyệt khách hàng sau khi chuyển tiền được chuyển hướng về `/don-hang/[publicId]/ket-qua` và thực hiện cơ chế Polling (chu kỳ 3s/lần, tối đa 10 lần) để đón nhận trạng thái `paid`.
9. Khi trạng thái chuyển sang đã thanh toán, giao diện bắn hiệu ứng pháo hoa ăn mừng và hiển thị hóa đơn thanh toán.

**Luồng ngoại lệ:**
- Nếu số tiền thanh toán bị thiếu hoặc sai lệch: Trạng thái đơn được chuyển sang `needs_reconciliation` (cần đối soát thủ công) để nhân viên quản trị xử lý trong Admin.

**Kết quả:** Đơn hàng được thanh toán tự động, không cần chụp ảnh màn hình chuyển khoản thủ công.

**Các file source code liên quan:**
- Frontend: `src/app/thanh-toan/CheckoutClient.tsx`, `src/app/don-hang/[publicId]/ket-qua/PaymentResultClient.tsx`, `src/lib/api/shop.ts`.
- Backend: `app/Http/Controllers/Api/V1/SepayPaymentController.php` (các hàm `handleIpn`, `getPaymentStatus`), `app/Services/Shop/SepayPaymentService.php`, `app/Services/Shop/OrderWorkflowService.php`, `app/Models/OrderPayment.php`.
