# OMPRAMANI — TASK 54: SỔ ĐĂNG KÝ VÀ THEO DÕI LỖI
**BUG REGISTER & REMEDIATION LOG**

Tài liệu này ghi nhận toàn bộ các lỗi kỹ thuật được phát hiện trong chu kỳ kiểm thử chuẩn bị tiền phát hành (Pre-Release Testing Cycle), mức độ nghiêm trọng, nguyên nhân gốc rễ và bằng chứng kiểm thử lại.

---

### Bảng phân loại mức độ nghiêm trọng:
- **P0 (Critical)**: Lộ thông tin bí mật, bypass phân quyền, sai số tiền thanh toán, mất mát dữ liệu tài chính hoặc người dùng.
- **P1 (High)**: Không thể checkout, hỏng luồng đặt hàng, sai tồn kho, hỏng trang chức năng cốt lõi.
- **P2 (Medium)**: Lỗi giao diện trên một số viewport, lỗi cú pháp hoặc cảnh báo kiểu dữ liệu không ảnh hưởng chức năng chính.
- **P3 (Low)**: Lỗi thẩm mỹ nhỏ, khoảng cách lề, lỗi chính tả phụ.

---

## DANH SÁCH LỖI VÀ TRẠNG THÁI KHẮC PHỤC

### 1. `BUG-SEC-01` — Lỗ hổng bảo mật chuỗi phụ thuộc Composer (13 Advisories)
- **Mức độ nghiêm trọng**: **P1 (High)**
- **Phân hệ bị ảnh hưởng**: Backend Framework (`ompramani-api`)
- **Mô tả**: Lệnh `composer audit` ban đầu báo cáo 13 lỗ hổng bảo mật trên 8 packages cốt lõi (`laravel/framework`, `livewire/livewire`, `symfony/html-sanitizer`, `symfony/http-foundation`, `symfony/http-kernel`, `symfony/mime`, `symfony/routing`, `symfony/yaml`).
- **Nguyên nhân gốc rễ**: Các package trong `composer.lock` cũ bị khóa ở các phiên bản trước bản vá bảo mật tháng 11/2026.
- **Giải pháp**: Thực hiện nâng cấp có mục tiêu an toàn (`composer update "laravel/framework" "livewire/livewire" "symfony/*" -W`). Nâng cấp `laravel/framework` lên `13.43.0`, `livewire` lên `4.4.8`, và các package Symfony lên `8.1.11`.
- **Tương thích SePay**: `sepay/sepay-pg: 1.0.0` và `guzzlehttp/guzzle: 7.16.3` hoàn toàn tương thích và không bị ảnh hưởng.
- **Kết quả kiểm thử lại**: Lệnh `composer audit` trả về: `No security vulnerability advisories found.` (Mã thoát 0). Toàn bộ 768 backend tests PASS.
- **Trạng thái**: **RESOLVED / VERIFIED**

---

### 2. `BUG-SEC-02` — Lỗ hổng Denial of Service trong thư viện Zod (<4.6.6)
- **Mức độ nghiêm trọng**: **P1 (High)**
- **Phân hệ bị ảnh hưởng**: Frontend dependencies (`ompramani-web`)
- **Mô tả**: Báo cáo `npm audit` ghi nhận CVE liên quan đến việc Zod bị cạn kiệt bộ nhớ khi xử lý constrained collections ngoại cỡ.
- **Giải pháp**: Thực hiện `npm audit fix` nâng cấp an toàn `zod` từ `4.6.5` lên `4.6.7`.
- **Kết quả kiểm thử lại**: Lỗ hổng Zod đã được vá triệt để. Quá trình biên dịch TypeScript `npx tsc --noEmit` và `npm run build` không gặp lỗi.
- **Trạng thái**: **RESOLVED / VERIFIED**

---

### 3. `BUG-FE-01` — Lỗi cú pháp Fragment JSX trong trang Video Phật Pháp
- **Mức độ nghiêm trọng**: **P2 (Medium)**
- **File bị ảnh hưởng**: `src/app/video-phat-phap/[slug]/page.tsx`
- **Mô tả**: Thẻ fragment `<>` được mở ở đầu khối render nhưng thiếu thẻ đóng `</>` ở cuối component gây ra lỗi parser: `Parsing error: Unexpected token. Did you mean {'}'} or &rbrace;?`.
- **Giải pháp**: Bổ sung thẻ đóng `</>` trước khi kết thúc hàm trả về của component.
- **Kết quả kiểm thử lại**: `npx eslint` và `npm run build` biên dịch trang chi tiết video thành công không lỗi.
- **Trạng thái**: **RESOLVED / VERIFIED**

---

### 4. `BUG-FE-02` — Lỗi truy cập thuộc tính sai kiểu trong Schema Product
- **Mức độ nghiêm trọng**: **P2 (Medium)**
- **File bị ảnh hưởng**: `src/app/cua-hang/[slug]/page.tsx`
- **Mô tả**: TypeScript báo lỗi `TS2339: Property 'stock' does not exist on type 'ProductDetail'`.
- **Nguyên nhân gốc rễ**: Interface `ProductDetail` trong `src/types/shop.ts` sử dụng thuộc tính `is_available: boolean` và `stock_quantity: number`, không có thuộc tính `stock`.
- **Giải pháp**: Cập nhật logic xác định trạng thái sẵn hàng schema sang `product.is_available ? "InStock" : "OutOfStock"`.
- **Kết quả kiểm thử lại**: TypeScript biên dịch sạch sẽ 100%, không còn lỗi kiểu dữ liệu.
- **Trạng thái**: **RESOLVED / VERIFIED**

---

### 5. `BUG-FE-03` — Lỗi tên thuộc tính ảnh đại diện và phiên bản mặc định của Kinh điển
- **Mức độ nghiêm trọng**: **P2 (Medium)**
- **File bị ảnh hưởng**: `src/app/kinh/[slug]/page.tsx` và `src/app/sitemap.ts`
- **Mô tả**: TypeScript báo lỗi `TS2339` đối với `sutra.cover_image`, `sutra.default_version_id` và `sutra.published_at`.
- **Nguyên nhân gốc rễ**: Mô hình `Sutra` chuẩn trong backend và frontend sử dụng thuộc tính `thumbnail`, và phiên bản mặc định được lấy từ quan hệ `versions.find(v => v.is_default)`.
- **Giải pháp**: Chuẩn hóa tên trường sang `sutra.thumbnail` và tìm kiếm `activeVersionId` an toàn qua mảng `versions`.
- **Kết quả kiểm thử lại**: Quá trình build `npm run build` prerender thành công toàn bộ 50 routes.
- **Trạng thái**: **RESOLVED / VERIFIED**

---

## TỔNG KẾT BẢNG LỖI:
- **Tổng số lỗi phát hiện**: 5 lỗi.
- **Tổng số lỗi đã sửa thành công**: 5 / 5 lỗi (100%).
- **Lỗi P0 còn lại**: **0**
- **Lỗi P1 còn lại**: **0**
- **Lỗi P2 còn lại**: **0**
- **Lỗi P3 còn lại**: **0**
