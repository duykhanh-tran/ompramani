# SƠ ĐỒ TUẦN TỰ: HOÀN THÀNH PHIÊN TU TẬP & CẬP NHẬT CHUỖI STREAK (M06.07, M10.04)

```mermaid
sequenceDiagram
    autonumber
    actor HanhGia as Hành giả tu tập
    participant FE as Màn hình Tu tập
    participant Modal as InstrumentCompletionModal.tsx
    participant Hook as usePracticeSession.ts
    participant ClientAPI as practice-sessions.ts
    participant Ctrl as PracticeSessionController
    participant Svc as PracticeSessionService
    participant StreakSvc as PracticeStreakService
    participant DB as PostgreSQL (practice_sessions, user_profiles)

    HanhGia->>FE: Bấm "Hoàn thành thời khóa"
    FE->>Modal: Mở hộp thoại xác nhận kết thúc & đúc kết công đức
    HanhGia->>Modal: Bấm "Xác nhận hoàn thành"
    Modal->>Hook: complete(finalProgressData)
    Hook->>ClientAPI: completePracticeSession(sessionId, payload)
    ClientAPI->>Ctrl: POST /api/v1/me/practice-sessions/{id}/complete
    Ctrl->>Svc: completeSession($sessionId, $user, $progressData)

    Svc->>DB: DB::transaction + lockForUpdate()
    Svc->>DB: Cập nhật status = 'completed', ended_at = now()
    Svc->>Svc: Bắn sự kiện PracticeSessionCompleted(Session)
    
    Note over Svc, StreakSvc: Tự động tính toán lại Chuỗi ngày tu tập (Streak)
    Svc->>StreakSvc: calculateCurrentStreak($user)
    StreakSvc->>StreakSvc: resolveTimezone($user) (Mặc định 'Asia/Ho_Chi_Minh')
    StreakSvc->>DB: Truy vấn các phiên completed trong 30 ngày qua
    DB-->>StreakSvc: Danh sách các ngày hoàn thành tu tập
    StreakSvc->>StreakSvc: Đánh giá tiêu chuẩn tính chuỗi (Tối thiểu 1 phiên >= 5 phút hoặc hoàn thành mục tiêu)
    StreakSvc-->>Svc: Kết quả: { current_streak, longest_streak, is_practiced_today: true }
    
    DB-->>Svc: Transaction Commit
    Svc-->>Ctrl: Updated Session Model kèm Streak Data
    Ctrl-->>ClientAPI: HTTP 200 OK { data: PracticeSessionResource }
    ClientAPI-->>Hook: Session đã đóng
    Hook-->>Modal: Dữ liệu tổng kết buổi tu (Thời lượng, số lần gõ, chuỗi ngày)
    Modal-->>HanhGia: Hiển thị bảng chúc mừng hoan hỷ & Chuỗi ngày tu tập tăng lên
```

---

### Thuyết minh nghiệp vụ

**Tên chức năng:** Hoàn thành phiên tu tập và tính toán chuỗi ngày tu tập (Streak).

**Mã chức năng:** M06.07, M10.04

**Mục đích:** Đóng phiên tu tập, ghi nhận thành tựu công phu và tính toán số ngày tu tập liên tục (Streak) theo múi giờ của người dùng để khích lệ tinh thần tinh tấn.

**Tác nhân:** Hành giả đang có phiên tu tập đang mở.

**Điều kiện tiên quyết:** Phiên tu tập đang ở trạng thái `active` hoặc `paused`.

**Luồng xử lý chính:**
1. Hành giả bấm hoàn thành trên thanh công cụ thời khóa, hộp thoại `InstrumentCompletionModal.tsx` mở ra.
2. Xác nhận gửi request `POST /api/v1/me/practice-sessions/{id}/complete`.
3. `PracticeSessionService::completeSession` chạy trong `DB::transaction` với `lockForUpdate()`.
4. Ghi nhận thời điểm kết thúc `ended_at = now()`, trạng thái `completed`, lưu lần cuối dữ liệu `progress_data`.
5. Bắn sự kiện nội bộ `PracticeSessionCompleted`.
6. Gọi `PracticeStreakService::calculateCurrentStreak` để tính toán chuỗi ngày:
   - Xác định múi giờ người dùng từ `user_profiles.timezone` (nếu không có thì dùng `Asia/Ho_Chi_Minh`).
   - Kiểm tra chính sách đủ điều kiện (`PracticeStreakEligibilityPolicy`): thời gian hành trì tối thiểu đạt yêu cầu.
   - Quét ngược từ ngày hôm nay về quá khứ để xác định số ngày liên tiếp có thực hành tu tập.
7. Trả về kết quả phiên và thông tin chuỗi ngày mới.
8. Giao diện hiển thị bảng đúc kết thời khóa tu học trang nghiêm.

**Luồng ngoại lệ:**
- Phiên đã bị hủy hoặc hoàn thành trước đó: Trả về lỗi 422, đóng phiên trên giao diện.

**Kết quả:** Phiên tu tập chuyển trạng thái `completed`, chuỗi tu tập (`current_streak`) được cập nhật trên dashboard Hành trình tu học.

**Các file source code liên quan:**
- Frontend: `src/components/instruments/InstrumentCompletionModal.tsx`, `src/hooks/usePracticeSession.ts`, `src/lib/api/practice-sessions.ts`.
- Backend: `app/Services/Practice/PracticeSessionService.php` (hàm `completeSession`), `app/Services/Practice/PracticeStreakService.php`, `app/Services/Practice/PracticeStreakEligibilityPolicy.php`, `app/Http/Controllers/Api/V1/PracticeSessionController.php`.
