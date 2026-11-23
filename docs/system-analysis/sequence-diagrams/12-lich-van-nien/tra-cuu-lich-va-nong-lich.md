# SƠ ĐỒ TUẦN TỰ: TRA CỨU LỊCH VẠN NIÊN & NÔNG LỊCH TRUYỀN THỐNG (M12.01, M12.04, M12.05)

```mermaid
sequenceDiagram
    autonumber
    actor NguoiXem as Người xem / Phật tử
    participant Page as Màn hình Lịch (/lich-van-nien)
    participant View as LichVanNienView.tsx
    participant Grid as CalendarMonthGrid.tsx
    participant Details as SelectedDayDetails.tsx
    participant ClientAPI as calendar.ts & almanac.ts
    participant CalCtrl as CalendarController
    participant EvtCtrl as CalendarEventController
    participant AlmCtrl as AlmanacController
    participant CalSvc as CalendarService
    participant Engine as VietnameseLunisolarCalendar
    participant AlmSvc as AlmanacService
    participant DB as PostgreSQL (calendar_events, almanac_methods)

    NguoiXem->>Page: Mở Lịch Vạn Niên (Mặc định: Tháng & Ngày hiện tại)
    Page->>View: Khởi tạo view LichVanNienView
    
    par Tải đồng thời 3 luồng dữ liệu tháng
        View->>ClientAPI: getCalendarMonth(year, month)
        ClientAPI->>CalCtrl: GET /api/v1/calendar/month?year=...&month=...
        CalCtrl->>CalSvc: getMonth($year, $month)
        CalSvc->>Engine: convertSolarToLunar(day, month, year, timezone_offset=7.0)
        Engine-->>CalSvc: Mảng ngày Âm - Dương, tháng nhuận, can chi ngày
        CalSvc-->>CalCtrl: Dữ liệu lưới tháng
        CalCtrl-->>View: HTTP 200 OK (CalendarMonthData)
    and
        View->>ClientAPI: getCalendarMonthEvents(year, month)
        ClientAPI->>EvtCtrl: GET /api/v1/calendar/events/month
        EvtCtrl->>DB: Lấy ngày vía Phật giáo, ngày Rằm, Mùng 1 trong tháng
        DB-->>EvtCtrl: Danh sách sự kiện lễ hội
        EvtCtrl-->>View: HTTP 200 OK (EventsMonthData)
    and
        View->>ClientAPI: getAlmanacMonth(year, month)
        ClientAPI->>AlmCtrl: GET /api/v1/calendar/almanac/month
        AlmCtrl->>AlmSvc: getMonthAlmanac($year, $month)
        AlmSvc-->>AlmCtrl: Mảng ngày Hoàng Đạo, Hắc Đạo, Trực, Tú
        AlmCtrl-->>View: HTTP 200 OK (AlmanacMonthData)
    end

    View->>Grid: Ghép dữ liệu và vẽ Lưới lịch tháng (Hiển thị ngày Dương, ngày Âm, chấm sự kiện)

    NguoiXem->>Grid: Nhấp chọn một ngày cụ thể trên lưới (e.g. Ngày 15 Âm lịch)
    Grid->>Details: Kích hoạt SelectedDayDetails(selectedDate)
    Details->>Details: Tính toán Khổng Minh Lục Diệu trực tiếp qua khong-minh.ts
    Details-->>NguoiXem: Hiển thị đầy đủ: Can Chi, Giờ Hoàng Đạo, Lễ vía, Lục Diệu, Việc nên làm/kiêng kỵ
```

---

### Thuyết minh nghiệp vụ

**Tên chức năng:** Tra cứu Lịch Vạn Niên Âm - Dương và Nông lịch truyền thống.

**Mã chức năng:** M12.01, M12.04, M12.05, M12.06

**Mục đích:** Cung cấp công cụ tra cứu lịch Âm - Dương chính xác theo múi giờ Việt Nam (GMT+7), thông tin ngày vía chư Phật, Bồ Tát, ngày Sóc Vọng (Mùng một, Rằm) và thông tin cát hung, trực, tú theo nông lịch cổ truyền.

**Tác nhân:** Mọi đối tượng người dùng.

**Điều kiện tiên quyết:** Không yêu cầu đăng nhập.

**Luồng xử lý chính:**
1. Người dùng truy cập `/lich-van-nien`.
2. `LichVanNienView.tsx` kích hoạt 3 lời gọi API song song:
   - `GET /calendar/month`: Tính toán lịch Âm - Dương toàn bộ tháng bằng thuật toán thiên văn học chính xác `VietnameseLunisolarCalendar` (thuật toán tính sóc, tiết khí, tháng nhuận theo kinh tuyến 105° Đông).
   - `GET /calendar/events/month`: Truy vấn danh sách ngày lễ, ngày vía Phật, Bồ Tát từ bảng `calendar_events`.
   - `GET /calendar/almanac/month`: Tính toán Can Chi của năm/tháng/ngày/giờ, xác định ngày Hoàng Đạo/Hắc Đạo thông qua `ThanhLongAlmanacProvider`.
3. Giao diện hợp nhất 3 luồng dữ liệu hiển thị lên lưới lịch tháng `CalendarMonthGrid.tsx`.
4. Khi người dùng nhấp vào một ngày cụ thể:
   - Panel chi tiết `SelectedDayDetails.tsx` hiển thị thông tin chi tiết của ngày đó.
   - Hàm tiện ích phía client `khong-minh.ts` tự động tính toán vị trí Lục Diệu (Tốc Hỷ, Lưu Niên, Xích Khẩu, Tiểu Cát, Không Vong, Đại An).

**Luồng ngoại lệ:**
- Ngày tháng ngoài phạm vi hỗ trợ (1900 - 2100): Hệ thống trả về lỗi 422 kèm giới hạn niên đại được phép tra cứu.

**Kết quả:** Toàn bộ thông tin lịch pháp Phật giáo và nông lịch truyền thống được hiển thị chi tiết, chuẩn xác.

**Các file source code liên quan:**
- Frontend: `src/app/lich-van-nien/page.tsx`, `src/components/calendar/LichVanNienView.tsx`, `src/components/calendar/CalendarMonthGrid.tsx`, `src/components/calendar/SelectedDayDetails.tsx`, `src/lib/utils/khong-minh.ts`.
- Backend: `app/Services/Calendar/CalendarService.php`, `app/Services/Calendar/VietnameseLunisolarCalendar.php`, `app/Services/Almanac/AlmanacService.php`, `app/Services/Almanac/Providers/ThanhLongAlmanacProvider.php`, `app/Http/Controllers/Api/V1/CalendarController.php`.
