# SƠ ĐỒ TUẦN TỰ: TỰ ĐỘNG LƯU TIẾN TRÌNH ĐỌC KINH (M02.06)

```mermaid
sequenceDiagram
    autonumber
    actor NguoiDung as Người dùng
    participant Reader as SutraReaderArea.tsx
    participant Timer as Debounce Timer (1.5s)
    participant ClientAPI as reading-progress.ts
    participant Ctrl as SutraReadingProgressController
    participant DB as PostgreSQL (sutra_reading_progress)

    NguoiDung->>Reader: Đang đọc kinh, chuyển sang phẩm/phần mới
    Reader->>Reader: Tăng currentRevisionRef.current += 1
    Reader->>Timer: Đặt hẹn giờ Debounce 1500ms (Hủy hẹn giờ cũ nếu có)

    Note over Reader, Timer: Người dùng dừng đọc hoặc chuyển phẩm ổn định
    Timer->>ClientAPI: saveReadingProgress(sutraSlug, { section_id, version_id, revision })
    ClientAPI->>Ctrl: PUT /api/v1/me/sutras/{slug}/reading-progress (kèm auth)
    
    Ctrl->>DB: Tìm bản ghi tiến trình hiện tại (user_id, sutra_id)
    DB-->>Ctrl: Bản ghi cũ (hoặc null nếu đọc lần đầu)

    alt Revision gửi lên nhỏ hơn Revision đã lưu trong DB (Stale update)
        Ctrl-->>ClientAPI: HTTP 409 Conflict hoặc bỏ qua ghi đè vị trí cũ
        ClientAPI-->>Reader: Giữ nguyên trạng thái mới nhất từ server
    else Revision hợp lệ (Mới hơn hoặc bằng)
        Ctrl->>DB: updateOrCreate(user_id, sutra_id, { sutra_section_id, revision, last_read_at: now() })
        DB-->>Ctrl: Bản ghi tiến trình đọc cập nhật thành công
        Ctrl-->>ClientAPI: HTTP 200 OK { data: SutraReadingProgressResource }
        ClientAPI-->>Reader: Trả về trạng thái "Đã lưu tiến trình"
        Reader-->>NguoiDung: Hiển thị biểu tượng đánh dấu xanh (Đã lưu)
    end
```

---

### Thuyết minh nghiệp vụ

**Tên chức năng:** Tự động lưu tiến trình đọc kinh (Reading Progress Auto-Save).

**Mã chức năng:** M02.06

**Mục đích:** Ghi nhớ vị trí phẩm kinh mà Phật tử đang đọc dở để khi mở lại kinh hoặc vào Tủ kinh cá nhân có thể tiếp tục hành trì ngay lập tức.

**Tác nhân:** Người dùng đã đăng nhập.

**Điều kiện tiên quyết:** Người dùng có tài khoản và đang đọc một phẩm kinh cụ thể.

**Luồng xử lý chính:**
1. Khi người dùng chuyển phẩm hoặc cuộn đọc, `SutraReaderArea.tsx` kích hoạt hàm theo dõi.
2. Quản lý `currentRevisionRef` tăng dần theo từng lần tương tác để kiểm soát thứ tự phiên.
3. Kích hoạt cơ chế Debounce 1.5 giây để tránh gửi dồn dập request lên máy chủ khi người dùng lướt nhanh mục lục.
4. Hết thời gian debounce, gọi `saveReadingProgress()` gửi `PUT /api/v1/me/sutras/{slug}/reading-progress`.
5. `SutraReadingProgressController::upsert` kiểm tra khóa lạc quan (Optimistic Concurrency Control) dựa vào `revision` để đảm bảo nếu người dùng mở nhiều tab, tab đọc sau không bị tab đọc trước ghi đè lùi vị trí.
6. Cập nhật `sutra_section_id`, `last_read_at = now()`, `revision` vào bảng `sutra_reading_progress`.
7. Trả về mã HTTP 200 và giao diện hiển thị thông báo tiến trình đã được đồng bộ.

**Luồng ngoại lệ:**
- Nếu chưa đăng nhập: Trả về 401 Unauthorized, client im lặng bỏ qua việc lưu lên server mà không làm gián đoạn việc đọc của độc giả.

**Kết quả:** Vị trí đọc kinh được lưu trữ bền vững trong cơ sở dữ liệu.

**Các file source code liên quan:**
- Frontend: `src/components/sutra/SutraReaderArea.tsx` (dòng 40-80), `src/lib/api/reading-progress.ts`.
- Backend: `app/Http/Controllers/Api/V1/SutraReadingProgressController.php` (hàm `upsert`), `app/Models/SutraReadingProgress.php`.
