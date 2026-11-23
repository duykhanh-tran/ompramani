# SƠ ĐỒ TUẦN TỰ: TẠO ĐƠN HÀNG & KHÓA TRỪ TỒN KHO BI QUAN (M16.04, M16.05)

```mermaid
sequenceDiagram
    autonumber
    actor KhachHang as Khách hàng / Phật tử
    participant Cart as Giỏ hàng (CartContext.tsx)
    participant Checkout as Trang Thanh Toán (/thanh-toan)
    participant ClientAPI as shop.ts
    participant Ctrl as ShopOrderController
    participant OrderWf as OrderWorkflowService
    participant InvSvc as InventoryService
    participant DB as PostgreSQL (orders, order_items, products, inventory_movements)

    KhachHang->>Checkout: Điền họ tên, SĐT, địa chỉ nhận hàng, chọn phương thức (SePay/COD)
    Checkout->>ClientAPI: previewCheckout({ shipping_province, items })
    ClientAPI->>Ctrl: POST /api/v1/shop/checkout/preview
    Ctrl->>DB: Kiểm tra số lượng tồn kho & tính phí vận chuyển theo tỉnh/thành
    DB-->>Ctrl: Dữ liệu hợp lệ: subtotal, shipping_fee, total
    Ctrl-->>Checkout: HTTP 200 OK (CheckoutPreviewData)

    KhachHang->>Checkout: Bấm "Xác nhận đặt hàng"
    Checkout->>Checkout: Sinh Idempotency-Key ngẫu nhiên (chống bấm đúp chuột)
    Checkout->>ClientAPI: createOrder(CreateOrderPayload)
    ClientAPI->>Ctrl: POST /api/v1/shop/orders (Throttle 10 req/phút)
    
    Ctrl->>OrderWf: createOrderWithWorkflow($user, $data)
    OrderWf->>DB: DB::transaction() Bắt đầu giao dịch cô lập
    
    loop Với từng sản phẩm trong đơn hàng
        OrderWf->>DB: Product::where('id', $productId)->lockForUpdate()->first()
        alt Hết hàng hoặc số lượng trong kho < số lượng đặt
            DB-->>OrderWf: Tồn kho không đủ
            OrderWf-->>Ctrl: Ném ValidationException 422 ("Sản phẩm X không đủ số lượng")
            Ctrl-->>Checkout: HTTP 422
            Checkout-->>KhachHang: Báo lỗi hết hàng, yêu cầu cập nhật lại giỏ
        else Đủ hàng tồn kho
            alt inventory_mode == 'tracked'
                OrderWf->>DB: Giảm stock_quantity: decrement($quantity)
                OrderWf->>DB: InventoryMovement::create([type='order_placed', change=-quantity])
            end
        end
    end

    OrderWf->>DB: Order::create([order_number, public_id, total_vnd, order_status='pending', idempotency_key])
    OrderWf->>DB: OrderItem::insert(items)
    OrderWf->>DB: OrderStatusHistory::create([from=null, to='pending'])
    DB-->>OrderWf: Transaction Commit thành công
    
    OrderWf-->>Ctrl: Đối tượng Order hoàn chỉnh
    Ctrl-->>ClientAPI: HTTP 201 Created { order: OrderResource, payment_data: [...] }
    ClientAPI-->>Checkout: Trả về đơn hàng mới tạo
    Checkout->>Cart: clearCart() (Xóa giỏ hàng trong LocalStorage sau khi tạo đơn thành công)
```

---

### Thuyết minh nghiệp vụ

**Tên chức năng:** Đặt đơn hàng mới kèm khóa tồn kho bi quan và bảo vệ Idempotency.

**Mã chức năng:** M16.04, M16.05

**Mục đích:** Xử lý tạo đơn hàng pháp bảo an toàn tuyệt đối trong môi trường đồng thời cao; đảm bảo không bao giờ bị bán quá số lượng tồn kho (overselling) và không bị tạo trùng đơn hàng khi người dùng nhấn nút nhiều lần.

**Tác nhân:** Người dùng đã đăng nhập.

**Điều kiện tiên quyết:** Giỏ hàng có ít nhất 1 sản phẩm hợp lệ, người dùng đã đăng nhập.

**Luồng xử lý chính:**
1. Khách hàng nhập thông tin nhận hàng tại `/thanh-toan`.
2. Frontend gọi `POST /api/v1/shop/checkout/preview` để máy chủ thẩm định lại giá từng sản phẩm, kiểm tra còn hàng và tính toán cước phí vận chuyển chính xác theo tỉnh/thành (`ShippingFeePolicy`).
3. Frontend tạo một khóa bất biến `idempotency_key` (dạng `idem_{timestamp}_{random}`).
4. Gửi `POST /api/v1/shop/orders` có giới hạn tần suất 10 lần/phút.
5. `OrderWorkflowService` mở một `DB::transaction()` và áp dụng kỹ thuật khóa dòng bi quan `lockForUpdate()` trên từng bản ghi `Product`.
6. Kiểm tra số lượng tồn kho: Nếu chế độ tồn kho là `Tracked` (`inventory_mode == 'tracked'`), hệ thống trừ trực tiếp số lượng tồn và tạo bản ghi lịch sử biến động kho trong bảng `inventory_movements`.
7. Tạo bản ghi đơn hàng trong bảng `orders` (mã công khai `public_id` dạng ULID, mã đơn hàng `order_number` dạng `OPM-YYMMDD-XXXX`), lưu danh sách sản phẩm vào `order_items`.
8. Ghi log trạng thái ban đầu `pending` vào bảng `order_status_histories`.
9. Khi đơn hàng tạo thành công, giỏ hàng `CartContext` tự động được dọn sạch khỏi `localStorage`.

**Luồng ngoại lệ:**
- Nếu một sản phẩm trong giỏ bị hết hàng trong lúc đặt: Giao dịch Database tự động Rollback nguyên vẹn, trả về lỗi 422 thông báo rõ sản phẩm bị thiếu.

**Kết quả:** Đơn hàng được tạo thành công, tồn kho được trừ an toàn, sẵn sàng bước vào quy trình thanh toán.

**Các file source code liên quan:**
- Frontend: `src/app/thanh-toan/CheckoutClient.tsx`, `src/context/CartContext.tsx`, `src/lib/api/shop.ts`.
- Backend: `app/Http/Controllers/Api/V1/ShopOrderController.php` (hàm `store`), `app/Services/Shop/OrderWorkflowService.php`, `app/Services/Shop/InventoryService.php`, `app/Models/Order.php`, `app/Models/Product.php`.
