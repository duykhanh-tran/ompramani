# BẢNG MA TRẬN ĐỐI CHIẾU SOURCE CODE TOÀN HỆ THỐNG
## (End-to-End Source Code Traceability Matrix)

Ma trận đối chiếu này thể hiện tính nhất quán tuyệt đối giữa giao diện người dùng, lời gọi mạng, tầng điều khiển, tầng nghiệp vụ và cơ sở dữ liệu vật lý.

---

### Bảng 1: Phân hệ Xác thực & Bảo mật (M01)

| Mã CN | Tên chức năng | Giao diện Next.js | API Client Module | HTTP Endpoint | Controller & Method | Service / Request | Model & Bảng DB | Kỹ thuật an toàn áp dụng |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **M01.01** | Đăng ký tài khoản | `/dang-ky`<br>`RegisterForm.tsx` | `register()` trong `auth.ts` | `POST /api/v1/auth/register` | `AuthController::register` | `RegisterRequest`<br>Event `Registered` | `User`<br>Bảng `users` | Role ép cứng 'user', Hash password bcrypt 12 vòng, CSRF |
| **M01.02** | Đăng nhập | `/dang-nhap`<br>`LoginForm.tsx` | `login()` trong `auth.ts` | `POST /api/v1/auth/login` | `AuthController::login` | `LoginRequest::authenticate` | `User`<br>Bảng `users` | Rate limit 10 lần/phút, Session Regenerate chống Session Fixation |
| **M01.03** | Đăng xuất | `Header.tsx`<br>`AuthContext.tsx` | `logout()` trong `auth.ts` | `POST /api/v1/auth/logout` | `AuthController::logout` | Auth Guard `web` | Bảng `sessions` | Hủy session và invalidate token trên server |
| **M01.04** | Lấy thông tin User | `AuthContext.tsx` | `getCurrentUser()` trong `auth.ts` | `GET /api/v1/auth/me` | `AuthController::me` | `UserResource` | `User`<br>Bảng `users` | Middleware `auth:sanctum`, Cache-Control `no-store` |
| **M01.05** | Khởi tạo CSRF | `client.ts` | `getCsrfCookie()` trong `auth.ts` | `GET /sanctum/csrf-cookie` | Laravel Sanctum Kernel | Sanctum Middleware | Cookie `XSRF-TOKEN` | Bảo vệ toàn bộ request POST/PUT/DELETE chống tấn công CSRF |
| **M01.06** | Quên mật khẩu | `/quen-mat-khau` | `forgotPassword()` trong `auth.ts` | `POST /api/v1/auth/forgot-password` | `PasswordResetController::forgotPassword` | Laravel Password Broker | Bảng `password_reset_tokens` | Rate limit 6 req/phút, token băm SHA-256 an toàn |
| **M01.07** | Đặt lại mật khẩu | `/dat-lai-mat-khau` | `resetPassword()` trong `auth.ts` | `POST /api/v1/auth/reset-password` | `PasswordResetController::resetPassword` | Password Broker Reset | `User`, `password_reset_tokens` | Token một lần, tự động thu hồi sau khi đặt lại thành công |
| **M01.08** | Xác thực Email | `/xac-thuc-email` | Native Link Fetch | `GET /api/v1/auth/email/verify/{id}/{hash}` | `EmailVerificationController::verify` | Middleware `signed` | `User`<br>Bảng `users` | URL ký số mật mã chống giả mạo URL xác thực |
| **M01.11** | Xem hồ sơ | `/tai-khoan` | `getProfile()` trong `profile.ts` | `GET /api/v1/profile` | `ProfileController::show` | `UserProfileResource` | `UserProfile`<br>Bảng `user_profiles` | Tự động tạo hồ sơ mặc định nếu chưa tồn tại |
| **M01.12** | Cập nhật hồ sơ | `/tai-khoan` | `updateProfile()` trong `profile.ts` | `PATCH /api/v1/profile` | `ProfileController::update` | `UpdateProfileRequest` | `UserProfile` | Kiểm tra định dạng ngày sinh, múi giờ theo IANA |
| **M01.13** | Tải lên Avatar | `/tai-khoan` | `uploadAvatar()` trong `profile.ts` | `POST /api/v1/profile/avatar` | `ProfileController::uploadAvatar` | `UploadAvatarRequest` | Storage Disk R2/Local | Giới hạn dung lượng 2MB, định dạng mimes: jpeg,png,webp |
| **M01.15** | Đổi mật khẩu | `/tai-khoan` | `changePassword()` trong `profile.ts` | `PATCH /api/v1/account/password` | `AccountSecurityController::changePassword` | `ChangePasswordRequest` | `User`<br>Bảng `users` | Bắt buộc nhập mật khẩu cũ, kiểm tra `Hash::check()` |

