'use client';

import React from 'react';

interface ContentGuidelinesModalProps {
  isOpen: boolean;
  onClose: () => void;
}

export function ContentGuidelinesModal({
  isOpen,
  onClose,
}: ContentGuidelinesModalProps) {
  if (!isOpen) return null;

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-on-surface/40 backdrop-blur-xs animate-in fade-in duration-150"
      role="dialog"
      aria-modal="true"
      aria-labelledby="guidelines-title"
    >
      <div className="relative w-full max-w-xl bg-surface-container-lowest rounded-3xl p-6 sm:p-8 shadow-xl border border-outline-variant/60 max-h-[90vh] overflow-y-auto">
        <div className="flex items-center justify-between pb-4 border-b border-outline-variant/30">
          <div className="flex items-center gap-2.5">
            <div className="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center">
              <span className="material-symbols-outlined text-[20px]">
                menu_book
              </span>
            </div>
            <h2
              id="guidelines-title"
              className="font-headline-md text-xl font-semibold text-primary"
            >
              Quy Chuẩn Cầu Nguyện Thiện Lành
            </h2>
          </div>
          <button
            type="button"
            onClick={onClose}
            className="p-1 rounded-full text-outline hover:text-on-surface hover:bg-surface-container-low transition-colors"
            aria-label="Đóng"
          >
            <span className="material-symbols-outlined text-[20px]">close</span>
          </button>
        </div>

        <div className="py-5 space-y-4 font-body-md text-sm sm:text-base text-on-surface-variant leading-relaxed">
          <div className="p-4 rounded-2xl bg-primary-fixed/20 border border-primary/20 text-on-surface">
            <p className="font-semibold text-primary flex items-center gap-1.5 mb-1">
              <span className="material-symbols-outlined text-[18px]">
                verified_user
              </span>
              <span>Bảo vệ quyền riêng tư cá nhân</span>
            </p>
            <p className="text-sm text-on-surface-variant">
              Nội dung chia sẻ công khai sau khi duyệt sẽ hiển thị trên mạng
              Internet. Xin hoan hỷ <strong>không cung cấp họ tên thật đầy đủ, số điện thoại, địa chỉ cụ thể, số tài khoản</strong> hoặc thông tin nhạy cảm của bản thân và người thân.
            </p>
          </div>

          <h3 className="font-headline-sm text-base font-semibold text-on-surface pt-2">
            Nguyên tắc chia sẻ trong tinh thần thanh tịnh:
          </h3>
          <ul className="space-y-2.5 list-disc pl-5 text-sm sm:text-base">
            <li>
              <strong>Ngôn từ từ ái, tôn trọng:</strong> Viết lời nguyện với tâm từ bi, hòa nhã và kính tín, không chứa ngôn từ phán xét, hiềm khích hay chia rẽ.
            </li>
            <li>
              <strong>Không quảng cáo &amp; kêu gọi tài chính:</strong> Tuyệt đối không gắn liên kết bán hàng, huy động quyên góp cá nhân hoặc dịch vụ thương mại.
            </li>
            <li>
              <strong>Không mạo danh:</strong> Không đăng tải nội dung thay mặt người khác khi chưa có sự đồng thuận, hoặc mạo danh các tổ chức tâm linh.
            </li>
            <li>
              <strong>Tâm nguyện thuần khiết:</strong> Không gian cầu nguyện không tính điểm, không xếp hạng và không khẳng định hệ thống có thể bảo đảm lời nguyện thành hiện thực.
            </li>
          </ul>

          <div className="p-3.5 rounded-2xl bg-surface-container-low text-outline text-xs leading-relaxed">
            Mọi lời nguyện gửi chia sẻ đều được Ban Kiểm Duyệt tiếp nhận và thẩm định thủ công nhằm gìn giữ sự trang nghiêm và an lành chung cho toàn thể đại chúng.
          </div>
        </div>

        <div className="pt-3 border-t border-outline-variant/30 flex justify-end">
          <button
            type="button"
            onClick={onClose}
            className="px-6 py-2.5 rounded-full bg-primary text-on-primary hover:bg-on-primary-container font-label-md text-sm font-semibold transition-colors"
          >
            Đã hiểu &amp; Đóng
          </button>
        </div>
      </div>
    </div>
  );
}
