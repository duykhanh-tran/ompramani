# SƠ ĐỒ TUẦN TỰ: KIỂM SOÁT QUYỀN & QUẢN TRỊ TRÊN FILAMENT ADMIN (M17.01)

```mermaid
sequenceDiagram
    autonumber
    actor Admin as Quản trị viên
    participant Login as Màn hình Login Admin (/admin/login)
    participant Filament as Filament Panel Engine
    participant UserMod as User Model (canAccessPanel)
    participant Page as Trang Quản Lý Kho (/admin/manage-inventory)
    participant InvSvc as InventoryService
    participant DB as PostgreSQL (products, inventory_movements)

    Admin->>Login: Nhập email, mật khẩu quản trị & Bấm đăng nhập
    Login->>Filament: POST /admin/login
    Filament->>UserMod: Auth::attempt() & kiểm tra canAccessPanel($panel)
    
    alt User có role == 'user' (Người dùng thường cố tình đăng nhập admin)
        UserMod-->>Filament: Trả về false ($this->role === 'admin')
        Filament-->>Login: HTTP 403 Forbidden (Từ chối truy cập bảng quản trị)
        Login-->>Admin: Báo lỗi "Bạn không có quyền truy cập trang này"
    else User có role == 'admin'
        UserMod-->>Filament: Trả về true
        Filament-->>Admin: Chuyển hướng vào Bảng điều khiển quản trị (/admin)
    end

    Admin->>Filament: Chọn chức năng "Điều chỉnh Tồn kho thủ công" (Manage Inventory)
    Filament->>Page: Mở ManageInventory.php
    Admin->>Page: Chọn sản phẩm (ID: 5), chọn loại điều chỉnh (+10 Thêm hàng), nhập lý do
    Page->>InvSvc: adjustStock($productId, $quantityChange, $reason, $adminUser)
    InvSvc->>DB: DB::transaction() + Product::lockForUpdate()
    InvSvc->>DB: increment('stock_quantity', 10)
    InvSvc->>DB: InventoryMovement::create([product_id, type='manual_adjustment', change=+10, actor_type='admin', actor_id=$adminId])
    DB-->>InvSvc: Ghi nhận biến động kho thành công
    InvSvc-->>Page: Số lượng tồn mới cập nhật
    Page-->>Admin: Hiển thị thông báo Filament Notification "Đã điều chỉnh kho thành công"
```

---

### Thuyết minh nghiệp vụ

**Tên chức năng:** Kiểm soát quyền truy cập Admin Panel và điều chỉnh tồn kho vật phẩm thủ công.

**Mã chức năng:** M17.01, M16.12

**Mục đích:** Bảo vệ hệ thống quản trị chỉ dành riêng cho các tài khoản được ủy thác đặc quyền (`role = 'admin'`), hỗ trợ thủ kho cập nhật số lượng nhập kho mới hoặc kiểm kê định kỳ với đầy đủ vết kiểm toán.

**Tác nhân:** Quản trị viên hệ thống (Admin).

**Điều kiện tiên quyết:** Tài khoản trong bảng `users` có cột `role = 'admin'`.

**Luồng xử lý chính:**
1. Quản trị viên truy cập `/admin/login`.
2. Filament kiểm tra hợp lệ thông tin qua phương thức `canAccessPanel(Panel $panel): bool` cài đặt trên `User` model:
   ```php
   public function canAccessPanel(Panel $panel): bool {
       return $this->role === 'admin';
   }
   ```
3. Nếu tài khoản chỉ là người dùng thông thường (`role === 'user'`), Filament lập tức từ chối với mã lỗi 403 Forbidden.
4. Khi đã đăng nhập, quản trị viên có thể truy cập các trang chuyên biệt như `/admin/manage-inventory`.
5. Tại đây, khi điều chỉnh tồn kho (nhập hàng mới, hao hụt, mất mát...), `InventoryService` chạy giao dịch database khóa bi quan dòng sản phẩm, cập nhật `stock_quantity` và lưu bản ghi lịch sử `inventory_movements` ghi rõ ID quản trị viên thực hiện.

**Luồng ngoại lệ:**
- Người dùng chưa đăng nhập cố truy cập `/admin`: Tự động bị chuyển hướng về màn hình đăng nhập `/admin/login`.

**Kết quả:** Kho hàng được cập nhật chính xác, mọi hành động quản trị đều được lưu vết chi tiết.

**Các file source code liên quan:**
- Backend: `app/Models/User.php` (hàm `canAccessPanel`), `app/Filament/Pages/ManageInventory.php`, `app/Services/Shop/InventoryService.php`, `app/Models/InventoryMovement.php`.
