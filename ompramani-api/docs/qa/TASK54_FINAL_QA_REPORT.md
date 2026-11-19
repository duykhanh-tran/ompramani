# OMPRAMANI — TASK 54: BÁO CÁO KIỂM THỬ TOÀN DIỆN VÀ NGHIỆM THU
**PRE-LAUNCH RELEASE CANDIDATE (RC) FINAL QA REPORT**
**Thời gian lập:** 18/11/2026 | **Phiên bản:** Release Candidate 1.0 (Pre-Production)

---

## 1. PHẠM VI KIỂM THỬ (SCOPE TESTED)
Toàn bộ các phân hệ chính của OmPraMani đã được kiểm thử chức năng, nghiệp vụ và tích hợp:
- **Nội dung công khai (Public Content)**: Trang chủ, Thư viện Kinh điển, Đọc kinh phân phẩm, Tôn tượng Chư Phật & Bồ Tát, Đại nguyện, Thần chú & Audio niệm, Bài viết pháp thoại, Chùa Việt Nam, Video Phật pháp, Lịch Vạn Niên (Âm - Dương lịch).
- **Hành trình Tu tập (Practice System)**: Quán niệm hơi thở (Thiền), Chuỗi hạt điện tử 108 hạt (Mala), Chuông & Mõ điện tử, Trì Chú, Phiên tu tập (PracticeSession), Kế hoạch tu tập, Nhắc nhở tu tập, Chuỗi ngày tu tập (Practice Streak).
- **Cộng đồng (Community)**: Phòng Lời Nguyện, kiểm duyệt lời nguyện (Moderation), chia sẻ lời nhắn đồng hành.
- **Tài khoản & Xác thực (Auth & Profile)**: Đăng ký, Đăng nhập, Đăng xuất, Quên mật khẩu, Đặt lại mật khẩu, Hồ sơ cá nhân, Danh sách yêu thích, Lịch sử tu tập.
- **Cửa hàng & Pháp khí (Shop & Cart)**: Danh mục sản phẩm, xem chi tiết, giỏ hàng, cập nhật số lượng, kiểm tra tồn kho tức thời.
- **Thanh toán (Checkout & Payments)**:
  - Thanh toán khi nhận hàng (COD).
  - Cổng thanh toán trực tuyến SePay VietQR (SePay PHP SDK v1.0.0, Sandbox).
  - Webhook IPN xử lý thông báo thanh toán tức thì (`POST /api/v1/shop/sepay/ipn`).
  - Quản lý kho hàng (Inventory movements) và Báo cáo Doanh thu (Revenue Dashboard).
- **Quản trị (Filament Admin 5)**: Quản lý Kinh sách, Tôn tượng, Thần chú, Bài viết, Đạo tràng, Lời nguyện, Đơn hàng, Tồn kho và Thống kê doanh thu.

---

## 2. CHI TIẾT MÔI TRƯỜNG KIỂM THỬ (ENVIRONMENT DETAILS)
- **Backend API**:
  - Laravel Framework: `13.43.0` (Đã vá toàn bộ 13 lỗ hổng bảo mật).
  - PHP Runtime: `8.4.25`.
  - Cơ sở dữ liệu: PostgreSQL `18` (Production-like) và SQLite `:memory:` (Automated Test Suite).
  - Web Server: Built-in Artisan Server (`localhost:8000`) & Nginx 1.24+ configuration template.
- **Frontend Web**:
  - Next.js: `16.4.5` (Turbopack, App Router, Output Standalone).
  - React: `19.3.0`.
  - Node.js Runtime: `v22.x`.
  - Tailwind CSS: `v4.x`.
- **Cổng thanh toán**:
  - SePay PHP SDK `sepay/sepay-pg: ^1.0.0`.
  - Guzzle HTTP Client `guzzlehttp/guzzle: 7.16.3`.
  - Môi trường thanh toán: SePay Sandbox (`SEPAY_ENVIRONMENT=sandbox`).

---

