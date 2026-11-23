# SƠ ĐỒ PHÂN RÃ CHỨC NĂNG CHI TIẾT (FDD CẤP 2 VÀ CẤP 3)
## (Detailed Functional Decomposition per Domain Group)

Tài liệu này chi tiết hóa từng phân hệ lớn thành các module và chức năng nghiệp vụ cụ thể (Cấp 3) kèm mã định danh chuẩn xác theo source code thực tế.

---

### 1. Phân hệ Quản lý Tài khoản & Bảo mật (M01)

```mermaid
flowchart TD
    classDef mod fill:#00695c,stroke:#004d40,stroke-width:1.5px,color:#ffffff;
    classDef func fill:#e0f2f1,stroke:#00796b,stroke-width:1px,color:#004d40;

    M01["M01. Xác thực & Hồ sơ cá nhân (IAM)"]:::mod

    subgraph SUB_AUTH["Xác thực danh tính (Sanctum SPA)"]
        M01_01["M01.01. Đăng ký tài khoản"]:::func
        M01_02["M01.02. Đăng nhập hệ thống"]:::func
        M01_03["M01.03. Đăng xuất phiên"]:::func
        M01_04["M01.04. Lấy thông tin user hiện tại (/auth/me)"]:::func
        M01_05["M01.05. Khởi tạo CSRF Cookie"]:::func
    end

    subgraph SUB_SEC["Bảo mật & Khôi phục tài khoản"]
        M01_06["M01.06. Yêu cầu đặt lại mật khẩu"]:::func
        M01_07["M01.07. Đặt lại mật khẩu mới"]:::func
        M01_08["M01.08. Xác thực Email qua link"]:::func
        M01_09["M01.09. Gửi lại email kích hoạt"]:::func
        M01_10["M01.10. Kiểm tra trạng thái verify email"]:::func
        M01_15["M01.15. Thay đổi mật khẩu trong cài đặt"]:::func
        M01_16["M01.16. Cập nhật địa chỉ email chính"]:::func
    end

    subgraph SUB_PROF["Quản lý Hồ sơ cá nhân"]
        M01_11["M01.11. Xem hồ sơ pháp danh, bio"]:::func
        M01_12["M01.12. Cập nhật thông tin hồ sơ"]:::func
        M01_13["M01.13. Tải lên avatar cá nhân (Storage)"]:::func
        M01_14["M01.14. Xóa ảnh đại diện"]:::func
    end

    M01 --> SUB_AUTH
    M01 --> SUB_SEC
    M01 --> SUB_PROF
```

---

### 2. Phân hệ Nội dung & Kho tàng Phật học (M02, M03, M04, M05, M14)

