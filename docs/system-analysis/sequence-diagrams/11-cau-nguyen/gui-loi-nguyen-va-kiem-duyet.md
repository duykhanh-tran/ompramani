# SƠ ĐỒ TUẦN TỰ: GỬI LỜI CẦU NGUYỆN & QUY TRÌNH KIỂM DUYỆT (M11.02, M11.04, M11.08)

```mermaid
sequenceDiagram
    autonumber
    actor NguoiDung as Người dùng (Gửi nguyện)
    participant FE as Màn hình Gửi Nguyện (/cau-nguyen/gui-loi-nguyen)
    participant ClientAPI as prayers.ts
    participant Ctrl as MyPrayerIntentionController
    participant Svc as PrayerIntentionService
    participant DB as PostgreSQL (prayer_intentions, prayer_intention_audits)
    actor Admin as Quản trị viên (Kiểm duyệt)
    participant Filament as Filament Admin Panel (/admin/prayer-intentions)
    participant ModSvc as PrayerModerationService
    participant NotifSvc as PrayerNotificationService

    NguoiDung->>FE: Soạn tiêu đề, nội dung lời nguyện, chọn phân loại & Chế độ công khai
    FE->>ClientAPI: createPrayerIntention(payload)
    ClientAPI->>Ctrl: POST /api/v1/me/prayer-intentions (Rate limit 15 req/phút)
    Ctrl->>Svc: createIntention($user, $data)
    Svc->>DB: DB::transaction -> Ghi bản ghi (moderation_status = 'pending')
    Svc->>DB: Ghi log lịch sử kiểm duyệt vào prayer_intention_audits
    DB-->>Ctrl: Bản ghi lời nguyện mới tạo
    Ctrl-->>FE: HTTP 201 Created { data: MyPrayerIntentionResource }
    FE-->>NguoiDung: Thông báo "Lời nguyện đã được gửi và đang chờ ban biên tập phê duyệt"

    Note over Admin, ModSvc: Quá trình kiểm duyệt an toàn trong Admin Panel
    Admin->>Filament: Mở danh sách lời nguyện đang chờ duyệt (Pending)
    Filament->>DB: Lấy danh sách prayer_intentions status='pending'
    Admin->>Filament: Xem nội dung, kiểm tra ngôn từ phản cảm, mê tín
    
    alt Nội dung vi phạm chính sách
        Admin->>Filament: Bấm "Từ chối" (Reject) kèm lý do (RejectionReason)
        Filament->>ModSvc: reject($intention, $reason, $notes, $adminUser)
        ModSvc->>DB: Cập nhật moderation_status = 'rejected', rejection_notes
        ModSvc->>NotifSvc: Gửi thông báo đến tác giả về lý do từ chối
    else Nội dung thanh tịnh, hợp chuẩn
        Admin->>Filament: Bấm "Phê duyệt" (Approve)
        Filament->>ModSvc: approve($intention, $adminUser)
        ModSvc->>DB: Cập nhật moderation_status = 'approved', published_at = now()
        ModSvc->>DB: Ghi log audit duyệt thành công
        ModSvc->>NotifSvc: Gửi thông báo chúc mừng lời nguyện đã xuất bản
        Filament-->>Admin: Lời nguyện chuyển sang trạng thái đã duyệt
    end
```

---

### Thuyết minh nghiệp vụ

**Tên chức năng:** Gửi lời cầu nguyện cá nhân và quy trình kiểm duyệt an toàn nội dung.

**Mã chức năng:** M11.02, M11.04, M11.08

**Mục đích:** Đảm bảo không gian cầu nguyện cộng đồng luôn trang nghiêm, đúng chánh pháp, ngăn chặn các hành vi trục lợi tâm linh, mê tín dị đoan hoặc ngôn từ thù ghét.

**Tác nhân:** Người dùng (Tạo lời nguyện) và Quản trị viên (Kiểm duyệt).

**Điều kiện tiên quyết:** Người dùng đã đăng nhập; Quản trị viên có quyền truy cập Filament Admin.

**Luồng xử lý chính:**
1. Người dùng soạn lời nguyện (cầu an, cầu siêu, hồi hướng, sám hối...) tại `/cau-nguyen/gui-loi-nguyen`.
2. Frontend gọi `POST /api/v1/me/prayer-intentions`.
3. `PrayerIntentionService::createIntention` ghi nhận lời nguyện vào bảng `prayer_intentions` với trạng thái ban đầu bắt buộc là `pending` (chờ duyệt).
4. Đồng thời ghi log vết kiểm duyệt đầu tiên vào bảng `prayer_intention_audits`.
5. Ban biên tập kiểm tra nội dung trong Filament Admin.
6. Khi duyệt:
   - **Nếu Hợp lệ:** Đánh dấu `moderation_status = 'approved'`, cập nhật `published_at = now()`, phát thông báo đến chuông thông báo của tác giả.
   - **Nếu Vi phạm:** Chọn lý do từ chối từ Enum `PrayerRejectionReason` (mê tín, thông tin cá nhân nhạy cảm, quảng cáo...), cập nhật trạng thái `rejected`.
7. Lời nguyện chỉ hiển thị ra bảng tin cộng đồng công khai sau khi đã được phê duyệt.

**Luồng ngoại lệ:**
- Người dùng gửi quá 15 lời nguyện trong 1 phút: Bị chặn bởi Middleware Throttle (mã lỗi 429).

**Kết quả:** Lời nguyện được quản lý vòng đời chặt chẽ, an toàn về mặt truyền thông Phật giáo.

**Các file source code liên quan:**
- Frontend: `src/app/cau-nguyen/gui-loi-nguyen/page.tsx`, `src/components/prayer/SubmitPrayerForm.tsx`, `src/lib/api/prayers.ts`.
- Backend: `app/Http/Controllers/Api/V1/MyPrayerIntentionController.php`, `app/Services/Prayer/PrayerIntentionService.php`, `app/Services/Prayer/PrayerModerationService.php`, `app/Filament/Resources/PrayerIntentions/PrayerIntentionResource.php`.