---

### Bảng 2: Phân hệ Kinh điển & Số hóa (M02, M03)

| Mã CN | Tên chức năng | Giao diện Next.js | API Client Module | HTTP Endpoint | Controller & Method | Service / Request | Model & Bảng DB | Kỹ thuật an toàn áp dụng |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **M02.01** | Danh sách kinh | `/kinh`<br>`SutraCard.tsx` | `getSutras()` trong `sutras.ts` | `GET /api/v1/sutras` | `SutraController::index` | Scope `published` | `Sutra`<br>Bảng `sutras` | Lọc trạng thái published, phân trang Server-side |
| **M02.02** | Chi tiết bộ kinh | `/kinh/[slug]` | `getSutra()` trong `sutras.ts` | `GET /api/v1/sutras/{slug}` | `SutraController::show` | Eager loading `versions` | `Sutra`, `SutraVersion` | Kiểm tra slug duy nhất, 404 chuẩn hóa |
| **M02.04** | Cây mục lục kinh | `SutraTableOfContents.tsx` | `getSutraSections()` trong `sutras.ts` | `GET /api/v1/sutras/{slug}/sections` | `SutraController::sections` | Đệ quy cây `parent_id` | `SutraSection` | Cấu trúc cây phân cấp (Tree hierarchy) |
| **M02.05** | Đọc nội dung phẩm | `SutraReaderArea.tsx` | `getSutraSection()` trong `sutras.ts` | `GET /api/v1/sutra-sections/{id}` | `SutraSectionController::show` | Eager loading `sutra` | `SutraSection` | Tùy chỉnh cỡ chữ font-size lưu LocalStorage |
| **M02.06** | Lưu tiến trình đọc | `SutraReaderArea.tsx` | `saveReadingProgress()` trong `reading-progress.ts` | `PUT /api/v1/me/sutras/{slug}/reading-progress` | `SutraReadingProgressController::upsert` | Optimistic lock `revision` | `SutraReadingProgress` | Khóa lạc quan revision tránh xung đột đa tab, debounce 1.5s |
| **M02.09** | Tạo bookmark | `SutraReaderArea.tsx` | `createSutraBookmark()` trong `sutra-bookmarks.ts` | `POST /api/v1/me/sutra-bookmarks` | `SutraBookmarkController::store` | `StoreBookmarkRequest` | `SutraBookmark` | Giới hạn độ dài ghi chú, tránh tạo bookmark trùng lặp |
| **M03.01** | Tải lên file gốc | Filament Admin Panel | Livewire Upload Form | Admin Resource | `DocumentImportService::storeSourceDocument` | `DocxParser`<br>`PdfTextParser` | `SourceDocument`<br>`DocumentImport` | Băm checksum SHA-256, kiểm tra định dạng docx/pdf |
| **M03.03** | Duyệt & Số hóa | Filament Admin Panel | Livewire Action Button | Admin Action `approve` | `DocumentImportService::approveImport` | `DB::transaction` | `SutraVersion`<br>`SutraSection` | Transaction toàn vẹn dữ liệu từ import sang bản chính thức |

---

### Bảng 3: Phân hệ Công cụ Tu tập (M06, M07, M08)

| Mã CN | Tên chức năng | Giao diện Next.js | API Client Module | HTTP Endpoint | Controller & Method | Service / Request | Model & Bảng DB | Kỹ thuật an toàn áp dụng |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **M06.01** | Bắt đầu phiên tu | `InstrumentsClient.tsx`<br>`MeditationClient.tsx` | `startPracticeSession()` trong `practice-sessions.ts` | `POST /api/v1/me/practice-sessions` | `PracticeSessionController::store` | `PracticeSessionService::startSession` | `PracticeSession`<br>Bảng `practice_sessions` | Idempotency qua `client_session_key`, `lockForUpdate()`, hủy phiên cũ |
| **M06.05** | Heartbeat định kỳ | `usePracticeSession.ts` | `heartbeatPracticeSession()` | `POST /api/v1/me/practice-sessions/{id}/heartbeat` | `PracticeSessionController::heartbeat` | `PracticeSessionService::heartbeat` | `PracticeSession` | Clamping thời gian tối đa 120 giây giữa 2 heartbeat chống gian lận |
| **M06.06** | Cập nhật tiến độ | `InstrumentsClient.tsx` | `updatePracticeProgress()` | `PATCH /api/v1/me/practice-sessions/{id}/progress` | `PracticeSessionController::updateProgress` | `PracticeSessionService::updateProgress` | `PracticeSession` | Monotonic `sequence` ref chống trễ mạng và race conditions |
| **M06.07** | Hoàn thành phiên | `InstrumentCompletionModal.tsx` | `completePracticeSession()` | `POST /api/v1/me/practice-sessions/{id}/complete` | `PracticeSessionController::complete` | Event `PracticeSessionCompleted`<br>`PracticeStreakService` | `PracticeSession` | Bắn Event cập nhật chuỗi ngày tu tập (Streak) trong transaction |
| **M06.09** | Chuông mõ Web Audio | `/chuong-mo`<br>`bellAudio.ts` | Không gọi API | None (Client Audio API) | None | Native Web Audio API | None (Không lưu DB) | Tổng hợp sóng âm chuông bát (Singing bowl) và gõ mõ bằng AudioContext |
| **M08.01** | Tràng hạt số | `/trang-hat`<br>`MalaClient.tsx` | Không gọi API | None (Client State) | None | Native Haptic & Web Audio | LocalStorage | Lắng nghe phím Space/Enter toàn cục, rung `navigator.vibrate` |

