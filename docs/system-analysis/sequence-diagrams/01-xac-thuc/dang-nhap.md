# SƠ ĐỒ TUẦN TỰ: ĐĂNG NHẬP HỆ THỐNG (M01.02)

```mermaid
sequenceDiagram
    autonumber
    actor NguoiDung as Người dùng
    participant FE as Giao diện (/dang-nhap)
    participant ClientAPI as auth.ts (ApiClient)
    participant Sanctum as Laravel Sanctum
    participant AuthCtrl as AuthController
    participant Req as LoginRequest
    participant DB as PostgreSQL (users, sessions)

    NguoiDung->>FE: Nhập email, mật khẩu & bấm "Đăng nhập"
    FE->>ClientAPI: login({ email, password, remember })
    ClientAPI->>Sanctum: GET /sanctum/csrf-cookie
    Sanctum-->>ClientAPI: Set-Cookie: XSRF-TOKEN
    ClientAPI->>AuthCtrl: POST /api/v1/auth/login (Header: X-XSRF-TOKEN)
    AuthCtrl->>Req: authenticate() (Kiểm tra RateLimiter: 10 lần/phút)

    alt Quá số lần đăng nhập sai
        Req-->>AuthCtrl: TooManyRequestsHttpException (429)
        AuthCtrl-->>ClientAPI: HTTP 429 { message: "Quá nhiều lần thử..." }
        ClientAPI-->>FE: Báo lỗi giới hạn tần suất
        FE-->>NguoiDung: Thông báo tạm khóa đăng nhập trong X giây
    else Thông tin sai mật khẩu
        Req->>DB: Truy vấn User theo email & Hash::check()
        DB-->>Req: Mật khẩu không trùng khớp
        Req-->>AuthCtrl: ValidationException (422)
        AuthCtrl-->>ClientAPI: HTTP 422 { errors: { email: [...] } }
        ClientAPI-->>FE: Báo lỗi xác thực
        FE-->>NguoiDung: Hiển thị "Thông tin đăng nhập không chính xác"
    else Xác thực thành công
        Req->>DB: User hợp lệ
        AuthCtrl->>Sanctum: session()->regenerate() (Chống Session Fixation)
        AuthCtrl->>DB: Cập nhật bảng sessions
        AuthCtrl-->>ClientAPI: HTTP 200 OK { data: { user: UserResource } }
        ClientAPI-->>FE: Trả về đối tượng AuthUser
        FE->>FE: AuthContext.setUser(user), isAuthenticated = true
        FE-->>NguoiDung: Chuyển hướng về trang trước đó (hoặc /tai-khoan)
    end
```

---

### Thuyết minh nghiệp vụ

**Tên chức năng:** Đăng nhập hệ thống qua Cookie-based Stateful Session.

**Mã chức năng:** M01.02

**Mục đích:** Xác thực danh tính người dùng bằng email và mật khẩu, cấp phiên làm việc có bảo vệ CSRF để sử dụng các tính năng cá nhân hóa.

**Tác nhân:** Khách hoặc Người dùng có tài khoản.

**Điều kiện tiên quyết:** Người dùng đã có tài khoản trên hệ thống.

**Luồng xử lý chính:**
1. Người dùng nhập email và mật khẩu tại `/dang-nhap`.
2. Frontend gọi `getCsrfCookie()` để nhận `XSRF-TOKEN` từ Sanctum.
3. Gửi `POST /api/v1/auth/login` kèm thông tin đăng nhập và header `X-XSRF-TOKEN`.
4. `LoginRequest::authenticate` kiểm tra giới hạn tần suất đăng nhập (tối đa 10 lần/phút theo IP và email).
5. Kiểm tra mật khẩu băm trong database qua `Hash::check()`.
6. Thực hiện đăng nhập (`Auth::attempt`), tái tạo session ID (`session()->regenerate()`) để phòng chống tấn công chiếm đoạt phiên (Session Fixation).
7. Trả về thông tin người dùng định dạng `UserResource` (mã HTTP 200).
8. `AuthContext` lưu trữ thông tin người dùng trong bộ nhớ React và chuyển hướng trang.

**Luồng ngoại lệ:**
- Đăng nhập sai thông tin: Trả về lỗi 422 Unprocessable Entity.
- Thử sai quá 10 lần: Trả về lỗi 429 Too Many Requests, yêu cầu chờ mở khóa.

**Kết quả:** Phiên làm việc được kích hoạt an toàn trong Cookie của trình duyệt.

**Các file source code liên quan:**
- Frontend: `src/app/dang-nhap/page.tsx`, `src/lib/api/auth.ts` (hàm `login`), `src/context/AuthContext.tsx`.
- Backend: `app/Http/Controllers/Api/V1/Auth/AuthController.php` (hàm `login`), `app/Http/Requests/Api/V1/Auth/LoginRequest.php`.
