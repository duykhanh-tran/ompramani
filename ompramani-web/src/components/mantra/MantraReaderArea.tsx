"use client";

import React, { useState, useEffect } from "react";
import { MantraVersion } from "@/types/api";
import { parseSafeContent } from "@/lib/utils/sacred";

interface MantraReaderAreaProps {
  version: MantraVersion;
  practiceGuidance?: string | null;
}

export function MantraReaderArea({
  version,
  practiceGuidance,
}: MantraReaderAreaProps) {
  const [fontSizeLevel, setFontSizeLevel] = useState<number>(1);
  const [copiedField, setCopiedField] = useState<
    "content" | "transliteration" | null
  >(null);

  useEffect(() => {
    try {
      const saved = localStorage.getItem("ompramani_reading_fontsize");
      if (saved !== null) {
        const level = parseInt(saved, 10);
        if (!isNaN(level) && level >= 0 && level <= 4) {
          // eslint-disable-next-line react-hooks/set-state-in-effect
          setFontSizeLevel(level);
        }
      }
    } catch {
      // Ignore localStorage errors
    }
  }, []);

  const fontSizes = [
    "text-[17px] leading-[32px]",
    "text-[19px] leading-[36px]",
    "text-[21px] leading-[40px]",
    "text-[23px] leading-[44px]",
    "text-[26px] leading-[48px]",
  ];

  const changeFontSize = (delta: number) => {
    setFontSizeLevel((prev) => {
      const next = Math.max(0, Math.min(4, prev + delta));
      try {
        localStorage.setItem("ompramani_reading_fontsize", next.toString());
      } catch {
        // Ignore
      }
      return next;
    });
  };

  const handleCopySacredText = (
    text: string,
    field: "content" | "transliteration"
  ) => {
    if (typeof window !== "undefined" && text) {
      // Copy exact verbatim text without mutating a single character
      navigator.clipboard.writeText(text);
      setCopiedField(field);
      setTimeout(() => {
        setCopiedField((current) => (current === field ? null : current));
      }, 2000);
    }
  };

  // Preserve exact sacred text lines without altering Unicode, diacritics, or punctuation
  const contentLines = (version.content || "")
    .split(/\r?\n/)
    .filter((line) => line.length > 0);

  const hasTransliteration = Boolean(
    version.transliteration && version.transliteration.trim().length > 0
  );
  const transliterationLines = hasTransliteration
    ? (version.transliteration || "")
        .split(/\r?\n/)
        .filter((line) => line.length > 0)
    : [];

  const hasTranslation = Boolean(
    version.translation && version.translation.trim().length > 0
  );
  const translationLines = hasTranslation
    ? (version.translation || "")
        .split(/\r?\n/)
        .filter((line) => line.length > 0)
    : [];

  const hasSource = Boolean(version.source && version.source.trim().length > 0);
  const hasNotes = Boolean(version.notes && version.notes.trim().length > 0);
  const hasPracticeGuidance = Boolean(
    practiceGuidance && practiceGuidance.trim().length > 0
  );
  const parsedPracticeGuidance = parseSafeContent(practiceGuidance);

  return (
    <div id="mantra-reader" className="space-y-8 scroll-mt-24">
      {/* Main Sacred Reader Card */}
      <article className="bg-surface-container-lowest rounded-3xl p-6 sm:p-10 border border-outline-variant/30 shadow-sm space-y-8">
        {/* Reader Header & Font Size Toolbar */}
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-outline-variant/30">
          <div>
            <div className="flex flex-wrap items-center gap-2 mb-1">
              <span className="w-2 h-2 rounded-full bg-primary" />
              <span className="font-label-caps text-label-caps text-primary uppercase">
                Mật Điển Trì Tụng
              </span>
              {version.script && (
                <span className="text-[11px] font-label-md text-outline">
                  · Văn tự: {version.script}
                </span>
              )}
              {version.language && (
                <span className="text-[11px] font-label-md text-outline uppercase">
                  · {version.language}
                </span>
              )}
            </div>
            <h2 className="font-headline-xl text-2xl sm:text-3xl font-bold text-on-surface">
              {version.name}
            </h2>
          </div>

          {/* Font Size Controls */}
          <div className="flex items-center gap-2 shrink-0 self-start sm:self-auto bg-surface-container-low p-1.5 rounded-2xl border border-outline-variant/40">
            <button
              type="button"
              suppressHydrationWarning
              onClick={() => changeFontSize(-1)}
              disabled={fontSizeLevel === 0}
              className="w-8 h-8 rounded-xl flex items-center justify-center font-bold text-on-surface hover:bg-surface-container hover:text-primary disabled:opacity-30 disabled:cursor-not-allowed transition-colors cursor-pointer"
              title="Giảm cỡ chữ"
            >
              A-
            </button>
            <span className="text-[12px] font-label-md text-outline px-1">
              {17 + fontSizeLevel * 2}px
            </span>
            <button
              type="button"
              suppressHydrationWarning
              onClick={() => changeFontSize(1)}
              disabled={fontSizeLevel === 4}
              className="w-8 h-8 rounded-xl flex items-center justify-center font-bold text-on-surface hover:bg-surface-container hover:text-primary disabled:opacity-30 disabled:cursor-not-allowed transition-colors cursor-pointer"
              title="Tăng cỡ chữ"
            >
              A+
            </button>
          </div>
        </div>

        {/* 1. NGUYÊN VĂN (content) */}
        <section aria-label="Nguyên văn thần chú" className="space-y-4">
          <div className="max-w-[800px] mx-auto flex items-center justify-between gap-2">
            <div className="flex items-center gap-2">
              <span className="material-symbols-outlined text-primary text-[20px]">
                auto_awesome
              </span>
              <h3 className="font-headline-md text-lg font-semibold text-on-surface">
                Nguyên Văn
              </h3>
            </div>

            {version.content && (
              <button
                type="button"
                suppressHydrationWarning
                onClick={() =>
                  handleCopySacredText(version.content, "content")
                }
                className="px-3.5 py-1.5 rounded-xl bg-surface-container-low hover:bg-surface-container text-on-surface hover:text-primary transition-colors font-label-md text-[12px] flex items-center gap-1.5 border border-outline-variant/40 cursor-pointer"
                title="Sao chép nguyên văn thần chú"
              >
                <span className="material-symbols-outlined text-[16px]">
                  {copiedField === "content" ? "check" : "content_copy"}
                </span>
                <span>
                  {copiedField === "content"
                    ? "Đã sao chép"
                    : "Sao chép văn bản"}
                </span>
              </button>
            )}
          </div>

          <div className="max-w-[800px] mx-auto bg-gradient-to-br from-surface-container-low/80 via-surface to-secondary-container/15 rounded-2xl p-6 sm:p-8 border border-outline-variant/40 shadow-xs">
            <div
              className={`font-serif text-on-surface font-normal tracking-normal break-words transition-all duration-200 space-y-4 ${fontSizes[fontSizeLevel]}`}
              spellCheck={false}
              translate="no"
            >
              {contentLines.map((line, idx) => (
                <p key={idx} className="whitespace-pre-wrap">
                  {line}
                </p>
              ))}
            </div>
          </div>
        </section>

        {/* 2. PHIÊN ÂM (transliteration) — Separate from content */}
        {hasTransliteration && version.transliteration && (
          <section
            aria-label="Phiên âm thần chú"
            className="space-y-4 pt-6 border-t border-outline-variant/30"
          >
            <div className="max-w-[800px] mx-auto flex items-center justify-between gap-2">
              <div className="flex items-center gap-2">
                <span className="material-symbols-outlined text-primary text-[20px]">
                  record_voice_over
                </span>
                <h3 className="font-headline-md text-lg font-semibold text-on-surface">
                  Phiên Âm
                </h3>
              </div>

              <button
                type="button"
                suppressHydrationWarning
                onClick={() =>
                  handleCopySacredText(
                    version.transliteration || "",
                    "transliteration"
                  )
                }
                className="px-3.5 py-1.5 rounded-xl bg-surface-container-low hover:bg-surface-container text-on-surface hover:text-primary transition-colors font-label-md text-[12px] flex items-center gap-1.5 border border-outline-variant/40 cursor-pointer"
                title="Sao chép bản phiên âm"
              >
                <span className="material-symbols-outlined text-[16px]">
                  {copiedField === "transliteration" ? "check" : "content_copy"}
                </span>
                <span>
                  {copiedField === "transliteration"
                    ? "Đã sao chép"
                    : "Sao chép phiên âm"}
                </span>
              </button>
            </div>

            <div className="max-w-[800px] mx-auto bg-surface-container-low/50 rounded-2xl p-6 sm:p-8 border border-outline-variant/30">
              <div
                className={`font-serif text-on-surface font-normal tracking-normal break-words transition-all duration-200 space-y-4 ${fontSizes[fontSizeLevel]}`}
                spellCheck={false}
                translate="no"
              >
                {transliterationLines.map((line, idx) => (
                  <p key={idx} className="whitespace-pre-wrap">
                    {line}
                  </p>
                ))}
              </div>
            </div>
          </section>
        )}

        {/* 3. DỊCH NGHĨA / GIẢI NGHĨA (translation) */}
        {hasTranslation && version.translation && (
          <section
            aria-label="Dịch nghĩa và giải nghĩa"
            className="space-y-4 pt-6 border-t border-outline-variant/30"
          >
            <div className="max-w-[800px] mx-auto flex items-center gap-2">
              <span className="material-symbols-outlined text-primary text-[20px]">
                translate
              </span>
              <h3 className="font-headline-md text-lg font-semibold text-on-surface">
                Dịch Nghĩa / Giải Nghĩa
              </h3>
            </div>

            <div className="max-w-[800px] mx-auto bg-surface rounded-2xl p-6 sm:p-8 border border-outline-variant/30">
              <div className="font-body-lg text-[16px] sm:text-[17px] leading-[30px] text-on-surface-variant break-words space-y-4">
                {translationLines.map((line, idx) => (
                  <p key={idx} className="whitespace-pre-wrap">
                    {line}
                  </p>
                ))}
              </div>
            </div>
          </section>
        )}

        {/* 4. SOURCE & NOTES (source / notes) */}
        {(hasSource || hasNotes) && (
          <footer className="max-w-[800px] mx-auto pt-6 border-t border-outline-variant/30 space-y-3 text-sm text-on-surface-variant">
            {hasSource && version.source && (
              <div className="flex items-start gap-2">
                <span className="material-symbols-outlined text-primary text-[18px] mt-0.5 shrink-0">
                  library_books
                </span>
                <div>
                  <span className="font-semibold text-on-surface">
                    Xuất xứ:{" "}
                  </span>
                  <span>{version.source}</span>
                </div>
              </div>
            )}

            {hasNotes && version.notes && (
              <div className="flex items-start gap-2">
                <span className="material-symbols-outlined text-outline text-[18px] mt-0.5 shrink-0">
                  info
                </span>
                <div>
                  <span className="font-semibold text-on-surface">
                    Ghi chú bản dịch:{" "}
                  </span>
                  <span className="whitespace-pre-wrap">{version.notes}</span>
                </div>
              </div>
            )}
          </footer>
        )}
      </article>

      {/* 5. PRACTICE GUIDANCE (mantra.practice_guidance) */}
      {hasPracticeGuidance && practiceGuidance && (
        <section
          aria-label="Hướng dẫn hành trì thần chú"
          className="bg-surface-container-lowest rounded-3xl p-6 sm:p-10 border border-outline-variant/30 shadow-sm space-y-5"
        >
          <div className="flex items-center gap-3 pb-4 border-b border-outline-variant/30">
            <div className="w-10 h-10 rounded-xl bg-surface-container flex items-center justify-center text-primary shrink-0">
              <span className="material-symbols-outlined text-[22px]">
                self_improvement
              </span>
            </div>
            <div>
              <span className="font-label-caps text-label-caps text-primary uppercase block">
                Nghi Quỹ Hành Trì
              </span>
              <h2 className="font-headline-lg text-xl sm:text-2xl font-bold text-on-surface">
                Hướng Dẫn Hành Trì
              </h2>
            </div>
          </div>

          {parsedPracticeGuidance.isHtml ? (
            <div
              className="max-w-[780px] font-body-lg text-[16px] sm:text-[17px] leading-[30px] text-on-surface-variant space-y-4 break-words"
              dangerouslySetInnerHTML={{
                __html: parsedPracticeGuidance.sanitizedHtml,
              }}
            />
          ) : (
            <div className="max-w-[780px] font-body-lg text-[16px] sm:text-[17px] leading-[30px] text-on-surface-variant space-y-4 break-words">
              {parsedPracticeGuidance.paragraphs.map((p, idx) => (
                <p key={idx} className="text-justify">
                  {p}
                </p>
              ))}
            </div>
          )}
        </section>
      )}
    </div>
  );
}
