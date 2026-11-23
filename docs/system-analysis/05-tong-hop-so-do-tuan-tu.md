# TỔNG HỢP TOÀN BỘ SƠ ĐỒ TUẦN TỰ HỆ THỐNG OMPRAMANI
## (Master Index of UML Sequence Diagrams)

Tài liệu này tổng hợp danh mục và liên kết trực tiếp đến toàn bộ các sơ đồ tuần tự (UML Sequence Diagrams) đại diện cho các luồng nghiệp vụ thực tế của hệ thống.

---

### Danh mục sơ đồ tuần tự theo từng phân hệ

| STT | Mã Chức Năng | Tên Luồng Nghiệp Vụ | Phân Loại Xử Lý | Liên Kết Sơ Đồ & Thuyết Minh |
| :---: | :---: | :--- | :--- | :--- |
| **1** | **M01.01** | Đăng ký tài khoản người dùng mới | Full-stack API (CSRF + Sanctum + Bcrypt) | [dang-ky.md](file:///f:/Ompramani/docs/system-analysis/sequence-diagrams/01-xac-thuc/dang-ky.md) |
| **2** | **M01.02** | Đăng nhập hệ thống (Cookie-based SPA Session) | Full-stack API (RateLimiter + Session Regenerate) | [dang-nhap.md](file:///f:/Ompramani/docs/system-analysis/sequence-diagrams/01-xac-thuc/dang-nhap.md) |
| **3** | **M01.03** | Đăng xuất phiên làm việc | Full-stack API (Session Invalidation) | [dang-xuat.md](file:///f:/Ompramani/docs/system-analysis/sequence-diagrams/01-xac-thuc/dang-xuat.md) |
| **4** | **M01.13** | Tải lên ảnh đại diện cá nhân (Avatar) | Storage Disk / Cloudflare R2 | [tai-len-avatar.md](file:///f:/Ompramani/docs/system-analysis/sequence-diagrams/01-xac-thuc/tai-len-avatar.md) |
| **5** | **M02.04 & 05** | Đọc kinh điển & Duyệt cây mục lục phân cấp | Database Hierarchical Query | [doc-kinh-va-muc-luc.md](file:///f:/Ompramani/docs/system-analysis/sequence-diagrams/02-kinh-dien/doc-kinh-va-muc-luc.md) |
| **6** | **M02.06** | Tự động lưu tiến trình đọc kinh | Optimistic Locking + Debounce 1.5s | [luu-tien-trinh-doc.md](file:///f:/Ompramani/docs/system-analysis/sequence-diagrams/02-kinh-dien/luu-tien-trinh-doc.md) |
| **7** | **M03.01 - 03** | Nhập liệu & Số hóa kinh điển từ DOCX/PDF | Parser Trích xuất văn bản + Admin Approve | [nhap-lieu-va-so-hoa.md](file:///f:/Ompramani/docs/system-analysis/sequence-diagrams/03-so-hoa-kinh-dien/nhap-lieu-va-so-hoa.md) |
| **8** | **M04.01 & 02** | Tra cứu danh tự Chư Phật, Bồ Tát & Đại nguyện | Đa thực thể + Accordion Vows | [tra-cuu-phat-bo-tat.md](file:///f:/Ompramani/docs/system-analysis/sequence-diagrams/04-phat-bo-tat/tra-cuu-phat-bo-tat.md) |
| **9** | **M05.01 - 03** | Tra cứu & Trì niệm thần chú lặp lại | Media Stream + Chant Audio Loop | [tri-niem-than-chu.md](file:///f:/Ompramani/docs/system-analysis/sequence-diagrams/05-than-chu/tri-niem-than-chu.md) |
| **10** | **M06.01, 05, 06** | Khởi tạo phiên tu tập, Heartbeat & Tiến độ | Pessimistic Lock + Heartbeat Clamped <= 120s | [khoi-tao-va-heartbeat-phien.md](file:///f:/Ompramani/docs/system-analysis/sequence-diagrams/06-phien-tu-tap/khoi-tao-va-heartbeat-phien.md) |
| **11** | **M06.07 & M10.04** | Hoàn thành phiên tu tập & Tính chuỗi Streak | Domain Event + Thuật toán tính Streak | [hoan-thanh-phien-tu-tap.md](file:///f:/Ompramani/docs/system-analysis/sequence-diagrams/06-phien-tu-tap/hoan-thanh-phien-tu-tap.md) |
| **12** | **M07.01 - 03** | Thực hành thiền định (Guest Mode & Online Session) | Web Audio Singing Bowl + Session Sync | [thuc-hanh-thien-dinh.md](file:///f:/Ompramani/docs/system-analysis/sequence-diagrams/07-thien-dinh/thuc-hanh-thien-dinh.md) |
| **13** | **M08.01 & 02** | Lần tràng hạt 108 hạt điện tử | **Chỉ chạy Client-side** (Web Audio + Haptic) | [lan-trang-hat-client.md](file:///f:/Ompramani/docs/system-analysis/sequence-diagrams/08-trang-hat/lan-trang-hat-client.md) |
| **14** | **M09.01, 03, 04** | Lập lịch nhắc nhở tu tập & Scheduler tự động | Background Cronjob + Quiet Hours Check | [lap-lich-nhac-nho-va-scheduler.md](file:///f:/Ompramani/docs/system-analysis/sequence-diagrams/09-ke-hoach-nhac-nho/lap-lich-nhac-nho-va-scheduler.md) |
| **15** | **M09.05, 07, 08** | Kế hoạch tu tập tuần & Liên kết phiên thực tế | Time-table Planner + Session Linking | [ke-hoach-va-lien-ket-phien.md](file:///f:/Ompramani/docs/system-analysis/sequence-diagrams/09-ke-hoach-nhac-nho/ke-hoach-va-lien-ket-phien.md) |
| **16** | **M10.01 - 03** | Hành trình tu học & Biểu đồ thống kê tiến độ | Time-series Aggregation + Heatmap | [hanh-trinh-tu-hoc-va-thong-ke.md](file:///f:/Ompramani/docs/system-analysis/sequence-diagrams/10-hanh-trinh-tu-hoc/hanh-trinh-tu-hoc-va-thong-ke.md) |
| **17** | **M11.02, 04, 08** | Gửi lời cầu nguyện cá nhân & Quy trình kiểm duyệt | Moderation Workflow + Audit Logging | [gui-loi-nguyen-va-kiem-duyet.md](file:///f:/Ompramani/docs/system-analysis/sequence-diagrams/11-cau-nguyen/gui-loi-nguyen-va-kiem-duyet.md) |
| **18** | **M11.06 & 07** | Phòng cầu nguyện chung & Cơ chế Polling tin nhắn | **Polling Stream** + Content Reporting | [phong-cau-nguyen-polling.md](file:///f:/Ompramani/docs/system-analysis/sequence-diagrams/11-cau-nguyen/phong-cau-nguyen-polling.md) |
| **19** | **M12.01, 04, 05** | Tra cứu Lịch Vạn Niên & Nông lịch truyền thống | Thuật toán Âm Dương VN + Can Chi, Hoàng Đạo | [tra-cuu-lich-va-nong-lich.md](file:///f:/Ompramani/docs/system-analysis/sequence-diagrams/12-lich-van-nien/tra-cuu-lich-va-nong-lich.md) |
| **20** | **M13.01 & 02** | Tra cứu danh bạ Chùa & Bản đồ chỉ đường | Normalized Search + GPS / Google Maps Link | [tra-cuu-danh-ba-chua.md](file:///f:/Ompramani/docs/system-analysis/sequence-diagrams/13-chua-tu-vien/tra-cuu-danh-ba-chua.md) |
| **21** | **M14.01 & 02** | Tra cứu bài viết giáo lý & Thư viện Video | Relations Eager Load + Iframe Quyền riêng tư | [tra-cuu-bai-viet-va-video.md](file:///f:/Ompramani/docs/system-analysis/sequence-diagrams/14-bai-viet-video/tra-cuu-bai-viet-va-video.md) |
| **22** | **M15.01 & 02** | Tìm kiếm toàn cục đa thực thể & Gợi ý từ khóa | GIN Index + Vietnamese Search Normalizer | [tim-kiem-toan-cuc.md](file:///f:/Ompramani/docs/system-analysis/sequence-diagrams/15-tim-kiem/tim-kiem-toan-cuc.md) |
| **23** | **M16.04 & 05** | Đặt đơn hàng mới kèm Idempotency & Khóa trừ kho | Idempotency Key + Pessimistic Stock Lock | [tao-don-hang-va-tru-kho.md](file:///f:/Ompramani/docs/system-analysis/sequence-diagrams/16-cua-hang-thanh-toan/tao-don-hang-va-tru-kho.md) |
| **24** | **M16.06 - 08** | Thanh toán SePay VietQR, Webhook IPN & Polling | Fail-closed Webhook + SDK SePay QR Code | [thanh-toan-sepay-va-ipn.md](file:///f:/Ompramani/docs/system-analysis/sequence-diagrams/16-cua-hang-thanh-toan/thanh-toan-sepay-va-ipn.md) |
| **25** | **M16.11** | Hủy đơn hàng chưa trả tiền & Tự động hoàn kho | Atomic Stock Restoration (InventoryMovement) | [huy-don-va-hoan-kho.md](file:///f:/Ompramani/docs/system-analysis/sequence-diagrams/16-cua-hang-thanh-toan/huy-don-va-hoan-kho.md) |
| **26** | **M17.01 & M16.12** | Kiểm soát quyền truy cập Panel & Quản trị kho | Gate check (role === 'admin') + Livewire CMS | [kiem-soat-truy-cap-va-quan-ly-admin.md](file:///f:/Ompramani/docs/system-analysis/sequence-diagrams/17-quan-tri-admin/kiem-soat-truy-cap-va-quan-ly-admin.md) |