```mermaid
flowchart TD
    classDef mod fill:#e65100,stroke:#bf360c,stroke-width:1.5px,color:#ffffff;
    classDef func fill:#fff3e0,stroke:#e65100,stroke-width:1px,color:#bf360c;

    G2["PHÂN HỆ NỘI DUNG & KHO TÀNG PHẬT HỌC"]:::mod

    M02["M02. Kinh điển & Thư viện đọc"]:::mod
    M03["M03. Số hóa văn bản (Admin)"]:::mod
    M04["M04. Chư Phật & Bồ Tát"]:::mod
    M05["M05. Thần chú & Trì tụng"]:::mod
    M14["M14. Bài viết & Video"]:::mod

    G2 --> M02
    G2 --> M03
    G2 --> M04
    G2 --> M05
    G2 --> M14

    subgraph SUB_SUTRA["Chức năng Đọc Kinh (M02)"]
        M02_01["M02.01. Xem danh mục kinh & lọc tông phái"]:::func
        M02_02["M02.02. Xem chi tiết bộ kinh"]:::func
        M02_03["M02.03. Xem danh sách bản dịch/phiên bản"]:::func
        M02_04["M02.04. Xem mục lục các phẩm kinh"]:::func
        M02_05["M02.05. Đọc nội dung phẩm kinh (Đổi font)"]:::func
        M02_06["M02.06. Tự động lưu tiến trình đọc"]:::func
        M02_07["M02.07. Tải tiến trình đọc bộ kinh"]:::func
        M02_08["M02.08. Quản lý tủ kinh đang đọc"]:::func
        M02_09["M02.09. Tạo bookmark có ghi chú"]:::func
        M02_10["M02.10. Quản lý danh sách bookmark"]:::func
        M02_11["M02.11. Xóa bookmark theo ID / Target"]:::func
    end

    subgraph SUB_INGEST["Số hóa Kinh điển (M03)"]
        M03_01["M03.01. Upload DOCX/PDF & Tạo SourceDocument"]:::func
        M03_02["M03.02. Parser bóc tách cây đề mục tự động"]:::func
        M03_03["M03.03. Phê duyệt chuyển thành SutraVersion"]:::func
    end

    subgraph SUB_FIG_MANTRA["Phật - Bồ Tát & Thần Chú (M04, M05)"]
        M04_01["M04.01. Danh sách Chư Phật, Bồ Tát"]:::func
        M04_02["M04.02. Chi tiết tiểu sử, hình tượng"]:::func
        M04_03["M04.03. Tra cứu các Đại Nguyện"]:::func
        M04_04["M04.04. Kinh & Chú liên quan"]:::func
        M05_01["M05.01. Danh sách thần chú"]:::func
        M05_02["M05.02. Chi tiết nguyên văn & phiên âm"]:::func
        M05_03["M05.03. Trình phát âm thanh trì niệm lặp lại"]:::func
    end

    subgraph SUB_MEDIA["Bài viết & Video (M14)"]
        M14_01["M14.01. Danh mục & Đọc bài viết giáo lý"]:::func
        M14_02["M14.02. Danh mục & Phát video Phật pháp"]:::func
    end

    M02 --> SUB_SUTRA
    M03 --> SUB_INGEST
    M04 & M05 --> SUB_FIG_MANTRA
    M14 --> SUB_MEDIA
```

---

### 3. Phân hệ Công cụ Hành trì Tu tập (M06, M07, M08)

```mermaid
flowchart TD
    classDef mod fill:#1b5e20,stroke:#0d5302,stroke-width:1.5px,color:#ffffff;
    classDef func fill:#e8f5e9,stroke:#2e7d32,stroke-width:1px,color:#1b5e20;

    G3["PHÂN HỆ CÔNG CỤ HÀNH TRÌ TU TẬP"]:::mod

    M06["M06. Phiên tu tập & Chuông Mõ"]:::mod
    M07["M07. Thực hành Thiền định"]:::mod
    M08["M08. Tràng hạt số (108 hạt)"]:::mod

    G3 --> M06
    G3 --> M07
    G3 --> M08

    subgraph SUB_SESSION["Quản lý Phiên tu tập (Backend Engine)"]
        M06_01["M06.01. Khởi tạo phiên tu tập (Row Lock)"]:::func
        M06_02["M06.02. Lấy phiên tu tập đang active"]:::func
        M06_03["M06.03. Tạm dừng phiên tu tập"]:::func
        M06_04["M06.04. Tiếp tục phiên tu tập"]:::func
        M06_05["M06.05. Heartbeat định kỳ (Kẹp <= 120s)"]:::func
        M06_06["M06.06. Cập nhật số lần gõ chuông/mõ"]:::func
        M06_07["M06.07. Hoàn thành phiên & tính Streak"]:::func
        M06_08["M06.08. Hủy bỏ phiên tu tập"]:::func
        M06_10["M06.10. Tự động hủy phiên stale qua Cron"]:::func
    end

    subgraph SUB_TOOLS["Tương tác âm thanh & Thiết bị hỗ trợ"]
        M06_09["M06.09. Web Audio API mô phỏng Chuông & Mõ"]:::func
        M07_01["M07.01. Cấu hình thời gian thiền & chuông xoay"]:::func
        M07_02["M07.02. Chế độ Thiền khách (Guest Mode Offline)"]:::func
        M07_03["M07.03. Thiền định đồng bộ phiên tu tập online"]:::func
        M08_01["M08.01. Lần hạt 108 hạt phím Space / Enter / Chạm"]:::func
        M08_02["M08.02. Rung Haptic & Đặt lại vòng tràng hạt"]:::func
    end

    M06 --> SUB_SESSION
    M06 & M07 & M08 --> SUB_TOOLS
```

---

### 4. Phân hệ Kế hoạch & Theo dõi Tiến độ (M09, M10)

