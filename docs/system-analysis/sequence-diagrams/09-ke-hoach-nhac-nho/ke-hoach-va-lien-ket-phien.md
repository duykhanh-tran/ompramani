# SƠ ĐỒ TUẦN TỰ: KẾ HOẠCH TU TẬP & LIÊN KẾT PHIÊN THỰC TẾ (M09.05, M09.07, M09.08)

```mermaid
sequenceDiagram
    autonumber
    actor NguoiDung as Người dùng
    participant Page as Kế Hoạch Tu Tập (/tai-khoan/ke-hoach-tu-tap)
    participant Grid as PracticeWeekGrid.tsx
    participant Modal as PracticeSessionLinkDialog.tsx
    participant ClientAPI as practice-plans.ts
    participant Ctrl as PracticePlanOccurrenceController
    participant LinkSvc as PracticePlanSessionLinkService
    participant DB as PostgreSQL (practice_plans, practice_plan_items, practice_plan_occurrence_sessions)

    NguoiDung->>Page: Mở kế hoạch tuần hiện tại
    Page->>ClientAPI: getPlannerWeek({ date: '2026-11-23' })
    ClientAPI->>Ctrl: GET /api/v1/me/practice-planner/week
    Ctrl->>DB: Nạp PracticePlan active, Items và các Session đã liên kết
    DB-->>Ctrl: Danh sách thời khóa và trạng thái hoàn thành (Fulfilled / Pending)
    Ctrl-->>ClientAPI: HTTP 200 OK (PlannerWeekData)
    ClientAPI-->>Grid: Render lưới tuần 7 ngày

    NguoiDung->>Grid: Chọn một buổi tu chưa hoàn thành & Bấm "Liên kết phiên"
    Grid->>Modal: Mở PracticeSessionLinkDialog
    Modal->>ClientAPI: getCandidateSessions({ item_id, date })
    ClientAPI->>Ctrl: GET /api/v1/me/practice-plan-occurrences/candidate-sessions
    Ctrl->>DB: Lấy danh sách PracticeSession completed cùng loại hình tu tập
    DB-->>Ctrl: Các phiên tu tập khả dụng
    Ctrl-->>Modal: Danh sách ứng viên

    NguoiDung->>Modal: Chọn một phiên tu tập phù hợp & Bấm "Xác nhận liên kết"
    Modal->>ClientAPI: linkSession({ plan_item_id, practice_session_id, occurrence_date })
    ClientAPI->>Ctrl: POST /api/v1/me/practice-plan-occurrences/link-session
    Ctrl->>LinkSvc: linkSessionToOccurrence()
    LinkSvc->>DB: DB::transaction -> Ghi vào practice_plan_occurrence_sessions
    DB-->>LinkSvc: Liên kết thành công
    LinkSvc-->>Ctrl: Trả về trạng thái Fulfilled
    Ctrl-->>ClientAPI: HTTP 200 OK
    ClientAPI-->>Grid: Cập nhật ô lịch tuần thành dấu tích xanh "Đã hoàn thành"
```

---

### Thuyết minh nghiệp vụ

**Tên chức năng:** Lập kế hoạch tu tập tuần và liên kết phiên hành trì thực tế.

**Mã chức năng:** M09.05, M09.07, M09.08

**Mục đích:** Giúp người tu học đặt ra mục tiêu tu tập trong tuần và đối chiếu xem buổi thực hành thực tế có hoàn thành đúng thời khóa đã đề ra hay không.

**Tác nhân:** Người dùng đã đăng nhập.

**Điều kiện tiên quyết:** Người dùng đã tạo kế hoạch tu tập và có phiên tu tập hoàn thành.

**Luồng xử lý chính:**
1. Người dùng mở trang `/tai-khoan/ke-hoach-tu-tap`.
2. Hệ thống gọi `GET /api/v1/me/practice-planner/week` tải lịch tuần với từng buổi tu theo các trạng thái: `pending` (chưa tu), `fulfilled` (đã tu), `skipped` (bỏ qua).
3. Nếu người dùng tu tự do bên ngoài (ví dụ vào `/chuong-mo` gõ mõ xong), có thể bấm vào buổi tu trên kế hoạch để liên kết phiên đó vào lịch.
4. `PracticeSessionLinkDialog.tsx` gọi `getCandidateSessions` để lấy các phiên tu tập phù hợp về mặt pháp môn (`practice_type`) và thời gian.
5. Khi người dùng xác nhận, `PracticePlanSessionLinkService` lưu liên kết vào bảng `practice_plan_occurrence_sessions`.
6. Lưới thời khóa tuần chuyển sang màu xanh đánh dấu đã hoàn thành mục tiêu.

**Luồng ngoại lệ:**
- Phiên tu tập đã bị hủy hoặc không cùng loại hình: Hệ thống từ chối liên kết và báo lỗi 422.

**Kết quả:** Buổi tu trong kế hoạch được đối soát và ghi nhận hoàn thành.

**Các file source code liên quan:**
- Frontend: `src/components/practice-planner/PracticePlannerManager.tsx`, `src/components/practice-planner/PracticeWeekGrid.tsx`, `src/components/practice-planner/PracticeSessionLinkDialog.tsx`.
- Backend: `app/Http/Controllers/Api/V1/PracticePlanOccurrenceController.php`, `app/Services/Practice/PracticePlanSessionLinkService.php`, `app/Models/PracticePlan.php`.