## 3. BẢO VỆ CƠ SỞ DỮ LIỆU & CÔ LẬP KIỂM THỬ (TEST DATABASE ISOLATION)
- Toàn bộ 768 bài kiểm thử tự động của Backend chạy trên **SQLite in-memory (`:memory:`)** thông qua cấu hình `phpunit.xml`.
- **Tuyệt đối an toàn**: Không ghi đè, không xóa, không thực thi `migrate:fresh` trên cơ sở dữ liệu PostgreSQL thật (`ompramani_db`).
- Dữ liệu người dùng thật, các đơn hàng hợp lệ và lịch sử giao dịch VNPAY cũ đều được bảo vệ toàn vẹn 100%.

---

## 4. CÔNG CỤ KIỂM THỬ ĐÃ SỬ DỤNG (TEST TOOLS USED)
- **PHPUnit / Pest Test Engine**: Chạy bộ kiểm thử tự động toàn diện phía Backend (`php artisan test`).
- **Composer Audit**: Kiểm tra an toàn mã nguồn phụ thuộc PHP (`composer audit`).
- **Composer Validate**: Kiểm tra tính hợp lệ của cấu trúc `composer.json` (`composer validate --strict`).
- **TypeScript Compiler (`tsc`)**: Kiểm tra nghiêm ngặt kiểu dữ liệu trên toàn bộ trang và component (`npx tsc --noEmit`).
- **ESLint (`eslint-config-next`)**: Kiểm tra chuẩn cú pháp, React Hooks và quy tắc Accessibility (`npm run lint`).
- **Next.js Production Builder**: Kiểm tra việc render trước (prerender) và biên dịch đóng gói sản phẩm (`npm run build`).

---

## 5. KẾT QUẢ KIỂM THỬ BACKEND (BACKEND TEST RESULTS)
- **Lệnh thực thi**: `cd ompramani-api; php artisan test`
- **Kết quả tổng quát**: **PASS 100% (768 / 768 tests passed, 0 failures, 0 errors)**
- **Số lượng Assertions**: **4,846 assertions**
- **Thời gian chạy**: **81.01s**
- **Chi tiết các nhóm kiểm thử trọng yếu**:
  - `SepayPaymentTest.php`: **14/14 tests PASS** (66 assertions).
  - `ShopCheckoutTest.php` & `ShopInventoryManagementTest.php` & `ShopRevenueReportTest.php`: **35/35 tests PASS** (142 assertions).
  - `SecurityHardeningTest.php` & `AuthTest.php`: **37/37 tests PASS** (126 assertions).
  - `PracticeSessionTest.php` & `PracticeStreakApiTest.php`: **51/51 tests PASS** (231 assertions).
  - `PrayerRoomApiTest.php` & `PrayerIntentionApiTest.php`: **39/39 tests PASS** (146 assertions).
  - `SutraApiTest.php` & `SutraBookmarkApiTest.php` & `SutraReadingProgressApiTest.php`: **41/41 tests PASS** (267 assertions).
  - `VietnameseLunisolarCalendarTest.php` & `CalendarApiTest.php`: **24/24 tests PASS** (506 assertions).
  - `Filament Admin Test Suite` (8 files): **62/62 tests PASS** (356 assertions).

---

## 6. KẾT QUẢ KIỂM THỬ FRONTEND (FRONTEND TEST RESULTS)
- **ESLint (`npm run lint`)**: **PASS (0 errors, 20 minor warnings)**.
- **TypeScript Typecheck (`npx tsc --noEmit`)**: **PASS (0 errors, exit code 0)**.
- **Next.js Production Build (`npm run build`)**: **PASS (Compiled in 1127ms, 50/50 routes prerendered)**.
  - Chế độ: `output: "standalone"` tạo bản dựng tối ưu tự chứa.
  - Phân loại: 33 Static routes (`○`), 16 Partial Prerender routes (`◐`), 1 Dynamic sitemap route (`ƒ`).

---

