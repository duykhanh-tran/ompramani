import React from "react";
import Link from "next/link";

export function PracticeShortcuts() {
  const tools = [
    {
      title: "Tọa Thiền",
      description: "Đồng hồ định tâm & chuông chánh niệm",
      icon: "self_improvement",
      href: "/thien",
      colorClass: "text-emerald-700 bg-emerald-500/10 hover:border-emerald-500/40",
    },
    {
      title: "Tràng Hạt 108",
      description: "Lần hạt điện tử và chu kỳ vòng",
      icon: "adjust",
      href: "/trang-hat",
      colorClass: "text-rose-700 bg-rose-500/10 hover:border-rose-500/40",
    },
    {
      title: "Trì Chú",
      description: "Thần chú chư Phật & Bồ Tát",
      icon: "record_voice_over",
      href: "/tri-chu",
      colorClass: "text-indigo-700 bg-indigo-500/10 hover:border-indigo-500/40",
    },
    {
      title: "Chuông & Mõ",
      description: "Âm thanh chánh niệm trang nghiêm",
      icon: "notifications",
      href: "/chuong-mo",
      colorClass: "text-yellow-800 bg-yellow-600/10 hover:border-yellow-600/40",
    },
    {
      title: "Thư Viện Kinh",
      description: "Kinh điển Tam Tạng đọc tụng",
      icon: "auto_stories",
      href: "/kinh",
      colorClass: "text-teal-700 bg-teal-500/10 hover:border-teal-500/40",
    },
  ];

  return (
    <div className="bg-surface-container-lowest rounded-3xl p-6 sm:p-8 border border-outline-variant/40 shadow-xs space-y-6">
      <div>
        <div className="flex items-center gap-2 mb-1">
          <span className="w-2 h-2 rounded-full bg-primary" />
          <span className="font-label-caps text-xs text-primary uppercase font-semibold">
            Tiếp Tục Tu Học
          </span>
        </div>
        <h2 className="font-headline-xl text-xl sm:text-2xl font-bold text-on-surface">
          Công Cụ Hành Trì
        </h2>
        <p className="font-body-sm text-xs text-on-surface-variant mt-0.5">
          Chọn pháp môn thích hợp để bắt đầu thời khóa mới của bạn
        </p>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        {tools.map((t) => (
          <Link
            key={t.title}
            href={t.href}
            className={`p-4 rounded-2xl border border-outline-variant/30 flex items-center gap-4 transition-all hover:shadow-xs group ${t.colorClass}`}
          >
            <div className="w-12 h-12 rounded-xl bg-surface-container flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
              <span className="material-symbols-outlined text-[24px]">
                {t.icon}
              </span>
            </div>

            <div className="min-w-0">
              <h3 className="font-headline-md text-sm font-bold text-on-surface group-hover:text-primary transition-colors">
                {t.title}
              </h3>
              <p className="font-body-sm text-xs text-outline line-clamp-1 mt-0.5">
                {t.description}
              </p>
            </div>
          </Link>
        ))}
      </div>
    </div>
  );
}
