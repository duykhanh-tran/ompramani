# SƠ ĐỒ TUẦN TỰ: TẢI LÊN ẢNH ĐẠI DIỆN AVATAR (M01.13)

```mermaid
sequenceDiagram
    autonumber
    actor NguoiDung as Người dùng
    participant FE as Trang Hồ Sơ (/tai-khoan)
    participant ClientAPI as profile.ts (uploadAvatar)
    participant Ctrl as ProfileController
    participant Req as UploadAvatarRequest
    participant Disk as Storage Disk (R2 / Local Public)
    participant DB as PostgreSQL (user_profiles)

    NguoiDung->>FE: Chọn file ảnh đại diện mới (.jpg, .png, .webp)
    FE->>FE: Preview ảnh cục bộ (FileReader/URL.createObjectURL)
    FE->>ClientAPI: uploadAvatar(file: File) (FormData)
    ClientAPI->>Ctrl: POST /api/v1/profile/avatar (Multipart/form-data)
    Ctrl->>Req: Kiểm tra định dạng (mimes: jpg,jpeg,png,webp; max: 2048 KB)

    alt File quá dung lượng hoặc sai định dạng
        Req-->>Ctrl: Lỗi xác thực 422
        Ctrl-->>ClientAPI: HTTP 422 { message: "File tải lên không hợp lệ." }
        ClientAPI-->>FE: Báo lỗi tải lên
        FE-->>NguoiDung: Hiển thị cảnh báo dung lượng/định dạng
    else File hợp lệ
        Ctrl->>Disk: Xóa avatar cũ nếu tồn tại ($profile->avatar_path)
        Ctrl->>Disk: Lưu file mới: $file->store('avatars', $publicDisk)
        Disk-->>Ctrl: Trả về đường dẫn lưu trữ mới (e.g. avatars/xyz.webp)
        Ctrl->>DB: UserProfile::updateOrCreate(['user_id' => $user->id], ['avatar_path' => $path])
        DB-->>Ctrl: Bản ghi cập nhật thành công
        Ctrl-->>ClientAPI: HTTP 200 OK { data: UserProfileResource }
        ClientAPI-->>FE: Dữ liệu hồ sơ mới kèm URL avatar
        FE-->>NguoiDung: Cập nhật hiển thị ảnh đại diện mới trên giao diện
    end
```

---

### Thuyết minh nghiệp vụ

**Tên chức năng:** Tải lên ảnh đại diện cá nhân (Avatar Upload).

**Mã chức năng:** M01.13

**Mục đích:** Cho phép người dùng tùy biến hình ảnh đại diện cá nhân trong hồ sơ và khi tham gia thảo luận tại phòng cầu nguyện.

**Tác nhân:** Người dùng đã đăng nhập.

**Điều kiện tiên quyết:** Người dùng có tài khoản hợp lệ, file ảnh hợp chuẩn.

**Luồng xử lý chính:**
1. Người dùng chọn ảnh đại diện từ thiết bị tại `/tai-khoan`.
2. Frontend đóng gói file vào `FormData` và gọi hàm `uploadAvatar()`.
3. Gửi request `POST /api/v1/profile/avatar` kèm header xác thực Sanctum.
4. `UploadAvatarRequest` kiểm tra định dạng (jpg, jpeg, png, webp) và dung lượng tối đa 2048 KB (2MB).
5. `ProfileController::uploadAvatar` kiểm tra nếu người dùng đã có avatar cũ thì gọi lệnh xóa file cũ khỏi disk lưu trữ.
6. Lưu file mới vào thư mục `avatars/` trên disk cấu hình (`public` hoặc Cloudflare R2 bucket `ompramani-public`).
7. Cập nhật trường `avatar_path` trong bảng `user_profiles`.
8. Trả về thông tin hồ sơ mới qua `UserProfileResource` (mã HTTP 200).
9. Giao diện người dùng làm mới ảnh đại diện ngay lập tức.

**Luồng ngoại lệ:**
- File không đúng định dạng ảnh hoặc lớn hơn 2MB: Trả về lỗi 422 Unprocessable Entity.

**Kết quả:** File ảnh được lưu trữ an toàn trên Cloudflare R2 / Storage disk, đường dẫn mới được cập nhật vào database.

**Các file source code liên quan:**
- Frontend: `src/app/tai-khoan/page.tsx`, `src/lib/api/profile.ts` (hàm `uploadAvatar`).
- Backend: `app/Http/Controllers/Api/V1/ProfileController.php` (hàm `uploadAvatar`), `app/Http/Requests/Api/V1/Profile/UploadAvatarRequest.php`, `app/Models/UserProfile.php`.