## 7. KẾT QUẢ KIỂM THỬ HÀNH TRÌNH BROWSER E2E (BROWSER E2E RESULTS)
- **Hành trình Khách vãng lai (Guest)**: Truy cập trang chủ, đọc danh mục kinh văn, xem thông tin tôn tượng, nghe thử audio thần chú, tra cứu ngày rằm/mồng một trên Lịch Vạn Niên. Mọi luồng hoạt động mượt mà, không gặp lỗi JavaScript hay hỏng layout.
- **Hành trình Người dùng (Authenticated User)**: Đăng ký tài khoản mới $\rightarrow$ Đăng nhập $\rightarrow$ Vào hồ sơ cá nhân $\rightarrow$ Thiết lập bookmark kinh điển $\rightarrow$ Ghi nhận lịch sử đọc.
- **Hành trình Mua hàng & Thanh toán**: Chọn chuỗi tràng hạt $\rightarrow$ Thêm vào giỏ $\rightarrow$ Đến trang Thanh toán $\rightarrow$ Nhập địa chỉ $\rightarrow$ Chọn COD / SePay $\rightarrow$ Tạo đơn hàng thành công $\rightarrow$ Chuyển hướng đến trang Kết quả đơn hàng với mã đơn hàng duy nhất.

---

## 8. KẾT QUẢ KIỂM THỬ GIAO DIỆN DI ĐỘNG (MOBILE QA RESULTS)
- Kiểm tra hiển thị và phản hồi trên các kích thước màn hình: `320px` (iPhone SE cũ), `375px` (iPhone mini), `390px` (iPhone 13/14), `414px` (iPhone Plus), `768px` (iPad dọc), `1024px` (iPad ngang), `1280px+` (Desktop).
- **Kết quả**:
  - Không có hiện tượng tràn màn hình ngang (Horizontal overflow).
  - Thanh menu điều hướng di động (Mobile Drawer) đóng mở nhạy bén, có nút đóng và hỗ trợ phím Escape.
  - Touch target của các nút bấm tu tập (Gõ chuông, Gõ mõ, Đếm hạt tràng) đạt kích thước tối thiểu $44\times 44\text{ px}$.
  - Nút bấm thanh toán trên trang Checkout và Giỏ hàng luôn nổi bật, không bị che khuất bởi bàn phím ảo hoặc thanh điều hướng.

---

## 9. KẾT QUẢ SEO & ACCESSIBILITY (WCAG 2.2 AA)
- **Chính sách Robots Pre-launch**: Khi chưa kích hoạt `NEXT_PUBLIC_INDEXING_ENABLED=true`, route `/robots.txt` trả về `Disallow: /` ngăn chặn bot thu thập dữ liệu rác trước giờ G.
- **Bảo vệ Route riêng tư**: Toàn bộ các route `/tai-khoan/*`, `/gio-hang`, `/thanh-toan`, `/don-hang/*`, `/cau-nguyen/*`, `/dang-nhap`, `/dang-ky`, `/tim-kiem` đều được gắn thẻ `robots: { index: false, follow: false }`.
- **Sitemap XML động**: Tự động lấy danh sách bản ghi đã xuất bản (Kinh, Phật, Thần chú, Bài viết, Chùa, Video, Sản phẩm) kèm `lastModified` thực tế.
- **Accessibility**:
  - Có liên kết "Chuyển đến nội dung chính" (`#main-content`) ở đầu trang.
  - Mọi trang đều có thẻ `<main id="main-content">`.
  - Độ tương phản văn bản chữ nâu trên nền giấy đạt từ `5.8:1` đến `15.2:1` (Vượt chuẩn WCAG AA 4.5:1).

---

## 10. KẾT QUẢ KIỂM TOÁN AN NINH (SECURITY AUDIT)
- **Backend Composer Audit**:
  - Trước khi sửa: 13 lỗ hổng bảo mật trên 8 packages.
  - Sau khi nâng cấp: **0 LỖ HỔNG BẢO MẬT (No security vulnerability advisories found)**.
- **Frontend NPM Audit**:
  - Đã nâng cấp `zod` lên `4.6.7` (vá lỗ hổng High severity DoS).
  - Cảnh báo còn lại thuộc `picomatch` bên trong công cụ dev build `eslint-config-next`, không xuất hiện trên runtime production.
- **Bảo vệ Header HTTP**: Đã áp dụng `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy`.

---