---

### Bảng 4: Phân hệ Cửa hàng & Thanh toán SePay (M16)

| Mã CN | Tên chức năng | Giao diện Next.js | API Client Module | HTTP Endpoint | Controller & Method | Service / Request | Model & Bảng DB | Kỹ thuật an toàn áp dụng |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **M16.01** | Xem sản phẩm | `/cua-hang`<br>`[slug]/page.tsx` | `getProducts()` trong `shop.ts` | `GET /api/v1/shop/products` | `ShopProductController::index` | Model Policy | `Product` | Tự động loại trừ các khẳng định mê tín (`FORBIDDEN_CLAIMS`) |
| **M16.02** | Giỏ hàng Client | `/gio-hang`<br>`CartContext.tsx` | Không gọi API | None (Client Engine) | None | `cartStorage.ts` | LocalStorage | Đồng bộ giỏ hàng đa tab qua sự kiện `storage`, giới hạn số lượng |
| **M16.04** | Xem trước Checkout | `/thanh-toan`<br>`CheckoutClient.tsx` | `previewCheckout()` trong `shop.ts` | `POST /api/v1/shop/checkout/preview` | `ShopOrderController::checkoutPreview` | `ShippingFeePolicy` | `Product` | Tính phí giao hàng theo tỉnh/thành, kiểm tra còn hàng tức thì |
| **M16.05** | Tạo đơn hàng | `/thanh-toan`<br>`CheckoutClient.tsx` | `createOrder()` trong `shop.ts` | `POST /api/v1/shop/orders` | `ShopOrderController::store` | `OrderWorkflowService`<br>`InventoryService` | `Order`, `OrderItem`, `InventoryMovement` | `idempotency_key`, `lockForUpdate()` trừ kho hàng tồn, audit history |
| **M16.06** | Form SePay VietQR | `CheckoutClient.tsx` | Nhận kèm response tạo đơn | Trực tiếp từ `store()` | `SepayPaymentService::generateCheckoutForm` | `SePay\SePayClient` SDK | `OrderPayment` | Sinh mã hóa đơn OPM-XXXX-A1 ký chữ ký số, auto-submit POST |
| **M16.07** | SePay Webhook IPN | SePay Gateway Server | Server-to-Server Webhook | `POST /api/v1/shop/sepay/ipn` | `SepayPaymentController::handleIpn` | `verifyIpnSecretKey`<br>`OrderWorkflowService` | `Order`, `OrderPayment`, `OrderStatusHistory` | Fail-closed xác thực `X-Secret-Key`, kiểm tra số tiền khớp 100% |
| **M16.08** | Polling thanh toán | `PaymentResultClient.tsx` | `getPaymentStatus()` | `GET /api/v1/shop/orders/{publicId}/payment-status` | `SepayPaymentController::getPaymentStatus` | Query Status | `Order`, `OrderPayment` | Polling tối đa 10 lần (chu kỳ 3s), nút kiểm tra thủ công |
| **M16.09** | Chuyển sang COD | `PaymentResultClient.tsx` | `switchOrderToCod()` | `POST /api/v1/shop/orders/{publicId}/switch-to-cod` | `ShopOrderController::switchToCod` | `OrderWorkflowService` | `Order`, `OrderPayment` | Chỉ cho phép chuyển đổi khi đơn chưa thanh toán, rate limit |
| **M16.11** | Hủy đơn & Hoàn kho | `/tai-khoan/don-hang` | `cancelOrder()` | `POST /api/v1/shop/me/orders/{publicId}/cancel` | `ShopOrderController::cancel` | `OrderWorkflowService` | `Order`, `Product`, `InventoryMovement` | Hoàn lại số lượng tồn kho nguyên vẹn cho sản phẩm Tracked |
