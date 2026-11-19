"use client";

import React, { useState } from "react";
import Image from "next/image";
import { VideoDetail } from "@/types/video";

interface VideoWatchAreaProps {
  video: VideoDetail;
}

export function VideoWatchArea({ video }: VideoWatchAreaProps) {
  const [isPlaying, setIsPlaying] = useState<boolean>(false);

  const isApprovedEmbed =
    video.playback_mode === "approved_embed" && Boolean(video.embed_url);

  const isExternalOnly = video.playback_mode === "external_link_only";
  const isUnavailable = video.playback_mode === "unavailable";

  if (isUnavailable) {
    return (
      <div className="w-full aspect-video rounded-3xl bg-surface-container-low border border-outline-variant/40 flex flex-col items-center justify-center p-6 text-center space-y-3">
        <span className="material-symbols-outlined text-[48px] text-outline">
          videocam_off
        </span>
        <h3 className="font-headline-sm text-base sm:text-lg font-semibold text-on-surface">
          Video hiện không khả dụng
        </h3>
        <p className="font-body-sm text-xs sm:text-sm text-on-surface-variant max-w-md">
          Nội dung video này hiện đã tạm ngưng phát hoặc đang được ban biên tập rà soát lại quyền sử dụng.
        </p>
      </div>
    );
  }

  // Active Embedded Iframe
  if (isPlaying && isApprovedEmbed && video.embed_url) {
    return (
      <div className="w-full aspect-video rounded-3xl overflow-hidden border border-outline-variant/40 shadow-lg bg-black relative">
        <iframe
          src={`${video.embed_url}?autoplay=1`}
          title={video.title}
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
          allowFullScreen
          className="w-full h-full border-0"
        />
      </div>
    );
  }

  // Click-to-load / Consent Poster
  return (
    <div className="w-full aspect-video rounded-3xl overflow-hidden border border-outline-variant/40 shadow-md relative bg-surface-container-low group">
      {/* Background Poster Image */}
      {video.thumbnail_url ? (
        <Image
          src={video.thumbnail_url}
          alt={video.title}
          fill
          priority
          className="object-cover"
          sizes="(max-width: 1024px) 100vw, 1024px"
        />
      ) : (
        <div className="w-full h-full flex flex-col items-center justify-center bg-radial from-surface-container-high/60 to-surface-container-low text-primary/40 space-y-2 p-6 text-center">
          <span className="material-symbols-outlined text-[64px]">
            ondemand_video
          </span>
          <span className="text-xs text-on-surface-variant/70 italic">
            {video.title}
          </span>
        </div>
      )}

      {/* Dark overlay for contrast */}
      <div className="absolute inset-0 bg-black/45 group-hover:bg-black/55 transition-colors" />

      {/* Badges top */}
      <div className="absolute top-4 left-4 right-4 flex items-center justify-between pointer-events-none">
        <span className="px-3 py-1 rounded-full text-xs font-medium bg-black/70 text-white backdrop-blur-xs">
          Nền tảng: {video.provider_label}
        </span>

        {video.duration_formatted && (
          <span className="px-3 py-1 rounded-full text-xs font-mono font-medium bg-black/70 text-white backdrop-blur-xs">
            {video.duration_formatted}
          </span>
        )}
      </div>

      {/* Center Action Button */}
      <div className="absolute inset-0 flex flex-col items-center justify-center p-6 text-center text-white space-y-3">
        {isApprovedEmbed ? (
          <>
            <button
              type="button"
              onClick={() => setIsPlaying(true)}
              className="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-primary text-on-primary flex items-center justify-center shadow-xl hover:scale-108 hover:bg-primary/95 transition-all cursor-pointer focus:outline-hidden focus:ring-4 focus:ring-white/40"
              aria-label={`Bắt đầu phát video: ${video.title}`}
            >
              <span className="material-symbols-outlined text-[36px] sm:text-[44px] ml-1">
                play_arrow
              </span>
            </button>
            <div className="space-y-1">
              <p className="font-headline-sm text-base sm:text-lg font-bold font-serif drop-shadow-xs max-w-xl line-clamp-1">
                {video.title}
              </p>
              <p className="text-xs text-white/80 font-label-sm max-w-md">
                Phát qua {video.provider_label} (Chế độ tăng cường bảo vệ quyền riêng tư)
              </p>
            </div>
          </>
        ) : isExternalOnly ? (
          <div className="space-y-4 max-w-md">
            <div className="w-14 h-14 mx-auto rounded-full bg-surface-container-highest/80 text-primary flex items-center justify-center">
              <span className="material-symbols-outlined text-[30px]">
                open_in_new
              </span>
            </div>
            <div className="space-y-1">
              <h3 className="font-headline-sm text-base sm:text-lg font-bold font-serif text-white">
                Xem tại kênh phát hành chính thức
              </h3>
              <p className="text-xs text-white/80 leading-relaxed">
                Video này được nhà xuất bản bảo hộ bản quyền và xem trực tiếp trên nền tảng nguồn.
              </p>
            </div>
            <div>
              <a
                href={video.canonical_source_url}
                target="_blank"
                rel="noopener noreferrer"
                className="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-primary text-on-primary font-label-md text-sm font-semibold hover:bg-primary/90 transition-colors shadow-lg cursor-pointer"
              >
                <span>Xem video trên {video.provider_label}</span>
                <span className="material-symbols-outlined text-[16px]">
                  open_in_new
                </span>
              </a>
            </div>
          </div>
        ) : null}
      </div>
    </div>
  );
}
