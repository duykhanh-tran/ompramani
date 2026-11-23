import React from "react";
import Link from "next/link";

export function Footer() {
  return (
    <footer className="w-full bg-surface-container-low text-on-surface-variant shadow-[0_-1px_10px_rgba(140,109,45,0.03)] mt-16">
      <div className="max-w-[1360px] mx-auto px-4 sm:px-6 py-12">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8 pb-10">
          {/* Brand Col */}
          <div className="lg:col-span-2 space-y-4">
            <div className="flex items-center gap-3">
              <span className="font-headline-md text-headline-md font-semibold text-primary">
                Om Mani
              </span>
            </div>
            <p className="font-body-md text-body-md max-w-md text-on-surface-variant">
              Cổng thông tin và không gian tu tập trực tuyến trang nghiêm dành
              cho hàng triệu Phật tử khắp 174 quốc gia. Nơi kết nối tâm nguyện,
              hành trì sám hối, gìn giữ pháp lạc và chánh niệm trong từng khoảnh
              khắc hiện tại.
            </p>
            <div className="flex items-center gap-2 text-primary">
              <span className="material-symbols-outlined text-[18px]">
                public
              </span>
              <span className="font-label-md text-label-md font-semibold">
                Cộng đồng Phật tử 174 quốc gia cùng tu tập
              </span>
            </div>
          </div>

          {/* Col 1 */}
          <div>
            <h4 className="font-headline-md text-headline-md font-medium text-on-surface mb-3">
              Kinh Điển &amp; Giáo Lý
            </h4>
            <ul className="space-y-2 font-body-sm text-body-sm">
              <li>
                <Link
                  href="/kinh/kinh-a-di-da"
                  className="hover:text-primary transition-colors"
                >
                  Kinh Kim Cương &amp; Bát Nhã
                </Link>
              </li>
              <li>
                <Link
                  href="/kinh/kinh-phap-cu"
                  className="hover:text-primary transition-colors"
                >
                  Kinh Pháp Cú Toàn Tập
                </Link>
              </li>
              <li>
                <Link
                  href="/kinh/kinh-dia-tang"
                  className="hover:text-primary transition-colors"
                >
                  Kinh Địa Tạng Bản Nguyện
                </Link>
              </li>
              <li>
                <Link
                  href="/video-phat-phap"
                  className="hover:text-primary transition-colors"
                >
                  Video Phật Pháp &amp; Bài Giảng
                </Link>
              </li>
              <li>
                <Link
                  href="/thien"
                  className="hover:text-primary transition-colors"
                >
                  Thực Hành Thiền Định Hàng Ngày
                </Link>
              </li>
              <li>
                <Link
                  href="/chu"
                  className="hover:text-primary transition-colors"
                >
                  Tuyển Tập Thần Chú &amp; Chân Ngôn
                </Link>
              </li>
            </ul>
          </div>

          {/* Col 2 */}
          <div>
            <h4 className="font-headline-md text-headline-md font-medium text-on-surface mb-3">
              Tu Học &amp; Hành Trì
            </h4>
            <ul className="space-y-2 font-body-sm text-body-sm">
              <li>
                <Link
                  href="/lich-van-nien"
                  className="hover:text-primary transition-colors"
                >
                  Lịch Vạn Niên &amp; Ngày Vía Phật
                </Link>
              </li>
              <li>
                <Link
                  href="/trang-hat"
                  className="hover:text-primary transition-colors"
                >
                  Tràng Hạt Niệm Phật Điện Tử
                </Link>
              </li>
              <li>
                <Link
                  href="/chuong-mo"
                  className="hover:text-primary transition-colors"
                >
                  Chuông Mõ Khai Tâm Online
                </Link>
              </li>
              <li>
                <Link
                  href="/cau-nguyen"
                  className="hover:text-primary transition-colors"
                >
                  Cầu Nguyện Cộng Đồng
                </Link>
              </li>
              <li>
                <Link
                  href="/cua-hang"
                  className="hover:text-primary transition-colors"
                >
                  Cửa Hàng Vật Phẩm Phật Giáo
                </Link>
              </li>
              <li>
                <Link
                  href="/video-phat-phap"
                  className="hover:text-primary transition-colors"
                >
                  Khóa Tu Học &amp; Bài Giảng
                </Link>
              </li>
            </ul>
          </div>

          {/* Col 3 */}
          <div>
            <h4 className="font-headline-md text-headline-md font-medium text-on-surface mb-3">
              Phật Sự &amp; Hộ Pháp
            </h4>
            <ul className="space-y-2 font-body-sm text-body-sm">
              <li>
                <Link
                  href="/cua-hang"
                  className="hover:text-primary transition-colors"
                >
                  Đóng Góp Ấn Tống Kinh Điển
                </Link>
              </li>
              <li>
                <Link
                  href="/cau-nguyen/gui-loi-nguyen"
                  className="hover:text-primary transition-colors"
                >
                  Gửi Ý Niệm Cầu An Cầu Siêu
                </Link>
              </li>
              <li>
                <Link
                  href="/tim-chua"
                  className="hover:text-primary transition-colors"
                >
                  Chùa Việt Nam &amp; Thiện Nguyện
                </Link>
              </li>
              <li>
                <Link
                  href="/guide"
                  className="hover:text-primary transition-colors"
                >
                  Hướng Dẫn Sử Dụng Cổng Web
                </Link>
              </li>
            </ul>
          </div>
        </div>

        {/* Quote & Bottom Bar */}
        <div className="pt-8 bg-surface-container/40 rounded-xl px-6 py-6 text-center space-y-3">
          <p className="font-quote-editorial text-quote-editorial text-primary italic max-w-2xl mx-auto">
            “Tâm là chủ, tâm dẫn đầu các pháp. Tâm an tịnh nói năng hay hành
            động, hạnh phúc sẽ theo ta như bóng chẳng rời hình.”
          </p>
          <div className="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2 font-label-md text-label-md text-outline">
            <p>
              © 2026 Om Mani. Bản quyền thuộc Không gian Tu tập Phật giáo Trực
              tuyến.
            </p>
            <div className="flex items-center gap-6">
              <Link
                href="/terms"
                className="hover:text-primary transition-colors"
              >
                Quy chế bảo vệ chánh pháp
              </Link>
              <Link
                href="/privacy"
                className="hover:text-primary transition-colors"
              >
                Chính sách thanh tịnh &amp; Riêng tư
              </Link>
              <Link
                href="/contact"
                className="hover:text-primary transition-colors"
              >
                Liên hệ Ban Quản Trị
              </Link>
            </div>
          </div>
        </div>
      </div>
    </footer>
  );
}
