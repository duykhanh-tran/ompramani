# SƠ ĐỒ TUẦN TỰ: LẬP LỊCH NHẮC NHỞ & PHÂN PHÁT TỰ ĐỘNG (M09.01, M09.03, M09.04)

```mermaid
sequenceDiagram
    autonumber
    actor NguoiDung as Người dùng
    participant FE as Màn hình Nhắc nhở (/tai-khoan/nhac-nho-tu-tap)
    participant ClientAPI as practice-reminders.ts
    participant Ctrl as PracticeReminderController
    participant SchedSvc as PracticeReminderScheduleService
    participant DB as PostgreSQL (practice_reminders, notifications)
    participant Cron as Linux Cron / Systemd Timer
    participant Cmd as DispatchDuePracticeRemindersCommand
    participant DispatchSvc as PracticeReminderDispatchService

    NguoiDung->>FE: Tạo lời nhắc mới: "Thời khóa niệm Phật 19:30 hàng ngày"
    FE->>ClientAPI: createPracticeReminder({ practice_type, local_time: "19:30", weekdays: [...] })
    ClientAPI->>Ctrl: POST /api/v1/me/practice-reminders
    Ctrl->>SchedSvc: computeNextDueAtUtc(localTime, timezone, weekdays)
    SchedSvc-->>Ctrl: next_due_at_utc = '2026-11-23 12:30:00'
    Ctrl->>DB: PracticeReminder::create([..., next_due_at_utc, is_enabled=true])
    DB-->>Ctrl: Lời nhắc đã lưu
    Ctrl-->>FE: HTTP 201 Created

    Note over Cron, DispatchSvc: Tiến trình nền chạy tự động mỗi phút qua Systemd Timer
    Cron->>Cmd: php artisan practice-reminders:dispatch-due
    Cmd->>DispatchSvc: dispatchDueReminders()
    DispatchSvc->>DB: Truy vấn practice_reminders (where is_enabled = true and next_due_at_utc <= now())
    DB-->>DispatchSvc: Danh sách các lời nhắc đã đến hạn

    loop Với từng lời nhắc đến hạn
        DispatchSvc->>DB: Kiểm tra UserNotificationPreference (Quiet Hours)
        alt Rơi vào khung giờ tĩnh lặng (Quiet Hours)
            DispatchSvc->>DB: Ghi log delivery: status = 'skipped_quiet_hours'
        else Hợp lệ
            DispatchSvc->>DB: Tạo bản ghi notifications (Database / Push Notification)
            DispatchSvc->>DB: Ghi log practice_reminder_deliveries (status = 'sent')
        end
        DispatchSvc->>SchedSvc: Tính next_due_at_utc cho chu kỳ tiếp theo
        SchedSvc-->>DispatchSvc: Thời điểm tiếp theo
        DispatchSvc->>DB: Cập nhật reminder: next_due_at_utc mới, last_scheduled_at_utc = now()
    end
```

---

### Thuyết minh nghiệp vụ

**Tên chức năng:** Lập lịch nhắc nhở tu tập và phân phát tự động qua tiến trình nền.

**Mã chức năng:** M09.01, M09.03, M09.04

**Mục đích:** Giúp Phật tử duy trì công phu đều đặn bằng cách nhắc nhở đúng giờ; hệ thống nền quét định kỳ để gửi thông báo mà không làm chậm trải nghiệm người dùng.

**Tác nhân:** Người dùng (Lập lịch) và Hệ thống tự động (Cron Scheduler phân phát).

**Điều kiện tiên quyết:** Người dùng kích hoạt lời nhắc và cấu hình múi giờ phù hợp.

**Luồng xử lý chính:**
1. Người dùng cài đặt giờ nhắc nhở (ví dụ 19:30) và các ngày trong tuần tại `/tai-khoan/nhac-nho-tu-tap`.
2. `PracticeReminderScheduleService` quy đổi giờ địa phương (`local_time`) và múi giờ (`timezone`) của người dùng sang giờ UTC chuẩn (`next_due_at_utc`).
3. Lưu vào cơ sở dữ liệu với trạng thái `is_enabled = true`.
4. Trên máy chủ, Systemd Timer kích hoạt lệnh `practice-reminders:dispatch-due` mỗi 1 phút một lần theo cấu hình tại [routes/console.php](file:///f:/Ompramani/ompramani-api/routes/console.php).
5. `PracticeReminderDispatchService` truy vấn các lời nhắc có `next_due_at_utc <= now()`.
6. Kiểm tra cài đặt Giờ tĩnh lặng (`Quiet Hours`): Nếu người dùng đang trong thời gian nghỉ ngơi, hệ thống ghi nhận trạng thái bỏ qua `skipped_quiet_hours`.
7. Nếu hợp lệ, bắn thông báo `PrayerCommunityNotification` vào bảng `notifications`.
8. Tính toán lại thời điểm nhắc tiếp theo và cập nhật vào database.

**Luồng ngoại lệ:**
- Lời nhắc bị trễ quá 60 phút do máy chủ khởi động lại: Hệ thống ghi log `skipped_late` và nhảy thẳng đến chu kỳ tiếp theo để tránh làm phiền người dùng.

**Kết quả:** Lời nhắc được gửi đúng giờ, lịch trình tiếp theo được tự động gia hạn.

**Các file source code liên quan:**
- Backend: `routes/console.php`, `app/Console/Commands/DispatchDuePracticeRemindersCommand.php`, `app/Services/Practice/PracticeReminderDispatchService.php`, `app/Services/Practice/PracticeReminderScheduleService.php`, `app/Models/PracticeReminder.php`, `app/Models/PracticeReminderDelivery.php`.
