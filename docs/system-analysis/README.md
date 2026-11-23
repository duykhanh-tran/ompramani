# TÀI LIỆU PHÂN TÍCH VÀ THIẾT KẾ KIẾN TRÚC HỆ THỐNG OMPRAMANI
## (Senior System Analyst & Software Architect Specification Report)

---

## 1. Giới thiệu tổng quan

Tài liệu này là kết quả phân tích trực tiếp, toàn diện và có tính hệ thống cao từ mã nguồn thực tế của dự án **OmPraMani – Nền tảng số hóa Phật giáo**, được xây dựng phục vụ chuyên sâu cho báo cáo phân tích thiết kế hệ thống và đồ án tốt nghiệp kỹ sư phần mềm.

Hệ thống được kiến trúc theo mô hình **Decoupled Headless Full-stack Architecture**:
- **Frontend:** Next.js 16 (App Router), React 19, TypeScript, Tailwind CSS, Web Audio API.
- **Backend:** Laravel Framework (PHP 8.4), Laravel Sanctum (Stateful Cookie-based SPA Auth), Eloquent ORM.
- **Admin CMS:** Filament Admin Panel v5 (`/admin`).
- **Database:** PostgreSQL 15+ (với mở rộng Full-Text Search, GIN Indexing).
- **Storage & CDN:** Cloudflare R2 Object Storage (qua S3 Flysystem driver).
- **Payment Gateway:** Cổng thanh toán SePay (quét mã VietQR chuyển khoản tự động kèm IPN Webhook bảo mật bằng `X-Secret-Key`).
- **Hàng đợi & Định thời:** Laravel Database Queue & Systemd Timer Scheduler.

---

## 2. Cấu trúc bộ tài liệu

Bộ tài liệu được tổ chức tại thư mục `docs/system-analysis/` gồm các phần chính sau:

| Tên tập tin | Nội dung chi tiết |
| :--- | :--- |
| [README.md](file:///f:/Ompramani/docs/system-analysis/README.md) | Giới thiệu tổng quan kiến trúc, hướng dẫn đọc tài liệu và mục lục hệ thống |
| [01-danh-muc-chuc-nang.md](file:///f:/Ompramani/docs/system-analysis/01-danh-muc-chuc-nang.md) | Bảng danh mục toàn bộ chức năng thực tế của hệ thống với mã định danh chuẩn hóa |
| [02-so-do-phan-ra-tong-quat.md](file:///f:/Ompramani/docs/system-analysis/02-so-do-phan-ra-tong-quat.md) | Sơ đồ phân rã chức năng tổng quát (FDD Cấp 0, 1, 2, 3) bằng cú pháp Mermaid |
| [03-so-do-phan-ra-chi-tiet.md](file:///f:/Ompramani/docs/system-analysis/03-so-do-phan-ra-chi-tiet.md) | Sơ đồ phân rã chức năng chi tiết cho từng nhóm module lớn |
| [04-bang-doi-chieu-source-code.md](file:///f:/Ompramani/docs/system-analysis/04-bang-doi-chieu-source-code.md) | Bảng ma trận đối chiếu từng chức năng từ UI, Route, Controller, Service, Model đến DB Table |
| [05-tong-hop-so-do-tuan-tu.md](file:///f:/Ompramani/docs/system-analysis/05-tong-hop-so-do-tuan-tu.md) | Mục lục liên kết toàn bộ sơ đồ tuần tự UML (Sequence Diagrams) |
| [06-bao-cao-tong-hop.md](file:///f:/Ompramani/docs/system-analysis/06-bao-cao-tong-hop.md) | Báo cáo phân tích kiến trúc, tính toàn vẹn dữ liệu, concurrency và bảng kiểm tra chất lượng |
| **`sequence-diagrams/`** | Thư mục chứa các sơ đồ tuần tự chi tiết và bài thuyết minh cho từng chức năng theo module |

---

## 3. Hệ thống mã định danh chức năng chuẩn hóa

Mã chức năng được thiết kế theo cấu trúc phân cấp: `M<Mã_Nhóm>.<Số_Thứ_Tự>`:
- **M01:** Xác thực & Bảo mật tài khoản (IAM)
- **M02:** Kinh điển & Thư viện đọc kinh (Sutras & Reading Library)
- **M03:** Số hóa & Nhập dữ liệu kinh điển (Ingestion Engine)
- **M04:** Tra cứu Phật & Bồ Tát (Buddhist Figures)
- **M05:** Thần chú & Trì niệm (Mantras & Chanting)
- **M06:** Phiên tu tập & Chuông mõ điện tử (Practice Sessions & Instruments)
- **M07:** Thiền định (Meditation Practice)
- **M08:** Tràng hạt điện tử (Mala Beads)
- **M09:** Kế hoạch & Nhắc nhở tu tập (Practice Planner & Reminders)
- **M10:** Hành trình tu học & Chuỗi tu tập (Dharma Journey & Practice Streak)
- **M11:** Cầu nguyện & Phòng tâm linh trực tuyến (Prayer & Spiritual Room)
- **M12:** Lịch vạn niên & Nông lịch (Lunisolar Calendar & Almanac)
- **M13:** Danh bạ Chùa & Tu viện (Temples & Monasteries Directory)
- **M14:** Bài viết giáo lý & Video Phật pháp (Articles & Media Library)
- **M15:** Tìm kiếm toàn cục (Global Search Engine)
- **M16:** Cửa hàng Phật phẩm & Thanh toán SePay (Shop & SePay Checkout)
- **M17:** Quản trị hệ thống (Filament Admin Management)
