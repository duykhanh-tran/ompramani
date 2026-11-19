import React from "react";
import Link from "next/link";
import { Mantra } from "@/types/api";

interface RelatedMantrasProps {
  figureName: string;
  mantras?: Mantra[];
}

export function RelatedMantras({ figureName, mantras }: RelatedMantrasProps) {
  if (!mantras || mantras.length === 0) {
    return null;
  }

  return (
    <section
      aria-label="Thần chú liên hệ"
      className="bg-surface-container-lowest rounded-3xl p-6 sm:p-10 border border-outline-variant/30 shadow-sm space-y-6"
    >
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-outline-variant/30">
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 rounded-xl bg-secondary-container flex items-center justify-center text-on-secondary-container shrink-0">
            <span className="material-symbols-outlined text-[22px]">
              auto_awesome
            </span>
          </div>
          <div>
            <span className="font-label-caps text-label-caps text-primary uppercase block">
              Mật Ngữ Gia Trì
            </span>
            <h2 className="font-headline-lg text-xl sm:text-2xl font-bold text-on-surface">
              Thần Chú Liên Hệ Với {figureName}
            </h2>
          </div>
        </div>

        <Link
          href="/chu"
          className="font-label-md text-label-md text-primary hover:underline flex items-center gap-1 self-start sm:self-auto"
        >
          <span>Thư viện thần chú</span>
          <span className="material-symbols-outlined text-[16px]">
            chevron_right
          </span>
        </Link>
      </div>

      <div className={`grid gap-4 ${mantras.length > 1 ? "grid-cols-1 md:grid-cols-2" : "grid-cols-1"}`}>
        {mantras.map((mantra) => (
          <Link
            key={mantra.id}
            href={`/chu/${mantra.slug}`}
            className="p-5 rounded-2xl bg-surface-container-low/70 hover:bg-surface-container border border-outline-variant/40 transition-all group flex flex-col justify-between gap-3 shadow-xs"
          >
            <div className="space-y-1.5">
              <div className="flex items-center justify-between gap-2">
                <div className="flex items-center gap-2 min-w-0">
                  <span className="material-symbols-outlined text-primary text-[20px] shrink-0">
                    graphic_eq
                  </span>
                  <h3 className="font-headline-md text-lg font-semibold text-on-surface group-hover:text-primary transition-colors truncate">
                    {mantra.title}
                  </h3>
                </div>
                {mantra.tradition && (
                  <span className="px-2.5 py-0.5 rounded-full bg-surface-container-lowest text-primary font-label-md text-[11px] font-medium shrink-0 border border-outline-variant/30">
                    {mantra.tradition.name}
                  </span>
                )}
              </div>

              {mantra.original_title && (
                <p className="font-quote-editorial text-sm text-outline italic truncate">
                  {mantra.original_title}
                </p>
              )}

              {mantra.short_description && (
                <p className="font-body-sm text-body-sm text-on-surface-variant line-clamp-2 pt-1">
                  {mantra.short_description}
                </p>
              )}
            </div>

            <div className="pt-2 border-t border-outline-variant/30 flex items-center justify-between font-label-md text-[12px]">
              <span className="text-outline">Đà La Ni · Chân Ngôn</span>
              <span className="text-primary font-semibold group-hover:underline flex items-center gap-1">
                <span>Đọc &amp; trì tụng</span>
                <span className="material-symbols-outlined text-[16px] group-hover:translate-x-0.5 transition-transform">
                  arrow_forward
                </span>
              </span>
            </div>
          </Link>
        ))}
      </div>
    </section>
  );
}