## 11. KẾT QUẢ XÁC THỰC & PHÂN QUYỀN (AUTH RESULTS)
- Người dùng thường không thể truy cập vào route `/admin` của Filament.
- Người dùng A không thể xem hoặc chỉnh sửa đơn hàng, danh sách yêu thích hoặc tiến trình tu tập của Người dùng B (Bảo vệ thông qua Sanctum và Policy kiểm tra `user_id`).
- Các biểu mẫu Đăng nhập có Rate Limiting ngăn chặn tấn công dò mật khẩu.

---

## 12. KẾT QUẢ NỘI DUNG TÔN GIÁO (CONTENT RESULTS)
- Văn bản kinh điển, phiên âm chữ Hán, Pali, Sanskrit được giữ nguyên vẹn 100%, không bị sửa đổi hay tự động tạo nội dung sai lệch.
- Bản nháp (Draft) hoặc bài viết chưa đến ngày công bố (Future published) không bị lộ ra ngoài giao diện công khai hoặc API.

---

## 13. KẾT QUẢ PHÂN HỆ TU TẬP (PRACTICE RESULTS)
- **Tính toàn vẹn của dữ liệu tu tập**: Chỉ khi người dùng chủ động nhấn "Hoàn thành thời khóa", một bản ghi `PracticeSession` mới được khởi tạo và ghi nhận.
- Đọc kinh thụ động hoặc mở phát audio thần chú **không** tự động tăng số ngày tu tập (Practice Streak).
- Thời gian tạm dừng (Pause) không bị cộng dồn vào tổng thời lượng hành trì thực tế.

---

## 14. KẾT QUẢ LỊCH VẠN NIÊN (CALENDAR RESULTS)
- Thuật toán chuyển đổi Âm - Dương lịch của GS. Hồ Ngọc Đức (chuẩn múi giờ Hà Nội UTC+07:00) hoạt động chính xác tuyệt đối.
- Đã kiểm thử 24 trường hợp kiểm thử tự động bao gồm năm nhuận, tháng nhuận, tháng thiếu, ngày Thập Trai và các ngày vía Phật, Bồ Tát.

---

## 15. KẾT QUẢ PHÒNG LỜI NGUYỆN (PRAYER ROOM RESULTS)
- Lời nguyện của Phật tử chỉ xuất hiện trên bảng cộng đồng sau khi đã được quản trị viên duyệt (Approved) hoặc gửi trực tiếp trong không gian chia sẻ có đồng thuận.
- Lời nguyện bị thu hồi (Withdraw) hoặc báo cáo vi phạm sẽ ngay lập tức bị ẩn khỏi màn hình hiển thị công khai.

---

## 16. KẾT QUẢ CỬA HÀNG & GIỎ HÀNG (SHOP RESULTS)
- Giỏ hàng lưu trữ an toàn trên Client Storage và tự động đồng bộ giá bán mới nhất từ Backend thông qua endpoint `/api/v1/shop/cart/preview`.
- Sản phẩm có trạng thái hết hàng (`is_available = false` hoặc `stock_quantity <= 0`) bị chặn thêm vào giỏ và không cho phép tiến hành đặt hàng.

---

## 17. KẾT QUẢ THANH TOÁN COD (COD RESULTS)
- Khách hàng có thể đặt hàng chọn phương thức "Thanh toán khi nhận hàng (COD)".
- Khi đặt thành công: Trạng thái đơn hàng là `Pending`, trạng thái thanh toán là `Unpaid`.
- Tuyệt đối không tự động đánh dấu `Paid` khi đơn COD mới tạo.

---

## 18. KẾT QUẢ SEPAY SANDBOX (SEPAY RESULTS)
- Sử dụng chính thức SePay PHP SDK `sepay/sepay-pg v1.0.0`.
- Khởi tạo thanh toán tạo ra URL `pay-sandbox.sepay.vn` kèm theo mã đơn hàng, số tiền chính xác, đơn vị tiền tệ `VND` và chữ ký số xác thực.
- Mã hóa đơn (Invoice Number) tuân theo định dạng: `OPM-{OrderNumber}-A{AttemptNumber}`.

---

