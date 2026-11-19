import React from "react";
import Link from "next/link";
import { RelatedBuddhistContent } from "@/types/api";

interface RelatedBuddhistContentProps {
  items?: RelatedBuddhistContent[];
}

export function RelatedBuddhistContentSection({
  items,
}: RelatedBuddhistContentProps) {
  if (!items || items.length === 0) {
    return null;
  }

  const getItemDetails = (item: RelatedBuddhistContent) => {
    switch (item.type) {
      case "sutra":
        return {
          href: `/kinh/${item.slug}`,
          typeLabel: "Kinh Điển",
          icon: "menu_book",
          badgeBg: "bg-amber-100 text-amber-800 border-amber-200",
          iconColor: "text-amber-700",
        };
      case "mantra":
        return {
          href: `/chu/${item.slug}`,
          typeLabel: "Thần Chú",
          icon: "graphic_eq",
          badgeBg: "bg-indigo-100 text-indigo-800 border-indigo-200",
          iconColor: "text-indigo-700",
        };
      case "figure":
        return {
          href: `/phat-bo-tat/${item.slug}`,
          typeLabel: "Phật · Bồ Tát",
          icon: "self_improvement",
          badgeBg: "bg-emerald-100 text-emerald-800 border-emerald-200",
          iconColor: "text-emerald-700",
        };
      default:
        return {
          href: `/kinh/${item.slug}`,
          typeLabel: "Phật Pháp",
          icon: "auto_stories",
          badgeBg: "bg-stone-100 text-stone-800 border-stone-200",
          iconColor: "text-stone-700",
        };
    }
  };

  return (
    <section className="max-w-[780px] mx-auto w-full mt-12 pt-8 border-t border-outline-variant/30">
      <div className="flex items-center gap-2 mb-4">
        <span className="material-symbols-outlined text-primary text-[22px]">
          temple_buddhist
        </span>
        <h3 className="font-headline-md text-lg font-bold text-on-surface">
          Nội Dung Phật Giáo Liên Quan
        </h3>
      </div>
      <p className="font-body-sm text-xs text-on-surface-variant mb-4">
        Các bản Kinh, Chú hoặc danh hiệu Chư Phật & Bồ Tát được nhắc đến trong bài viết:
      </p>

      <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
        {items.map((item, idx) => {
          const details = getItemDetails(item);
          return (
            <Link
              key={`${item.type}-${item.id}-${idx}`}
              href={details.href}
              className="group p-4 rounded-xl bg-surface-container-low hover:bg-surface-container border border-outline-variant/40 hover:border-primary/50 transition-all flex items-center justify-between gap-3 shadow-2xs hover:shadow-xs"
            >
              <div className="flex items-center gap-3 min-w-0">
                <div className={`w-10 h-10 rounded-lg flex items-center justify-center shrink-0 bg-surface-container-lowest border border-outline-variant/30 ${details.iconColor}`}>
                  <span className="material-symbols-outlined text-[22px]">
                    {details.icon}
                  </span>
                </div>
                <div className="min-w-0">
                  <span className={`inline-block px-2 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wider border ${details.badgeBg}`}>
                    {details.typeLabel}
                  </span>
                  <h4 className="font-headline-sm text-sm font-semibold text-on-surface group-hover:text-primary transition-colors line-clamp-1 mt-1">
                    {item.title}
                  </h4>
                </div>
              </div>

              <span className="material-symbols-outlined text-outline group-hover:text-primary group-hover:translate-x-1 transition-all text-[18px] shrink-0">
                arrow_forward
              </span>
            </Link>
          );
        })}
      </div>
    </section>
  );
}
