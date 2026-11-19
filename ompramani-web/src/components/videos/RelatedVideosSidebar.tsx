import React from "react";
import Link from "next/link";
import Image from "next/image";
import { VideoSummary } from "@/types/video";

interface RelatedVideosSidebarProps {
  videos?: VideoSummary[];
  currentSlug?: string;
}

export function RelatedVideosSidebar({
  videos,
  currentSlug,
}: RelatedVideosSidebarProps) {
  const filtered = (videos || []).filter((v) => v.slug !== currentSlug);

  if (filtered.length === 0) {
    return null;
  }

  return (
    <aside className="w-full space-y-4">
      <div className="border-b border-outline-variant/40 pb-3">
        <span className="font-label-caps text-xs text-primary uppercase tracking-wider block font-semibold mb-1">
          Gợi ý bài giảng
        </span>
        <h2 className="font-headline-sm text-base sm:text-lg font-bold text-on-surface font-serif">
          Video Giảng Liên Quan
        </h2>
      </div>

      <div className="flex flex-col divide-y divide-outline-variant/30">
        {filtered.slice(0, 6).map((video) => (
          <Link
            key={video.public_id || video.slug}
            href={`/video-phat-phap/${video.slug}`}
            className="group py-3.5 first:pt-0 last:pb-0 flex flex-col gap-2 transition-colors"
          >
            <div className="relative aspect-video w-full rounded-xl overflow-hidden bg-surface-container-low shadow-2xs border border-outline-variant/30">
              {video.thumbnail_url ? (
                <Image
                  src={video.thumbnail_url}
                  alt={video.title}
                  fill
                  sizes="(max-width: 1024px) 100vw, 320px"
                  className="object-cover group-hover:scale-104 transition-transform duration-300"
                />
              ) : (
                <div className="w-full h-full flex items-center justify-center bg-surface-container text-primary/40">
                  <span className="material-symbols-outlined text-[32px]">
                    ondemand_video
                  </span>
                </div>
              )}

              {/* Play icon overlay */}
              <div className="absolute inset-0 bg-black/20 group-hover:bg-black/35 transition-colors flex items-center justify-center">
                <div className="w-9 h-9 rounded-full bg-primary/90 text-on-primary flex items-center justify-center shadow-md group-hover:scale-110 transition-transform">
                  <span className="material-symbols-outlined text-[20px] ml-0.5">
                    play_arrow
                  </span>
                </div>
              </div>

              {/* Duration badge */}
              {video.duration_formatted && (
                <span className="absolute bottom-1.5 right-1.5 px-1.5 py-0.5 rounded text-[10px] font-mono font-medium bg-black/75 text-white backdrop-blur-xs">
                  {video.duration_formatted}
                </span>
              )}
            </div>

            <div className="space-y-1">
              <h3 className="font-serif font-semibold text-sm leading-snug text-on-surface group-hover:text-primary transition-colors line-clamp-2">
                {video.title}
              </h3>

              {video.speaker_name && (
                <p className="text-xs text-on-surface-variant flex items-center gap-1">
                  <span className="material-symbols-outlined text-[13px] text-primary">
                    record_voice_over
                  </span>
                  <span className="truncate">{video.speaker_name}</span>
                </p>
              )}
            </div>
          </Link>
        ))}
      </div>
    </aside>
  );
}
