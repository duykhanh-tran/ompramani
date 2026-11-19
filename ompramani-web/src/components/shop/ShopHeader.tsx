import React from "react";

export function ShopHeader() {
  return (
    <div className="relative mb-8 text-center sm:text-left">
      <div className="flex flex-col sm:flex-row items-center sm:items-start justify-between gap-4">
        <div>
          <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-label-md font-medium bg-amber-500/10 text-amber-700 dark:text-amber-300 border border-amber-500/20 mb-2.5">
            <span className="material-symbols-outlined text-[16px]">storefront</span>
            <span>Vật Phẩm Tu Học OmPraMani</span>
          </div>
          <h1 className="font-serif text-2xl sm:text-3xl md:text-4xl font-bold text-on-surface tracking-tight">
            Cửa Hàng Vật Phẩm Phật Giáo
          </h1>
          <p className="mt-2 text-sm sm:text-base text-on-surface-variant max-w-2xl leading-relaxed">
            Tuyển chọn tràng hạt, chuông mõ pháp khí, kinh sách và hương trầm mộc mạc. Phụng sự đại chúng trên bước đường tinh tấn, thanh tịnh.
          </p>
        </div>
      </div>

      {/* Ethical Buddhist Shopping Pledge */}
      <div className="mt-4 p-3.5 sm:p-4 rounded-2xl bg-amber-50/70 dark:bg-amber-950/20 border border-amber-200/50 dark:border-amber-800/30 flex items-start gap-3 text-left">
        <span className="material-symbols-outlined text-amber-600 dark:text-amber-400 text-[22px] shrink-0 mt-0.5">
          verified
        </span>
        <div className="text-xs sm:text-sm text-on-surface-variant leading-relaxed">
          <strong className="font-semibold text-on-surface">Cam kết chánh niệm: </strong>
          Các vật phẩm tại OmPraMani là phương tiện hỗ trợ hành trì. Chúng tôi tuyệt đối không thương mại hóa tâm linh, không thêu dệt luận điệu mê tín hay hứa hẹn tăng công đức, giải nghiệp.
        </div>
      </div>
    </div>
  );
}
