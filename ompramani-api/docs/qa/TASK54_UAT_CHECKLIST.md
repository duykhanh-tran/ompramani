# OMPRAMANI — TASK 54: SỔ TAY NGHIỆM THU DÀNH CHO CHỦ DỰ ÁN
**USER ACCEPTANCE TESTING (UAT) CHECKLIST**

Tài liệu này được biên soạn ngắn gọn, tập trung vào 16 kịch bản trải nghiệm người dùng thực tế để Chủ Dự Án có thể tự mình kiểm tra và nghiệm thu trước khi chính thức bấm nút Launch.

---

| STT | Mã kịch bản | Phân hệ | Các bước thực hiện của Chủ Dự Án | Kết quả mong đợi | Trạng thái kỹ thuật | Xác nhận của Chủ Dự Án |
| :---: | :---: | :--- | :--- | :--- | :---: | :---: |
| **01** | `UAT-01` | **Khách vãng lai** | Truy cập trang chủ `http://localhost:3000`, lướt xem các khối nội dung, click vào banner Kinh điển, Tôn tượng và Bài viết. | Giao diện hiển thị trang nghiêm theo phong cách giấy ngà, chữ rõ ràng, ảnh sắc nét, không có lỗi console hay giật lag. | **PASS** | [  ] Đạt |
| **02** | `UAT-02` | **Đăng ký tài khoản** | Vào `/dang-ky`, nhập Họ tên, Email mới và Mật khẩu $\ge 8$ ký tự $\rightarrow$ Nhấn Đăng Ký. | Đăng ký thành công, tự động chuyển vào trang Hồ sơ cá nhân với thông tin đã nhập chính xác. | **PASS** | [  ] Đạt |
| **03** | `UAT-03` | **Đọc Kinh điển** | Vào `/kinh`, chọn Kinh A Di Đà $\rightarrow$ Đọc nội dung, thử chuyển phẩm, chỉnh cỡ chữ $\rightarrow$ Nhấn lưu dấu trang (Bookmark). | Văn bản hiển thị trang nghiêm, chuyển phẩm mượt mà, dấu trang được lưu đúng vị trí trong hồ sơ cá nhân. | **PASS** | [  ] Đạt |
| **04** | `UAT-04` | **Trì Chú & Audio** | Vào `/tri-chu`, chọn Đức Quan Thế Âm Bồ Tát $\rightarrow$ Mở câu thần chú Om Mani Padme Hum $\rightarrow$ Nhấn Play audio. | Âm thanh trì tụng thanh tịnh vang lên rõ ràng, có nút Pause, Loop và thanh tiến trình. Không tự động phát khi chưa bấm. | **PASS** | [  ] Đạt |
| **05** | `UAT-05` | **Thiền định** | Vào `/thien`, chọn thời gian 5 phút $\rightarrow$ Nhấn Bắt đầu thiền $\rightarrow$ Thử Pause và Resume $\rightarrow$ Nhấn Hoàn thành. | Có tiếng chuông mở đầu thanh tịnh, đồng hồ đếm ngược chính xác, khi hoàn thành có tiếng chuông kết thúc và màn hình chúc mừng. | **PASS** | [  ] Đạt |
| **06** | `UAT-06` | **Tiến trình tu tập** | Sau khi hoàn thành một thời khóa thiền hoặc niệm Phật $\rightarrow$ Vào `/tai-khoan/hanh-trinh`. | Hiển thị chính xác số phút vừa hành trì, số buổi tu tập trong tuần và chuỗi ngày liên tiếp (Streak) tăng lên 1 ngày. | **PASS** | [  ] Đạt |
| **07** | `UAT-07` | **Lịch Vạn Niên** | Vào `/lich-van-nien`, xem ngày hôm nay và chuyển sang các tháng khác trong năm 2026. | Hiển thị chính xác ngày Âm lịch theo chuẩn Việt Nam, ghi rõ ngày Sóc, Vọng, Thập Trai và ngày vía Chư Phật / Bồ Tát. | **PASS** | [  ] Đạt |
| **08** | `UAT-08` | **Phòng Lời Nguyện** | Vào `/cau-nguyen`, đọc các lời nguyện cộng đồng $\rightarrow$ Nhập một lời nguyện thiện lành và nhấn Gửi. | Lời nguyện xuất hiện với trạng thái trang nghiêm, có nút báo cáo vi phạm nội dung không phù hợp. | **PASS** | [  ] Đạt |
| **09** | `UAT-09` | **Xem Pháp khí / Cửa hàng** | Vào `/cua-hang`, duyệt danh sách sản phẩm $\rightarrow$ Bấm xem chi tiết một chuỗi tràng hạt bồ đề. | Hiển thị đầy đủ hình ảnh mộc mạc, mô tả ý nghĩa tu học, giá tiền VND rõ ràng và trạng thái còn hàng/hết hàng. | **PASS** | [  ] Đạt |
| **10** | `UAT-10` | **Thao tác Giỏ hàng** | Tại trang chi tiết vật phẩm, nhấn "Thêm vào giỏ" $\rightarrow$ Vào `/gio-hang` thử tăng giảm số lượng hoặc xóa vật phẩm. | Giỏ hàng cập nhật tức thì, tổng tiền tự động tính đúng theo giá bán chuẩn của Backend, không bị sai lệch số tiền. | **PASS** | [  ] Đạt |
| **11** | `UAT-11` | **Đặt hàng COD** | Tại `/thanh-toan`, điền Tên, SĐT, Địa chỉ $\rightarrow$ Chọn phương thức "Thanh toán khi nhận hàng (COD)" $\rightarrow$ Đặt hàng. | Đơn hàng tạo thành công, kho giảm đúng số lượng, chuyển sang trang xác nhận đơn với mã đơn hàng hợp lệ. | **PASS** | [  ] Đạt |
| **12** | `UAT-12` | **Đặt hàng SePay VietQR** | Tại `/thanh-toan`, điền thông tin $\rightarrow$ Chọn phương thức "Chuyển khoản SePay VietQR" $\rightarrow$ Nhấn Thanh toán. | Chuyển hướng sang giao diện SePay Sandbox với đúng số tiền đơn hàng và mã QR thanh toán chuẩn ngân hàng. | **PASS** | [  ] Đạt |
| **13** | `UAT-13` | **Lịch sử đơn hàng** | Vào `/tai-khoan/don-hang`, kiểm tra danh sách đơn vừa đặt. | Hiển thị đúng mã đơn hàng, ngày đặt, danh sách vật phẩm và trạng thái thanh toán (Chờ thanh toán / Đã thanh toán). | **PASS** | [  ] Đạt |
| **14** | `UAT-14` | **Quản trị Filament** | Đăng nhập Admin tại `http://localhost:8000/admin` $\rightarrow$ Thử chỉnh sửa một bài viết hoặc thêm một sự kiện lịch. | Giao diện quản trị tiếng Việt trực quan, lưu dữ liệu nhanh chóng, hiển thị ngay lập tức ra ngoài giao diện công khai. | **PASS** | [  ] Đạt |
| **15** | `UAT-15` | **Quản lý kho hàng** | Trong Filament Admin, vào mục Quản lý kho $\rightarrow$ Xem danh sách tồn kho sản phẩm. | Số lượng tồn kho phản ánh chính xác các biến động xuất nhập sau khi đặt hàng hoặc hủy đơn. | **PASS** | [  ] Đạt |
| **16** | `UAT-16` | **Thống kê Doanh thu** | Trong Filament Admin, vào trang Báo cáo Doanh thu. | Biểu đồ doanh thu chỉ hiển thị các đơn hàng đã thanh toán thực tế (Paid), tính đúng tổng tiền thực thu, không cộng trùng lặp. | **PASS** | [  ] Đạt |

---

### Kết luận nghiệm thu của Chủ Dự Án:
- **Đánh giá chung**: `[  ] Chấp thuận nghiệm thu (PASS UAT)` | `[  ] Yêu cầu chỉnh sửa thêm`
- **Chữ ký xác nhận**: ............................................................
- **Ngày xác nhận**: ...... / ...... / 2026