```mermaid
flowchart TD
    classDef mod fill:#01579b,stroke:#002f6c,stroke-width:1.5px,color:#ffffff;
    classDef func fill:#e1f5fe,stroke:#0277bd,stroke-width:1px,color:#01579b;

    G4["PHÂN HỆ KẾ HOẠCH & THEO DÕI TIẾN ĐỘ"]:::mod

    M09["M09. Kế hoạch & Nhắc nhở tu tập"]:::mod
    M10["M10. Hành trình tu học & Chuỗi ngày (Streak)"]:::mod

    G4 --> M09
    G4 --> M10

    subgraph SUB_REMIND["Nhắc nhở tu tập (Reminders)"]
        M09_01["M09.01. Tạo mới lời nhắc tu tập"]:::func
        M09_02["M09.02. Tạm dừng / Tiếp tục lời nhắc"]:::func
        M09_03["M09.03. Cấu hình Quiet Hours (Giờ tĩnh lặng)"]:::func
        M09_04["M09.04. Scheduler quét gửi lời nhắc mỗi phút"]:::func
    end

    subgraph SUB_PLAN["Kế hoạch tu tập (Practice Planner)"]
        M09_05["M09.05. Tạo kế hoạch thời khóa tu tập"]:::func
        M09_06["M09.06. Thêm mục thời khóa vào kế hoạch"]:::func
        M09_07["M09.07. Xem thời khóa hôm nay và tuần này"]:::func
        M09_08["M09.08. Liên kết phiên tu tập vào kế hoạch"]:::func
        M09_09["M09.09. Bỏ qua / Khôi phục buổi tu tập"]:::func
    end

    subgraph SUB_JOURNEY["Hành trình & Thống kê (Dharma Journey)"]
        M10_01["M10.01. Tổng quan hành trình tu học"]:::func
        M10_02["M10.02. Biểu đồ hoạt động tu học tuần/tháng"]:::func
        M10_03["M10.03. Phân bổ thời lượng theo pháp môn"]:::func
        M10_04["M10.04. Thuật toán tính Chuỗi ngày (Streak)"]:::func
    end

    M09 --> SUB_REMIND
    M09 --> SUB_PLAN
    M10 --> SUB_JOURNEY
```

---

### 5. Phân hệ Cộng đồng & Không gian Tâm linh (M11)

```mermaid
flowchart TD
    classDef mod fill:#ad1457,stroke:#6a0080,stroke-width:1.5px,color:#ffffff;
    classDef func fill:#fce4ec,stroke:#c2185b,stroke-width:1px,color:#880e4f;

    M11["M11. Lời nguyện & Phòng tâm linh trực tuyến"]:::mod

    subgraph SUB_PRAYER["Lời cầu nguyện cá nhân & Cộng đồng"]
        M11_01["M11.01. Xem danh sách lời nguyện cộng đồng đã duyệt"]:::func
        M11_02["M11.02. Tạo và gửi lời nguyện xin trợ duyên"]:::func
        M11_03["M11.03. Tham gia thắp nến đồng cầu nguyện"]:::func
        M11_04["M11.04. Gửi lời chúc, hồi hướng tâm linh"]:::func
        M11_05["M11.05. Báo cáo lời nguyện/phản hồi vi phạm"]:::func
    end

    subgraph SUB_ROOM["Phòng Cầu nguyện Trực tuyến (Unified Room)"]
        M11_06["M11.06. Tải tin nhắn phòng cầu nguyện (Polling)"]:::func
        M11_07["M11.07. Gửi tin nhắn vào phòng cầu nguyện chung"]:::func
    end

    subgraph SUB_MOD["Kiểm duyệt an toàn (Moderation)"]
        M11_08["M11.08. Duyệt / Từ chối lời nguyện trong Admin"]:::func
    end

    M11 --> SUB_PRAYER
    M11 --> SUB_ROOM
    M11 --> SUB_MOD
```

---

### 6. Phân hệ Lịch & Tra cứu Địa điểm (M12, M13, M15)

