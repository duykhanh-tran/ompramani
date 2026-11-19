"use client";

import React, { useState } from "react";
import Link from "next/link";
import { DEMO_PRACTICE_TOOLS, PracticeToolItem } from "@/data/demo/practiceWheel";

export function PracticeWheelSection() {
  const [activeTool, setActiveTool] = useState<string>("meditation");
  const [notice, setNotice] = useState<string | null>(null);

  const centerTool = DEMO_PRACTICE_TOOLS.find((t) => t.isCenter);
  const orbitalTools = DEMO_PRACTICE_TOOLS.filter((t) => !t.isCenter);

  const handleToolClick = (tool: PracticeToolItem) => {
    setActiveTool(tool.id);
    if (!tool.href) {
      setNotice(`Phương tiện "${tool.name}" đang được hoàn thiện.`);
      setTimeout(() => setNotice(null), 3000);
    }
  };

  return (
    <section
      id="phuong-tien"
      className="bg-surface-container-lowest rounded-3xl p-6 sm:p-8 lg:p-10 shadow-sm relative overflow-hidden scroll-mt-20"
    >
      {/* Background ambient circular halos */}
      <div className="absolute inset-0 flex items-center justify-center pointer-events-none opacity-40">
        <div className="w-[560px] h-[560px] rounded-full border border-secondary-container/30 bg-radial from-surface-container-low/60 to-transparent" />
      </div>

      {/* Floating Notice / Toast */}
      {notice && (
        <div className="absolute top-4 left-1/2 -translate-x-1/2 z-30 px-4 py-2 rounded-full bg-primary text-on-primary text-xs sm:text-sm font-medium shadow-md flex items-center gap-2 animate-fade-in transition-all">
          <span className="material-symbols-outlined text-[18px]">info</span>
          <span>{notice}</span>
        </div>
      )}

      {/* Header */}
      <div className="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-4 relative z-10">
        <div>
          <span className="font-label-caps text-label-caps text-primary uppercase tracking-wider">
            Trung tâm tu tập nổi bật
          </span>
          <h2 className="font-headline-xl text-headline-xl text-on-surface font-semibold tracking-tight mt-1">
            Phương Tiện Tu Tập Hằng Ngày
          </h2>
          <p className="font-body-md text-body-md text-on-surface-variant mt-1">
            Gìn giữ chánh niệm và nuôi dưỡng tâm bồ đề qua từng khoảnh khắc hiện tại.
          </p>
        </div>
        <a
          href="#phuong-tien"
          className="px-5 py-2 rounded-full bg-surface-container-low text-primary hover:bg-primary hover:text-on-primary transition-all font-label-lg text-label-lg self-start md:self-auto border border-outline-variant/40"
        >
          Xem tất cả phương tiện tu tập
        </a>
      </div>

      {/* Radial Interactive Dashboard */}
      <div className="relative py-8 flex flex-col items-center justify-center min-h-[460px] sm:min-h-[500px]">
        <div className="relative w-[300px] h-[300px] sm:w-[420px] sm:h-[420px] flex items-center justify-center">
          {/* Concentric orbital rings */}
          <div className="absolute inset-0 rounded-full border border-secondary-fixed/50 border-dashed pointer-events-none" />
          <div className="absolute w-[220px] h-[220px] sm:w-[300px] sm:h-[300px] rounded-full border border-secondary-container/40 pointer-events-none" />
          <div className="absolute inset-4 sm:inset-6 rounded-full flex items-center justify-center pointer-events-none">
            <div className="w-full h-full rounded-full border-[1.5px] border-secondary-container/30 border-dotted" />
          </div>

          {/* Central Anchor: Thiền định */}
          {centerTool && (
            <Link
              href={centerTool.href || "/thien"}
              className="relative z-20 flex flex-col items-center justify-center cursor-pointer group focus:outline-none"
              title="Vào thực hành Thiền định"
            >
              <div
                className="relative w-24 h-24 sm:w-36 sm:h-36 rounded-full bg-gradient-to-br from-[#c49b4f] via-[#a37930] to-[#785915] text-on-primary flex flex-col items-center justify-center shadow-md group-hover:scale-105 group-hover:shadow-lg transition-all duration-300 ring-4 ring-secondary-container/50 scale-105 ring-primary"
              >
                <div className="absolute inset-0 rounded-full bg-surface-container-lowest/15 animate-ping pointer-events-none" />
                <span className="material-symbols-outlined text-[28px] sm:text-[40px] text-surface mb-0.5">
                  {centerTool.icon}
                </span>
                <span className="font-headline-md text-[16px] sm:text-headline-md font-semibold text-surface tracking-wide">
                  {centerTool.name}
                </span>
              </div>
              {centerTool.subLabel && (
                <span className="font-label-caps text-[9px] sm:text-[10px] text-primary bg-secondary-container/60 px-2.5 py-0.5 rounded-full mt-2 font-medium">
                  {centerTool.subLabel}
                </span>
              )}
            </Link>
          )}

          {/* Orbiting Satellites */}
          {orbitalTools.map((tool: PracticeToolItem) => {
            const isSelected = activeTool === tool.id;
            const href = tool.href;

            const content = (
              <>
                <div
                  className={`w-12 h-12 sm:w-16 sm:h-16 rounded-full border flex items-center justify-center shadow-xs transition-all ${
                    isSelected
                      ? "bg-primary-container text-on-primary-container border-primary scale-110"
                      : "bg-surface-container-low border-secondary-fixed text-primary group-hover:scale-110 group-hover:bg-primary-container group-hover:text-on-primary-container"
                  }`}
                >
                  <span className="material-symbols-outlined text-[22px] sm:text-[28px]">
                    {tool.icon}
                  </span>
                </div>
                <span
                  className={`font-label-lg text-[12px] sm:text-label-lg font-semibold mt-1 transition-colors text-center whitespace-nowrap ${
                    isSelected
                      ? "text-primary"
                      : "text-on-surface group-hover:text-primary"
                  }`}
                >
                  {tool.name}
                </span>
              </>
            );

            if (href) {
              return (
                <Link
                  key={tool.id}
                  href={href}
                  className={`absolute ${tool.positionClass} flex flex-col items-center cursor-pointer group z-20 focus:outline-none`}
                  title={
                    tool.id === "video_lectures"
                      ? "Xem Video bài giảng Phật pháp"
                      : `Vào thực hành ${tool.name}${tool.id === "beads" ? " 108 hạt" : ""}`
                  }
                >
                  {content}
                </Link>
              );
            }

            return (
              <button
                key={tool.id}
                type="button"
                suppressHydrationWarning
                onClick={() => handleToolClick(tool)}
                className={`absolute ${tool.positionClass} flex flex-col items-center cursor-pointer group z-20 focus:outline-none`}
                title={`${tool.name} (Đang phát triển)`}
              >
                {content}
              </button>
            );
          })}
        </div>

        {/* Bottom CTA Button */}
        <div className="mt-8 z-10">
          <a
            href="#phuong-tien"
            className="px-6 py-2.5 rounded-full bg-surface-container hover:bg-primary-container hover:text-on-primary-container text-on-surface font-label-lg text-label-lg font-medium transition-all shadow-xs inline-flex items-center gap-2"
          >
            <span>Xem tất cả phương tiện tu tập</span>
            <span className="material-symbols-outlined text-[18px]">
              arrow_forward
            </span>
          </a>
        </div>
      </div>
    </section>
  );
}
