import React from "react";
import Link from "next/link";
import Image from "next/image";
import { TempleSummary } from "@/types/temple";
import { resolveMediaUrl } from "@/lib/utils/media";

interface TemplesSectionProps {
  temples?: TempleSummary[];
}

export function TemplesSection({ temples }: TemplesSectionProps) {
  // If no temples exist in backend, do not display the section
  if (!temples || temples.length === 0) {
    return null;
  }

  return (
    <div className="pt-6">
      <div className="flex items-center justify-between mb-6">
        <div>
          <span className="font-label-caps text-label-caps text-primary uppercase">
            Hành Hương Đất Việt
          </span>
          <h2 className="font-headline-xl text-headline-xl text-on-surface font-semibold tracking-tight">
            Danh Lam Chùa Việt Nam
          </h2>
        </div>
        <Link
          href="/tim-chua"
          className="font-label-lg text-label-lg text-primary hover:underline flex items-center gap-1"
        >
          <span>Khám phá danh lam tự viện</span>
          <span className="material-symbols-outlined text-[18px]">
            chevron_right
          </span>
        </Link>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        {temples.map((temple) => {
          const resolvedCover = resolveMediaUrl(temple.cover_image);
          const location = temple.province_name || temple.formatted_address || "Việt Nam";
          const tag = temple.place_type_label || (temple.traditions && temple.traditions[0]?.name) || "Tự viện";
          const description =
            temple.short_description ||
            "Danh lam tự viện thanh tịnh, nơi chư Tăng Ni và Phật tử tu học, chiêm bái.";
          const href = `/tim-chua/${temple.slug}`;

          return (
            <div
              key={temple.public_id || temple.slug}
              className="bg-surface-container-lowest rounded-2xl overflow-hidden shadow-xs hover:shadow-md transition-all flex flex-col group border border-outline-variant/40 hover:border-primary/40"
            >
              {/* Image with Location Tag */}
              <div className="h-56 relative overflow-hidden bg-surface-container-low">
                {resolvedCover ? (
                  <Image
                    src={resolvedCover}
                    alt={temple.name}
                    fill
                    sizes="(max-width: 768px) 100vw, 33vw"
                    className="object-cover group-hover:scale-105 transition-transform duration-500"
                  />
                ) : (
                  <div className="w-full h-full flex items-center justify-center bg-surface-container text-primary">
                    <span className="material-symbols-outlined text-[48px]">temple_buddhist</span>
                  </div>
                )}
                <div className="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent pointer-events-none" />
                <span className="absolute bottom-3 left-3 px-3 py-1 bg-surface-container-lowest/90 backdrop-blur-md rounded-full font-label-md text-xs font-semibold text-on-surface flex items-center gap-1.5 shadow-xs">
                  <span className="material-symbols-outlined text-[16px] text-primary">
                    location_on
                  </span>
                  <span>{location}</span>
                </span>
              </div>

              {/* Content & Action */}
              <div className="p-5 flex flex-col justify-between flex-1 space-y-4">
                <div>
                  <h3 className="font-headline-md text-base sm:text-lg font-bold text-on-surface group-hover:text-primary transition-colors line-clamp-1">
                    {temple.name}
                  </h3>
                  <p className="font-body-sm text-sm text-on-surface-variant mt-1.5 line-clamp-2 leading-relaxed">
                    {description}
                  </p>
                </div>

                <div className="pt-3 flex items-center justify-between font-label-md text-xs border-t border-outline-variant/30">
                  <span className="text-primary font-semibold px-2.5 py-0.5 rounded-full bg-primary/10">
                    {tag}
                  </span>
                  <Link
                    href={href}
                    className="text-primary font-semibold hover:underline flex items-center gap-1 group/btn"
                  >
                    <span>Xem chi tiết</span>
                    <span className="material-symbols-outlined text-[15px] group-hover/btn:translate-x-0.5 transition-transform">
                      arrow_forward
                    </span>
                  </Link>
                </div>
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
}
