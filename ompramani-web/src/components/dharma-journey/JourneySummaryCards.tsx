import React from "react";
import { DharmaJourneySummary } from "@/types/dharma-journey";
import { formatActiveDuration, formatNumberVi } from "@/lib/utils/sacred";

interface JourneySummaryCardsProps {
  summary: DharmaJourneySummary;
}

export function JourneySummaryCards({ summary }: JourneySummaryCardsProps) {
  const cards = [
    {
      id: "total_sessions",
      title: "Thời Khóa Hoàn Thành",
      mainValue: formatNumberVi(summary.completed_sessions),
      subValue: "Phiên tu tập đã hoàn thành",
      icon: "check_circle",
      colorClass: "text-primary bg-primary/10",
    },
    {
      id: "total_duration",
      title: "Tổng Thời Gian Hành Trì",
      mainValue: formatActiveDuration(summary.total_active_duration_seconds),
      subValue: "Thời gian thực hành thực tế",
      icon: "schedule",
      colorClass: "text-amber-700 bg-amber-500/10",
    },
    {
      id: "meditation",
      title: "Thiền Định",
      mainValue: formatActiveDuration(summary.meditation.active_duration_seconds),
      subValue: `${formatNumberVi(summary.meditation.sessions)} thời khóa`,
      icon: "self_improvement",
      colorClass: "text-emerald-700 bg-emerald-500/10",
    },
    {
      id: "mala",
      title: "Tràng Hạt",
      mainValue: `${formatNumberVi(summary.mala.bead_count)} hạt`,
      subValue: `${formatNumberVi(summary.mala.completed_rounds)} vòng 108 hoàn thành`,
      icon: "adjust",
      colorClass: "text-rose-700 bg-rose-500/10",
    },
    {
      id: "buddha_name",
      title: "Niệm Phật",
      mainValue: `${formatNumberVi(summary.buddha_name.repetition_count)} biến`,
      subValue: `${formatNumberVi(summary.buddha_name.sessions)} thời khóa tự ghi nhận`,
      icon: "spa",
      colorClass: "text-amber-800 bg-amber-600/10",
    },
    {
      id: "mantra",
      title: "Trì Chú",
      mainValue: `${formatNumberVi(summary.mantra.repetition_count)} biến`,
      subValue: `${formatNumberVi(summary.mantra.sessions)} thời khóa tự ghi nhận`,
      icon: "record_voice_over",
      colorClass: "text-indigo-700 bg-indigo-500/10",
    },
    {
      id: "sutra",
      title: "Tụng & Đọc Kinh",
      mainValue: formatActiveDuration(summary.sutra.active_duration_seconds),
      subValue: `${formatNumberVi(summary.sutra.sessions)} thời khóa tụng niệm`,
      icon: "auto_stories",
      colorClass: "text-teal-700 bg-teal-500/10",
    },
    {
      id: "bell_wooden_fish",
      title: "Chuông & Mõ",
      mainValue: `${formatNumberVi(summary.bell_wooden_fish.wooden_fish_count)} mõ`,
      subValue: `${formatNumberVi(summary.bell_wooden_fish.bell_count)} chuông · ${formatNumberVi(summary.bell_wooden_fish.sessions)} phiên`,
      icon: "notifications",
      colorClass: "text-yellow-800 bg-yellow-600/10",
    },
  ];

  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      {cards.map((card) => (
        <div
          key={card.id}
          className="bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/40 shadow-xs flex flex-col justify-between hover:border-primary/40 transition-colors"
        >
          <div className="flex items-start justify-between gap-3">
            <span className="font-label-md text-xs font-semibold text-outline uppercase tracking-wider">
              {card.title}
            </span>
            <div
              className={`w-9 h-9 rounded-xl flex items-center justify-center shrink-0 ${card.colorClass}`}
            >
              <span className="material-symbols-outlined text-[20px]">
                {card.icon}
              </span>
            </div>
          </div>

          <div className="mt-4 pt-1">
            <div className="font-headline-xl text-xl sm:text-2xl font-bold text-on-surface tracking-tight">
              {card.mainValue}
            </div>
            <p className="font-body-sm text-xs text-on-surface-variant mt-1">
              {card.subValue}
            </p>
          </div>
        </div>
      ))}
    </div>
  );
}
