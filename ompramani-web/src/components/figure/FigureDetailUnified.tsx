"use client";

import React, { useState, useEffect, useMemo } from "react";
import Image from "next/image";
import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { BuddhistFigure, Mantra } from "@/types/api";
import { resolveMediaUrl } from "@/lib/utils/media";
import { getFigureTraditions, getFigureTypeLabel, parseSafeContent } from "@/lib/utils/sacred";
import { FigureVowsAccordion } from "./FigureVowsAccordion";
import { RelatedSutras } from "./RelatedSutras";
import { MantraChantPlayer } from "@/components/mantra/MantraChantPlayer";

interface FigureDetailUnifiedProps {
  figure: BuddhistFigure;
}

type TabKey = "tieu-su" | "dai-nguyen" | "than-chu";

function ContentRenderer({ rawContent }: { rawContent: string }) {
  const parsed = parseSafeContent(rawContent);

  if (parsed.isHtml) {
    return (
      <div
        className="w-full space-y-4 leading-relaxed break-words font-serif text-[16px] sm:text-[17px] leading-[30px] text-on-surface"
        dangerouslySetInnerHTML={{ __html: parsed.sanitizedHtml }}
      />
    );
  }

  return (
    <div className="w-full space-y-4 break-words font-serif text-[16px] sm:text-[17px] leading-[30px] text-on-surface">
      {parsed.paragraphs.map((p, idx) => (
        <p key={idx} className="whitespace-pre-line leading-relaxed text-justify">
          {p}
        </p>
      ))}
    </div>
  );
}

