import React from "react";
import Link from "next/link";

const COMMUNITY_FEATURES = [
  { label: "Cầu an & Cầu siêu", icon: "folded_hands" },
  { label: "Thắp hương trực tuyến", icon: "local_fire_department" },
  { label: "Hồi hướng công đức", icon: "volunteer_activism" },
  { label: "Đạo tràng đồng tu", icon: "groups" },
];

const VIDEO_TOPICS = [
  "Giảng giải Giáo lý",
  "Nghi thức Tụng niệm",
  "Tư liệu Danh lam",
  "Thiền & Tỉnh thức",
];

export function CommunitySection() {
  return (
    <div className="lg:col-span-4 space-y-6">
      {/* 1. Community & Prayer Hall Card */}
      <div className="bg-gradient-to-br from-surface-container to-surface-container-high p-6 rounded-3xl shadow-sm relative overflow-hidden">
        <div className="flex items-center gap-2 mb-3">
          <span className="material-symbols-outlined text-primary text-[24px]">
            diversity_3
          </span>
          <span className="font-label-caps text-label-caps text-primary uppercase">
            Đạo Tràng Đồng Tu
          </span>
        </div>
        <h3 className="font-headline-lg text-headline-lg font-semibold text-on-surface leading-tight">
          Cộng Đồng Tu Tập &amp; Hồi Hướng
        </h3>
        <p className="font-body-sm text-body-sm text-on-surface-variant mt-2">
          Không gian trang nghiêm kết nối đạo hữu bốn phương: cùng chia sẻ ước nguyện
          thiện lành, thắp hương cầu an, cầu siêu và hồi hướng công đức thanh tịnh.
        </p>

        {/* Feature Badges Cloud */}
        <div className="flex flex-wrap gap-2 my-5">
          {COMMUNITY_FEATURES.map((item, idx) => (
            <span
              key={idx}
              className="px-3 py-1 bg-surface-container-lowest rounded-full font-label-md text-label-md text-on-surface flex items-center gap-1.5 shadow-xs"
            >
              <span className="material-symbols-outlined text-[15px] text-primary">
                {item.icon}
              </span>
              {item.label}
            </span>
          ))}
        </div>

        <Link
          href="/cau-nguyen"
          className="w-full py-2.5 rounded-xl bg-primary text-on-primary hover:bg-on-primary-container transition-colors font-label-lg text-label-lg font-semibold flex items-center justify-center gap-2"
        >
          <span>Tham gia Đạo Tràng Trực Tuyến</span>
          <span className="material-symbols-outlined text-[18px]">
            arrow_forward
          </span>
        </Link>
      </div>

      {/* 2. Buddhist Video Library Card */}
      <div className="bg-surface-container-lowest p-6 rounded-3xl shadow-sm">
        <div className="flex items-center justify-between mb-3">
          <span className="font-label-caps text-label-caps text-secondary uppercase">
            Tư Liệu Nghe Nhìn
          </span>
          <span className="material-symbols-outlined text-secondary">
            smart_display
          </span>
        </div>
        <h4 className="font-headline-md text-headline-md font-semibold text-on-surface">
          Thư Viện Video Phật Pháp
        </h4>
        <p className="font-body-sm text-body-sm text-on-surface-variant mt-1">
          Tuyển chọn bài giảng chánh pháp, nghi thức truyền thống và tư liệu tự viện
          có bản quyền minh bạch, được biên tập trang nghiêm.
        </p>

        {/* Topic Tags */}
        <div className="flex flex-wrap gap-2 mt-4">
          {VIDEO_TOPICS.map((topic, idx) => (
            <span
              key={idx}
              className="px-2.5 py-1 bg-surface-container rounded-lg font-label-sm text-label-sm text-on-surface-variant"
            >
              {topic}
            </span>
          ))}
        </div>

        <Link
          href="/video-phat-phap"
          className="w-full mt-5 py-2.5 rounded-xl bg-surface-container text-primary hover:bg-primary-container hover:text-on-primary-container transition-colors font-label-md text-label-md font-semibold flex items-center justify-center gap-1.5"
        >
          <span>Khám phá video Phật pháp</span>
          <span className="material-symbols-outlined text-[16px]">
            arrow_forward
          </span>
        </Link>
      </div>
    </div>
  );
}