```mermaid
flowchart TD
    classDef mod fill:#37474f,stroke:#212121,stroke-width:1.5px,color:#ffffff;
    classDef func fill:#eceff1,stroke:#455a64,stroke-width:1px,color:#263238;

    G6["PHÂN HỆ LỊCH & TRA CỨU ĐỊA ĐIỂM"]:::mod

    M12["M12. Lịch Vạn Niên & Nông lịch"]:::mod
    M13["M13. Danh bạ Chùa & Tu viện"]:::mod
    M15["M15. Tìm kiếm toàn cục"]:::mod

    G6 --> M12
    G6 --> M13
    G6 --> M15

    subgraph SUB_CAL["Thuật toán Lịch & Nông lịch (M12)"]
        M12_01["M12.01. Chuyển đổi Dương sang Âm lịch Việt Nam"]:::func
        M12_02["M12.02. Chuyển đổi Âm sang Dương lịch"]:::func
        M12_03["M12.03. Lưới lịch tháng Dương - Âm"]:::func
        M12_04["M12.04. Sự kiện & Ngày lễ Phật giáo trong tháng"]:::func
        M12_05["M12.05. Tra cứu Can Chi, Hoàng Đạo, Trực, Tú"]:::func
        M12_06["M12.06. Tính toán Khổng Minh Lục Diệu (Client)"]:::func
    end

    subgraph SUB_TEMPLE["Danh bạ Chùa (M13)"]
        M13_01["M13.01. Xem danh sách Chùa & Bộ lọc đa tiêu chí"]:::func
        M13_02["M13.02. Chi tiết chùa & Tọa độ chỉ đường Google Maps"]:::func
    end

    subgraph SUB_SEARCH["Tìm kiếm (M15)"]
        M15_01["M15.01. Tìm kiếm toàn cục đa thực thể (GIN Index)"]:::func
        M15_02["M15.02. Gợi ý từ khóa tự động (Auto-suggestions)"]:::func
    end

    M12 --> SUB_CAL
    M13 --> SUB_TEMPLE
    M15 --> SUB_SEARCH
```

---

### 7. Phân hệ Thương mại Pháp bảo & Quản trị (M16, M17)

```mermaid
flowchart TD
    classDef mod fill:#880e4f,stroke:#4a0072,stroke-width:1.5px,color:#ffffff;
    classDef func fill:#fce4ec,stroke:#880e4f,stroke-width:1px,color:#4a0072;

    G7["PHÂN HỆ THƯƠNG MẠI PHÁP BẢO & QUẢN TRỊ"]:::mod

    M16["M16. Cửa hàng & Thanh toán SePay"]:::mod
    M17["M17. Quản trị Hệ thống Filament"]:::mod

    G7 --> M16
    G7 --> M17

    subgraph SUB_SHOP_USER["Mua hàng & Thanh toán (Frontend)"]
        M16_01["M16.01. Danh mục sản phẩm & Chặn mê tín"]:::func
        M16_02["M16.02. Giỏ hàng đồng bộ LocalStorage"]:::func
        M16_03["M16.03. Xem trước giỏ hàng (Check tồn kho & Giá)"]:::func
        M16_04["M16.04. Xem trước thanh toán & Phí giao hàng"]:::func
        M16_05["M16.05. Tạo đơn hàng (Idempotency & Khóa tồn kho)"]:::func
        M16_06["M16.06. Khởi tạo form thanh toán SePay VietQR"]:::func
        M16_08["M16.08. Polling trạng thái thanh toán từ Client"]:::func
        M16_09["M16.09. Chuyển đổi sang COD khi SePay lỗi"]:::func
        M16_10["M16.10. Thử lại thanh toán SePay"]:::func
        M16_11["M16.11. Hủy đơn hàng chưa trả & Tự động hoàn kho"]:::func
    end

    subgraph SUB_PAYMENT_GW["Xử lý Cổng Thanh toán (Backend)"]
        M16_07["M16.07. Tiếp nhận Webhook IPN từ SePay (X-Secret-Key)"]:::func
    end

    subgraph SUB_ADMIN["Quản trị Viên (Filament Admin)"]
        M16_12["M16.12. Quản lý xuất nhập tồn kho thủ công"]:::func
        M16_13["M16.13. Báo cáo doanh thu & biểu đồ bán lẻ"]:::func
        M17_01["M17.01. Kiểm soát quyền truy cập Panel theo Role"]:::func
    end

    M16 --> SUB_SHOP_USER
    M16 --> SUB_PAYMENT_GW
    M16 & M17 --> SUB_ADMIN
```