export function FigureDetailUnified({ figure }: FigureDetailUnifiedProps) {
  const searchParams = useSearchParams();
  const requestedTab = searchParams.get("tab");
  const requestedMantra = searchParams.get("mantra");

  const resolveTab = (tab: string | null): TabKey => {
    if (tab === "than-chu" || tab === "mantra") return "than-chu";
    if (tab === "dai-nguyen" || tab === "vows") return "dai-nguyen";
    return "tieu-su";
  };

  const [activeTab, setActiveTab] = useState<TabKey>(() => resolveTab(requestedTab));

  // Mantras related to this figure
  const mantras: Mantra[] = useMemo(() => figure.mantras || [], [figure.mantras]);
  const [selectedMantraSlug, setSelectedMantraSlug] = useState<string>(
    mantras.length > 0 ? (requestedMantra || mantras[0].slug) : ""
  );

  // Sync tab from query param if available
  useEffect(() => {
    if (requestedTab) {
      const nextTab = resolveTab(requestedTab);
      setActiveTab((prev) => (prev !== nextTab ? nextTab : prev));
    }
  }, [requestedTab]);

  // Sync requested mantra from query param
  useEffect(() => {
    if (requestedMantra && mantras.some((m) => m.slug === requestedMantra)) {
      setSelectedMantraSlug(requestedMantra);
    }
  }, [requestedMantra, mantras]);

  const activeMantra =
    mantras.find((m) => m.slug === selectedMantraSlug) ||
    (mantras.length > 0 ? mantras[0] : null);

  const imageUrl = resolveMediaUrl(figure.thumbnail);
  const traditions = getFigureTraditions(figure);
  const figureTypeLabel = getFigureTypeLabel(figure.figure_type);

  const vowsCount =
    figure.vows?.length ||
    (figure.great_vows ? 48 : 0);

  return (
    <div className="max-w-[880px] mx-auto px-4 sm:px-6 py-6 sm:py-10 space-y-6 sm:space-y-8 select-none">
      {/* 1. Phần đầu: Nút quay lại & Danh hiệu & Ảnh lớn */}
      <header className="flex flex-col items-center text-center space-y-4">
        {/* Back Link */}
        <div className="w-full flex items-center justify-start">
          <Link
            href="/phat-bo-tat"
            className="inline-flex items-center gap-1 text-xs sm:text-sm font-medium text-[#735d45] hover:text-[#2b1f14] transition-colors py-1 px-2 rounded-full hover:bg-[#ebdcca]/40"
            title="Trở về danh sách Phật & Bồ Tát"
          >
            <span className="material-symbols-outlined text-lg leading-none">arrow_back</span>
            <span>Phật &amp; Bồ Tát</span>
          </Link>
        </div>

        {/* Large Portrait Image with Sacred Gold Border */}
        <div className="relative w-44 h-56 sm:w-56 sm:h-72 rounded-3xl overflow-hidden border-2 border-[#d4af37]/60 shadow-lg bg-[#FAF7F0] shrink-0">
          {imageUrl ? (
            <Image
              src={imageUrl}
              alt={figure.name}
              fill
              priority
              sizes="(max-width: 640px) 176px, 224px"
              className="object-cover object-top"
            />
          ) : (
            <div className="w-full h-full flex items-center justify-center text-[#c49a45]/60 bg-[#FAF7F0]">
              <span className="material-symbols-outlined text-6xl">spa</span>
            </div>
          )}
        </div>

        {/* Figure Badges & Titles */}
        <div className="space-y-1.5 max-w-xl">
          <div className="flex flex-wrap items-center justify-center gap-1.5">
            <span className="px-3 py-0.5 rounded-full bg-[#fcedc7] border border-[#d4af37]/50 text-[#694808] font-sans text-xs font-semibold">
              {figureTypeLabel}
            </span>
            {traditions.map((t, i) => (
              <span
                key={i}
                className="px-2.5 py-0.5 rounded-full bg-[#f0eae1] border border-[#d8ccbc] text-[#6d573f] font-sans text-xs"
              >
                {t.name}
              </span>
            ))}
          </div>

          <h1 className="font-serif text-2xl sm:text-3xl md:text-4xl font-bold text-[#2b1f14] tracking-wide">
            {figure.name}
          </h1>

          {figure.original_name && (
            <p className="font-sans text-xs sm:text-sm text-[#735d45] italic">
              {figure.original_name}
            </p>
          )}

          {figure.short_description && (
            <p className="font-sans text-xs sm:text-sm text-[#5c462e] leading-relaxed pt-1 max-w-lg mx-auto">
              {figure.short_description}
            </p>
          )}
        </div>
      </header>

      {/* 2. Thanh chuyển Tab Bo Tròn (Pill / Segmented Control) */}
      <div className="flex items-center justify-center">
        <div className="inline-flex p-1 rounded-full bg-[#eee5d8] border border-[#d8ccbc] shadow-2xs">
          <button
            type="button"
            onClick={() => setActiveTab("tieu-su")}
            className={`px-4 sm:px-6 py-2 rounded-full font-serif text-xs sm:text-sm font-semibold transition-all ${
              activeTab === "tieu-su"
                ? "bg-[#FAF7F0] text-[#2b1f14] shadow-xs"
                : "text-[#735d45] hover:text-[#2b1f14]"
            }`}
          >
            Tiểu sử
          </button>

          <button
            type="button"
            onClick={() => setActiveTab("dai-nguyen")}
            className={`px-4 sm:px-6 py-2 rounded-full font-serif text-xs sm:text-sm font-semibold transition-all flex items-center gap-1.5 ${
              activeTab === "dai-nguyen"
                ? "bg-[#FAF7F0] text-[#2b1f14] shadow-xs"
                : "text-[#735d45] hover:text-[#2b1f14]"
            }`}
          >
            <span>Đại nguyện</span>
            {vowsCount > 0 && (
              <span className="text-[10px] px-1.5 py-0.2 rounded-full bg-[#d4af37]/20 text-[#694808]">
                {vowsCount}
              </span>
            )}
          </button>

          <button
            type="button"
            onClick={() => setActiveTab("than-chu")}
            className={`px-4 sm:px-6 py-2 rounded-full font-serif text-xs sm:text-sm font-semibold transition-all flex items-center gap-1.5 ${
              activeTab === "than-chu"
                ? "bg-[#FAF7F0] text-[#2b1f14] shadow-xs"
                : "text-[#735d45] hover:text-[#2b1f14]"
            }`}
          >
            <span>Thần chú</span>
            {mantras.length > 0 && (
              <span className="text-[10px] px-1.5 py-0.2 rounded-full bg-[#8f5a28]/20 text-[#683e18]">
                {mantras.length}
              </span>
            )}
          </button>
        </div>
      </div>

      {/* 3. Tab Contents */}
      <main className="w-full space-y-6">
        {/* TAB 1: TIỂU SỬ */}
        {activeTab === "tieu-su" && (
          <div className="bg-[#FAF7F0] rounded-3xl p-6 sm:p-8 border border-[#dfd2be] shadow-sm space-y-6 animate-in fade-in duration-150">
            <div className="flex items-center gap-3 pb-3 border-b border-[#ebdcca]">
              <span className="material-symbols-outlined text-2xl text-[#8f5a28] leading-none">
                history_edu
              </span>
              <div>
                <span className="font-mono text-[10px] uppercase tracking-widest text-[#8c7457] block">
                  Bổn Tích &amp; Công Hạnh
                </span>
                <h2 className="font-serif text-lg sm:text-xl font-bold text-[#2b1f14]">
                  Giới Thiệu &amp; Công Hạnh
                </h2>
              </div>
            </div>

            {figure.biography ? (
              <ContentRenderer rawContent={figure.biography} />
            ) : (
              <p className="font-sans text-sm text-[#735d45] italic">
                Nội dung tiểu sử chi tiết của vị Phật / Bồ Tát này đang được ban biên tập hoàn thiện.
              </p>
            )}

            {/* Iconography if present */}
            {figure.iconography && (
              <div className="pt-4 border-t border-[#ebdcca] space-y-3">
                <h3 className="font-serif text-base font-bold text-[#2b1f14] flex items-center gap-2">
                  <span className="material-symbols-outlined text-lg text-[#8f5a28]">
                    filter_vintage
                  </span>
                  <span>Tôn Tượng &amp; Biểu Trưng</span>
                </h3>
                <ContentRenderer rawContent={figure.iconography} />
              </div>
            )}

            {/* Practice Guidance if present */}
            {figure.practice_guidance && (
              <div className="pt-4 border-t border-[#ebdcca] space-y-3">
                <h3 className="font-serif text-base font-bold text-[#2b1f14] flex items-center gap-2">
                  <span className="material-symbols-outlined text-lg text-[#8f5a28]">
                    self_improvement
                  </span>
                  <span>Hướng Dẫn Hành Trì</span>
                </h3>
                <ContentRenderer rawContent={figure.practice_guidance} />
              </div>
            )}
          </div>
        )}

        {/* TAB 2: ĐẠI NGUYỆN */}
        {activeTab === "dai-nguyen" && (
          <div className="animate-in fade-in duration-150">
            <FigureVowsAccordion
              figureName={figure.name}
              vowsIntroduction={figure.vows_introduction}
              legacyGreatVows={figure.great_vows}
              vows={figure.vows}
            />
          </div>
        )}

        {/* TAB 3: THẦN CHÚ */}
        {activeTab === "than-chu" && (
          <div className="space-y-6 animate-in fade-in duration-150">
            {mantras.length === 0 ? (
              <div className="bg-[#FAF7F0] rounded-3xl p-8 sm:p-12 text-center border border-[#dfd2be] shadow-sm space-y-3">
                <span className="material-symbols-outlined text-4xl text-[#c49a45]/60">
                  spa
                </span>
                <h3 className="font-serif text-lg font-bold text-[#2b1f14]">
                  Chưa có thần chú liên kết
                </h3>
                <p className="font-sans text-xs sm:text-sm text-[#735d45] max-w-md mx-auto">
                  Hiện chưa có bản thần chú nào được gán cho vị Phật / Bồ Tát này. Quý Phật tử có thể tham khảo thư viện thần chú tổng hợp.
                </p>
                <div className="pt-2">
                  <Link
                    href="/chu"
                    className="inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-[#8f5a28] text-white text-xs font-semibold shadow-xs hover:bg-[#784617]"
                  >
                    <span>Xem Thư Viện Thần Chú</span>
                    <span className="material-symbols-outlined text-sm">arrow_forward</span>
                  </Link>
                </div>
              </div>
            ) : (
              <>
                {/* Selector Pills if multiple Mantras */}
                {mantras.length > 1 && (
                  <div className="flex flex-wrap items-center justify-center gap-2">
                    {mantras.map((m) => (
                      <button
                        key={m.id}
                        type="button"
                        onClick={() => setSelectedMantraSlug(m.slug)}
                        className={`px-3.5 py-1.5 rounded-full font-serif text-xs sm:text-sm transition-all ${
                          selectedMantraSlug === m.slug
                            ? "bg-[#8f5a28] text-white font-bold shadow-xs"
                            : "bg-[#FAF7F0] text-[#6d573f] hover:text-[#2b1f14] border border-[#dfd2be]"
                        }`}
                      >
                        {m.title}
                      </button>
                    ))}
                  </div>
                )}

                {/* Main Mantra Content Card */}
                {activeMantra && (
                  <div className="bg-[#FAF7F0] rounded-3xl p-6 sm:p-8 md:p-10 border border-[#dfd2be] shadow-sm space-y-6 text-[#2b1f14]">
                    {/* Mantra Title */}
                    <div className="text-center pb-2 border-b border-[#ebdcca]/70 space-y-1">
                      <span className="font-mono text-[10px] uppercase tracking-widest text-[#8c7457]">
                        Mật Chú Oai Lực
                      </span>
                      <h2 className="font-serif text-xl sm:text-2xl md:text-3xl font-bold text-[#2b1f14]">
                        {activeMantra.title}
                      </h2>
                      {activeMantra.original_title && (
                        <p className="font-sans text-xs text-[#735d45] italic">
                          {activeMantra.original_title}
                        </p>
                      )}
                    </div>

                    {/* Chú gốc */}
                    {activeMantra.original_text && (
                      <div className="space-y-2">
                        <div className="flex items-center justify-center gap-2">
                          <span className="h-[1px] w-8 bg-[#d8ccbc]" />
                          <h3 className="font-serif text-xs sm:text-sm font-bold uppercase tracking-wider text-[#8f5a28]">
                            Chú Gốc
                          </h3>
                          <span className="h-[1px] w-8 bg-[#d8ccbc]" />
                        </div>
                        <div className="p-4 sm:p-6 rounded-2xl bg-[#f5ede1] border border-[#ebdcca] text-center font-serif text-base sm:text-lg leading-relaxed text-[#2b1f14] whitespace-pre-line tracking-wide shadow-2xs">
                          {activeMantra.original_text}
                        </div>
                      </div>
                    )}

                    {/* Phiên âm tiếng Việt */}
                    {activeMantra.vietnamese_transcription && (
                      <div className="space-y-2">
                        <div className="flex items-center justify-center gap-2">
                          <span className="h-[1px] w-8 bg-[#d8ccbc]" />
                          <h3 className="font-serif text-xs sm:text-sm font-bold uppercase tracking-wider text-[#8f5a28]">
                            Phiên Âm Tiếng Việt
                          </h3>
                          <span className="h-[1px] w-8 bg-[#d8ccbc]" />
                        </div>
                        <div className="p-4 sm:p-6 rounded-2xl bg-[#fbf8f2] border border-[#ebdcca] text-center font-serif text-base sm:text-lg leading-relaxed text-[#382716] whitespace-pre-line tracking-wide">
                          {activeMantra.vietnamese_transcription}
                        </div>
                      </div>
                    )}

                    {/* Mô tả ngắn / Ý nghĩa */}
                    {activeMantra.short_description && (
                      <div className="pt-2 text-center max-w-lg mx-auto">
                        <p className="font-sans text-xs sm:text-sm text-[#6d573f] leading-relaxed">
                          {activeMantra.short_description}
                        </p>
                      </div>
                    )}

                    {/* Integrated Audio Chant Player */}
                    <div className="pt-4 flex flex-col items-center justify-center">
                      <MantraChantPlayer
                        audioUrl={resolveMediaUrl(activeMantra.audio_url)}
                        mantraTitle={activeMantra.title}
                      />
                    </div>
                  </div>
                )}
              </>
            )}
          </div>
        )}

        {/* 4. Related Sutras */}
        <div className="pt-4">
          <RelatedSutras figureName={figure.name} sutras={figure.sutras} />
        </div>
      </main>
    </div>
  );
}
