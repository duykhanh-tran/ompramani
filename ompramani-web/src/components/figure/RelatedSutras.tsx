import React from "react";
import Link from "next/link";
import { Sutra } from "@/types/api";

interface RelatedSutrasProps {
  figureName: string;
  sutras?: Sutra[];
}

export function RelatedSutras({ figureName, sutras }: RelatedSutrasProps) {
  if (!sutras || sutras.length === 0) {
    return null;
  }

  return (
    <section
      aria-label="Kinh điển liên hệ"
      className="bg-surface-container-lowest rounded-3xl p-6 sm:p-10 border border-outline-variant/30 shadow-sm space-y-6"
    >
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-outline-variant/30">
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary shrink-0">
            <span className="material-symbols-outlined text-[22px]">
              menu_book
            </span>
          </div>
          <div>
            <span className="font-label-caps text-label-caps text-primary uppercase block">
              Pháp Bảo Trì Tụng
            </span>
            <h2 className="font-headline-lg text-xl sm:text-2xl font-bold text-on-surface">
              Kinh Điển Liên Hệ Với {figureName}
            </h2>
          </div>
        </div>

        <Link
          href="/kinh"
          className="font-label-md text-label-md text-primary hover:underline flex items-center gap-1 self-start sm:self-auto"
        >
          <span>Thư viện kinh điển</span>
          <span className="material-symbols-outlined text-[16px]">
            chevron_right
          </span>
        </Link>
      </div>

      <div className={`grid gap-4 ${sutras.length > 1 ? "grid-cols-1 md:grid-cols-2" : "grid-cols-1"}`}>
        {sutras.map((sutra) => (
          <Link
            key={sutra.id}
            href={`/kinh/${sutra.slug}`}
            className="p-5 rounded-2xl bg-surface-container-low/70 hover:bg-surface-container border border-outline-variant/40 transition-all group flex flex-col justify-between gap-3 shadow-xs"
          >
            <div className="space-y-1.5">
              <div className="flex items-center justify-between gap-2">
                <div className="flex items-center gap-2 min-w-0">
                  <span className="material-symbols-outlined text-primary text-[20px] shrink-0">
                    auto_stories
                  </span>
                  <h3 className="font-headline-md text-sm sm:text-base font-semibold text-on-surface group-hover:text-primary transition-colors truncate">
                    {sutra.title}
                  </h3>
                </div>
              </div>

              {sutra.original_title && (
                <p className="font-quote-editorial text-xs text-primary/80 italic pl-7">
                  {sutra.original_title}
                </p>
              )}

              {sutra.short_description && (
                <p className="font-body-sm text-xs text-on-surface-variant line-clamp-2 pl-7 leading-relaxed">
                  {sutra.short_description}
                </p>
              )}
            </div>

            <div className="flex items-center justify-between pt-2 border-t border-outline-variant/20 text-[11px] font-label-md text-outline pl-7">
              <span>{sutra.translator ? `Dịch giả: ${sutra.translator}` : "Kinh điển đại thừa"}</span>
              <span className="text-primary font-medium group-hover:translate-x-0.5 transition-transform flex items-center gap-0.5">
                Đọc kinh
                <span className="material-symbols-outlined text-[14px]">arrow_forward</span>
              </span>
            </div>
          </Link>
        ))}
      </div>
    </section>
  );
}
