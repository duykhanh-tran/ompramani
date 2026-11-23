# SƠ ĐỒ TUẦN TỰ: HÀNH TRÌNH TU HỌC & THỐNG KÊ TIẾN ĐỘ (M10.01, M10.02, M10.03)

```mermaid
sequenceDiagram
    autonumber
    actor NguoiDung as Người dùng
    participant Page as Hành Trình Tu Học (/tai-khoan/hanh-trinh)
    participant Dashboard as DharmaJourneyDashboard.tsx
    participant Chart as PracticeActivityChart.tsx
    participant ClientAPI as dharma-journey.ts
    participant Ctrl as DharmaJourneyController
    participant Svc as DharmaJourneyService
    participant DB as PostgreSQL (practice_sessions, sutra_reading_progress)

    NguoiDung->>Page: Truy cập Dashboard Hành Trình Tu Học
    Page->>Dashboard: Khởi tạo bảng điều khiển tổng quan
    
    par Tải đồng thời 3 nhóm dữ liệu thống kê
        Dashboard->>ClientAPI: getJourneySummary()
        ClientAPI->>Ctrl: GET /api/v1/me/dharma-journey/summary
        Ctrl->>Svc: getSummary($user)
        Svc->>DB: Tính tổng phút tu tập, tổng số buổi, số kinh đã đọc
        DB-->>Svc: Tổng kết số liệu
        Svc-->>Ctrl: Dữ liệu Summary
        Ctrl-->>Dashboard: HTTP 200 OK (Tổng phút, tổng ngày, chuỗi streak)
    and
        Dashboard->>ClientAPI: getJourneyActivity({ range: '30d' })
        ClientAPI->>Ctrl: GET /api/v1/me/dharma-journey/activity?range=30d
        Ctrl->>Svc: getActivityHeatmap($user, 30)
        Svc->>DB: Nhóm số phút tu tập theo từng ngày (DATE_TRUNC)
        DB-->>Svc: Mảng ngày và số phút
        Svc-->>Ctrl: Dữ liệu biểu đồ nhiệt
        Ctrl-->>Chart: HTTP 200 OK (Render cột biểu đồ hoạt động)
    and
        Dashboard->>ClientAPI: getJourneyBreakdown({ range: '30d' })
        ClientAPI->>Ctrl: GET /api/v1/me/dharma-journey/breakdown?range=30d
        Ctrl->>Svc: getBreakdownByPracticeType($user, 30)
        Svc->>DB: Phân rã thời gian: Thiền, Tụng kinh, Trì chú, Chuông mõ
        DB-->>Svc: Tỷ lệ phần trăm từng pháp môn
        Svc-->>Ctrl: Dữ liệu tỷ trọng
        Ctrl-->>Dashboard: HTTP 200 OK (Render biểu đồ phân bổ pháp môn)
    end

    Dashboard-->>NguoiDung: Trình bày giao diện trực quan hóa toàn bộ tiến trình tu học
```

---

### Thuyết minh nghiệp vụ

**Tên chức năng:** Theo dõi tổng quan hành trình tu học cá nhân (Dharma Journey Dashboard).

**Mã chức năng:** M10.01, M10.02, M10.03

**Mục đích:** Cung cấp cho Phật tử cái nhìn toàn cảnh về công phu tu tập cá nhân qua các con số thống kê chính xác, biểu đồ thời gian và sự cân bằng giữa các pháp môn.

**Tác nhân:** Người dùng đã đăng nhập.

**Điều kiện tiên quyết:** Người dùng có tài khoản trên hệ thống.

**Luồng xử lý chính:**
1. Khi mở `/tai-khoan/hanh-trinh`, trang web gửi song song 3 truy vấn API để tối ưu tốc độ tải:
   - `GET /summary`: Thống kê tổng số phút tu tập, số buổi hoàn thành, số kinh điển đã đọc và chuỗi ngày liên tục.
   - `GET /activity`: Trả về dữ liệu chuỗi thời gian (Time-series) trong 7 ngày, 30 ngày hoặc 90 ngày để vẽ biểu đồ cột/biểu đồ nhiệt.
   - `GET /breakdown`: Tổng hợp tỷ lệ thời gian phân bổ giữa các pháp môn: Thiền định (`meditation`), Tụng kinh (`sutra`), Trì chú (`mantra`), Chuông mõ (`bell_wooden_fish`).
2. `DharmaJourneyService` thực hiện các truy vấn gộp (Aggregate Query) và nhóm theo ngày theo múi giờ cá nhân của người dùng.
3. Giao diện Next.js hiển thị các thẻ tóm tắt (`JourneySummaryCards.tsx`), biểu đồ hoạt động (`PracticeActivityChart.tsx`) và biểu đồ tỷ trọng pháp môn (`PracticeTypeBreakdown.tsx`).

**Luồng ngoại lệ:**
- Người dùng mới chưa có buổi tu nào: Hệ thống trả về số liệu 0 kèm lời khuyên khởi đầu thời khóa đầu tiên.

**Kết quả:** Toàn bộ công đức và thời gian tu học được tổng kết minh bạch, khoa học.

**Các file source code liên quan:**
- Frontend: `src/app/tai-khoan/hanh-trinh/page.tsx`, `src/components/dharma-journey/DharmaJourneyDashboard.tsx`, `src/lib/api/dharma-journey.ts`.
- Backend: `app/Http/Controllers/Api/V1/DharmaJourneyController.php`, `app/Services/DharmaJourney/DharmaJourneyService.php`.
