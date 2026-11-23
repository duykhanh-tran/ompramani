# SƠ ĐỒ TUẦN TỰ: THỰC HÀNH THIỀN ĐỊNH ONLINE & GUEST MODE (M07.01, M07.02, M07.03)

```mermaid
sequenceDiagram
    autonumber
    actor ThienSinh as Thiền sinh / Người dùng
    participant Prep as MeditationPreparation.tsx
    participant Client as MeditationClient.tsx
    participant AudioUtil as bellAudio.ts (Web Audio)
    participant Hook as usePracticeSession.ts
    participant API as PracticeSessionController
    participant DB as PostgreSQL (practice_sessions)

    ThienSinh->>Prep: Chọn thời lượng thiền (e.g. 15, 30, 45 phút) & Bật chuông
    ThienSinh->>Prep: Bấm "Bắt đầu tọa thiền"
    Prep->>Client: Kích hoạt tọa thiền

    alt Trường hợp 1: Chế độ Khách (Guest Mode - Chưa đăng nhập)
        Client->>Client: setGuestActive(true), setGuestElapsedSeconds(0)
        Client->>AudioUtil: playSingingBowl() (Thỉnh 3 tiếng chuông bát mở đầu)
        AudioUtil-->>ThienSinh: Âm thanh chuông xoay ngân vang (Web Audio API)
        
        loop Mỗi 1 giây (Local Timer Progression)
            Client->>Client: setGuestElapsedSeconds(prev + 1)
        end

        ThienSinh->>Client: Hết giờ hoặc bấm "Xả thiền"
        Client->>AudioUtil: playSingingBowl() (Thỉnh chuông kết thúc)
        Client-->>ThienSinh: Hiển thị bảng tổng kết thiền định tại chỗ
    else Trường hợp 2: Chế độ Đã Đăng Nhập (Online Practice Session)
        Client->>Hook: start({ practice_type: 'meditation', planned_duration: 1800 })
        Hook->>API: POST /api/v1/me/practice-sessions
        API->>DB: Khởi tạo PracticeSession (practice_type = 'meditation')
        DB-->>API: Session ID mới
        API-->>Hook: Session Created
        Client->>AudioUtil: playSingingBowl() (Chuông khai thời)
        
        loop Định kỳ 30s
            Hook->>API: POST /practice-sessions/{id}/heartbeat
            API->>DB: Tích lũy active_duration_seconds
        end

        ThienSinh->>Client: Bấm "Hoàn thành xả thiền"
        Client->>Hook: complete()
        Hook->>API: POST /practice-sessions/{id}/complete
        API->>DB: status = 'completed' & Tính toán Streak
        DB-->>Client: Hoàn tất thời khóa thiền định
        Client-->>ThienSinh: Hiển thị chứng nhận thời khóa thiền định và chuỗi ngày
    end
```

---

### Thuyết minh nghiệp vụ

**Tên chức năng:** Thực hành thiền định (Meditation Session) hỗ trợ chế độ Khách và Tài khoản.

**Mã chức năng:** M07.01, M07.02, M07.03

**Mục đích:** Cung cấp không gian tọa thiền tĩnh lặng với âm thanh chuông xoay Tây Tạng (Tibetan singing bowl) được mô phỏng chân thực bằng Web Audio API, đồng thời lưu trữ lịch sử thiền vào hồ sơ cá nhân.

**Tác nhân:** Bất kỳ ai muốn thực hành thiền định (Khách vãng lai hoặc Thành viên).

**Điều kiện tiên quyết:** Thiết bị hỗ trợ trình duyệt web và âm thanh.

**Luồng xử lý chính:**
1. Thiền sinh truy cập `/thien`, chọn thời gian tọa thiền (15 phút, 30 phút, 45 phút hoặc tùy chỉnh).
2. Khi bấm bắt đầu:
   - **Nếu là Khách (Chưa đăng nhập):** Hệ thống chạy bộ đếm thời gian hoàn toàn phía Client (`guestElapsedSeconds`), không đòi hỏi đăng nhập hay kết nối mạng, phát chuông bát bằng Web Audio API.
   - **Nếu đã đăng nhập:** Hệ thống kích hoạt hook `usePracticeSession.ts` gửi yêu cầu lên server khởi tạo phiên `meditation`, định kỳ gửi heartbeat tích lũy phút thiền định.
3. Khi hết thời gian hoặc bấm xả thiền, chuông kết thúc vang lên và hệ thống tổng kết thời lượng tọa thiền.

**Luồng ngoại lệ:**
- Nếu đang có phiên thiền định hoặc chuông mõ khác chưa đóng: Hệ thống hiển thị `MeditationConflictModal` để thiền sinh quyết định xử lý phiên cũ trước khi vào thiền mới.

**Kết quả:** Thời khóa thiền định được hoàn thành thanh tịnh, số phút thiền được ghi nhận vào Hành trình tu học nếu có tài khoản.

**Các file source code liên quan:**
- Frontend: `src/app/thien/page.tsx`, `src/app/thien/MeditationClient.tsx`, `src/components/meditation/MeditationPreparation.tsx`, `src/components/meditation/MeditationActiveSession.tsx`, `src/lib/utils/bellAudio.ts`.
- Backend: Sử dụng chung hạ tầng API `PracticeSessionService` với `practice_type = 'meditation'`.