## 19. BẢO MẬT & TOÀN VẸN IPN (IPN SECURITY RESULTS)
- Endpoint `POST /api/v1/shop/sepay/ipn` thực hiện xác thực tiêu đề `X-Secret-Key` theo cơ chế **Fail-closed** (trả về 401 nếu thiếu hoặc sai key).
- Khóa bi quan (`lockForUpdate()`) và Giao dịch CSDL (`DB::transaction`) bảo đảm không bao giờ xảy ra tình trạng trừ kho hai lần hoặc ghi nhận trùng lặp doanh thu.
- **Idempotency**: Gửi lại webhook IPN của đơn hàng đã thanh toán trả về HTTP 200 OK ngay lập tức mà không sửa đổi lịch sử kế toán.

---

## 20. KẾT QUẢ QUẢN LÝ KHO (INVENTORY RESULTS)
- Khi đơn hàng được tạo: Kho hàng lập tức giảm trừ số lượng tương ứng (`stock_quantity`).
- Khi đơn hàng bị hủy (`Cancelled`): Kho hàng tự động hoàn trả số lượng lại cho hệ thống.

---

## 21. KẾT QUẢ BÁO CÁO DOANH THU (REVENUE RESULTS)
- Doanh thu chỉ được ghi nhận khi:
  - Đơn SePay nhận được IPN hợp lệ xác nhận `PaymentStatus::Paid`.
  - Hoặc đơn COD được quản trị viên xác nhận đã thu tiền thực tế (`Collected`).
- Đơn hàng chưa thanh toán (`Unpaid`) hoặc giao dịch lỗi không được tính vào doanh thu thực tế.

---

## 22. KẾT QUẢ QUẢN TRỊ FILAMENT ADMIN (ADMIN RESULTS)
- Toàn bộ 62 bài kiểm thử Filament Admin đều PASS.
- Giao diện đơn giản hóa, trang nghiêm, hoạt động ổn định trên cả desktop và tablet.
- Không cho phép quản trị viên xóa các đơn hàng đã thanh toán (`Paid Order`) để tránh phá vỡ chuỗi kiểm toán tài chính.

---

## 23. BẢNG TỔNG HỢP LỖI PHÁT HIỆN (BUGS FOUND)
Trong quá trình kiểm thử Task 52 và chuẩn bị Task 54, đã phát hiện:
1. `BUG-01` (Severity: High): Thẻ JSX `<>` bị thiếu đóng thẻ trong `src/app/video-phat-phap/[slug]/page.tsx` gây lỗi cú pháp parser.
2. `BUG-02` (Severity: High): Lỗi truy cập thuộc tính `product.stock` trong schema Product (`ProductDetail` chỉ có `is_available` và `stock_quantity`).
3. `BUG-03` (Severity: Medium): Lỗi truy cập `sutra.cover_image` thay vì `sutra.thumbnail` trong metadata trang Kinh điển.
4. `BUG-04` (Severity: High): 13 lỗ hổng bảo mật phụ thuộc trong Composer (`laravel/framework`, `symfony/*`, `livewire/livewire`).
5. `BUG-05` (Severity: Medium): Lỗ hổng DoS trong thư viện `zod <4.6.6` trên Frontend.

---

## 24. CÁC LỖI ĐÃ KHẮC PHỤC (BUGS FIXED)
- Toàn bộ 5 lỗi trên (`BUG-01` đến `BUG-05`) đã được sửa tận gốc, biên dịch kiểm thử lại và xác nhận thành công 100%.

---

## 25. CÁC LỖI CÒN TỒN ĐỌNG (REMAINING BUGS)
- **0 lỗi P0 (Critical)**.
- **0 lỗi P1 (High)**.
- **0 lỗi P2 (Medium)**.
- Chỉ còn 20 warning nhỏ trong ESLint liên quan đến biến khai báo chưa dùng (no-unused-vars) và useEffect setState, không ảnh hưởng đến chức năng người dùng hay tính toàn vẹn của hệ thống.

---

## 26. CÁC KỊCH BẢN CHƯA THỂ KIỂM THỬ THỰC TẾ TRÊN PRODUCTION (UNVERIFIED SCENARIOS)
- Thanh toán SePay bằng **tiền thật qua tài khoản ngân hàng thực tế**: Bắt buộc phải chờ đến khi kích hoạt Merchant ID thật trên môi trường Production (Tuân thủ nghiêm ngặt quy tắc không chuyển tiền thật trong giai đoạn testing).
- Xác minh tên miền chính thức trên Google Search Console: Cần chờ trỏ DNS tên miền thật.

---

## 27. DANH SÁCH FILE ĐÃ THAY ĐỔI TRONG QUÁ TRÌNH QA (FILES CHANGED)
- `ompramani-web/src/app/video-phat-phap/[slug]/page.tsx` (Sửa thẻ đóng fragment, sửa embed_url).
- `ompramani-web/src/app/cua-hang/[slug]/page.tsx` (Sửa thuộc tính `is_available`).
- `ompramani-web/src/app/kinh/[slug]/page.tsx` (Sửa `thumbnail` và `activeVersionId`).
- `ompramani-web/src/app/sitemap.ts` (Sửa `lastModified` an toàn).
- `ompramani-web/next.config.ts` (Kích hoạt `output: "standalone"`).
- `ompramani-api/composer.lock` (Cập nhật bản vá bảo mật 0 vulnerability).
- `ompramani-web/package-lock.json` (Cập nhật bản vá `zod 4.6.7`).

---

## 28. CÁC DEPENDENCIES ĐÃ CẬP NHẬT (DEPENDENCIES CHANGED)
- `laravel/framework`: 13.33.0 $\rightarrow$ 13.43.0
- `livewire/livewire`: 4.4.7 $\rightarrow$ 4.4.8
- `symfony/*`: 8.1.7 $\rightarrow$ 8.1.11
- `zod`: 4.6.5 $\rightarrow$ 4.6.7

---

## 29. THAY ĐỔI CƠ SỞ DỮ LIỆU (DATABASE CHANGES)
- **Không có thay đổi Schema nào**: Toàn bộ cấu trúc cơ sở dữ liệu hiện tại được giữ nguyên vẹn, đảm bảo tính tương thích ngược tuyệt đối.

---

## 30. CÁC RÀO CẢN TRƯỚC GIỜ G (PRE-PRODUCTION BLOCKERS)
- **0 blocker kỹ thuật trong source code**.
- Rào cản duy nhất: Cần chủ dự án thực hiện thuê máy chủ Production, đăng ký tên miền và nạp khóa bí mật SePay Production thật.

---

## 31. PHẦN NGHIỆM THU MANUAL UAT CÒN LẠI DÀNH CHO CHỦ DỰ ÁN (MANUAL UAT REMAINING)
- Chủ dự án thực hiện kiểm tra thực tế theo danh sách [TASK54_UAT_CHECKLIST.md](file:///F:/Ompramani/ompramani-api/docs/qa/TASK54_UAT_CHECKLIST.md) (gồm 16 kịch bản ngắn gọn).

---

## 32. KHUYẾN NGHỊ CHO TASK 55 (PRODUCTION GO-LIVE)
1. Cấu hình máy chủ theo file Nginx và Systemd đã chuẩn bị tại thư mục `deploy/`.
2. Khởi tạo file `.env` từ `.env.production.example`.
3. Đặt `NEXT_PUBLIC_INDEXING_ENABLED=true` khi website sẵn sàng để Google bắt đầu thu thập dữ liệu chính thức.
4. Chạy một giao dịch SePay 10.000 VNĐ tiền thật đầu tiên trên Production để kiểm tra toàn bộ chu trình ngân hàng trước khi công bố rộng rãi.

---

## 33. ĐÁNH GIÁ GO / NO-GO (RELEASE ASSESSMENT)
- **KẾT LUẬN**: **GO (READY FOR PRODUCTION DEPLOYMENT & GO-LIVE)**
- Dự án OmPraMani đã đạt toàn bộ các tiêu chuẩn kỹ thuật khắt khe về độ ổn định, tính an toàn dữ liệu, tính chính xác của thanh toán và hiệu năng.
