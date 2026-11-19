"use client";

import React from "react";
import Link from "next/link";
import Image from "next/image";
import { VideoSummary } from "@/types/video";
import { FavoriteButton } from "@/components/favorites/FavoriteButton";

interface VideoCardProps {
  video: VideoSummary;
}

export function VideoCard({ video }: VideoCardProps) {
  return (
    <article className="group bg-surface-container-lowest rounded-2xl border border-outline-variant/40 overflow-hidden shadow-xs hover:shadow-md hover:border-primary/40 transition-all flex flex-col h-full">
      {/* Thumbnail Area (16:9) */}
      <Link
        href={`/video-phat-phap/${video.slug}`}
        className="relative aspect-video w-full overflow-hidden bg-surface-container-low block focus:outline-hidden focus:ring-2 focus:ring-primary"
        aria-label={`Xem video: ${video.title}`}
      >
        {video.thumbnail_url ? (
          <Image
            src={video.thumbnail_url}
            alt={`Ảnh thu nhỏ video: ${video.title}`}
            fill
            className="object-cover group-hover:scale-105 transition-transform duration-300"
            sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw"
          />
        ) : (
          <div className="w-full h-full flex flex-col items-center justify-center bg-radial from-surface-container-high/40 to-surface-container-low text-primary/40 space-y-1 p-4">
            <span className="material-symbols-outlined text-[44px]">
              play_circle
            </span>
            <span className="text-[11px] font-label-sm text-on-surface-variant/70 italic">
              Video Phật Pháp
            </span>
          </div>
        )}

        {/* Play Icon overlay */}
        <div className="absolute inset-0 bg-black/20 group-hover:bg-black/35 transition-colors flex items-center justify-center">
          <div className="w-11 h-11 rounded-full bg-primary/90 text-on-primary flex items-center justify-center shadow-md group-hover:scale-110 transition-transform">
            <span className="material-symbols-outlined text-[26px] ml-0.5">
              play_arrow
            </span>
          </div>
        </div>

        {/* Duration badge */}
        {video.duration_formatted && (
          <span className="absolute bottom-2 right-2 z-10 px-2 py-0.5 rounded-md text-[11px] font-mono font-medium bg-black/75 text-white backdrop-blur-xs">
            {video.duration_formatted}
          </span>
        )}

        {/* Provider badge */}
        <span className="absolute top-2 left-2 z-10 px-2 py-0.5 rounded-md text-[10px] font-medium bg-black/60 text-white backdrop-blur-xs">
          {video.provider_label}
        </span>

        {/* Favorite Button on top right */}
        <div
          className="absolute top-2 right-2 z-10"
          onClick={(e) => {
            e.preventDefault();
            e.stopPropagation();
          }}
        >
          <FavoriteButton
            contentType="video"
            contentPublicId={video.slug}
            variant="compact"
          />
        </div>
      </Link>

      {/* Content Area */}
      <div className="p-4 sm:p-5 flex-1 flex flex-col justify-between space-y-3">
        <div className="space-y-2">
          {/* Category & Topic badges */}
          <div className="flex items-center gap-1.5 flex-wrap">
            {video.category_label && video.category !== "other" && (
              <span className="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-primary/10 text-primary">
                {video.category_label}
              </span>
            )}

            {video.playback_mode === "external_link_only" && (
              <span className="px-2 py-0.5 rounded-full text-[10px] font-medium bg-surface-container text-on-surface-variant flex items-center gap-0.5">
                <span className="material-symbols-outlined text-[12px]">open_in_new</span>
                Nguồn ngoài
              </span>
            )}
          </div>

          {/* Title */}
          <h3 className="font-headline-sm text-base sm:text-lg font-bold font-serif text-on-surface line-clamp-2 group-hover:text-primary transition-colors leading-snug">
            <Link href={`/video-phat-phap/${video.slug}`}>
              {video.title}
            </Link>
          </h3>

          {/* Short description */}
          {video.short_description && (
            <p className="font-body-sm text-xs sm:text-sm text-on-surface-variant line-clamp-2 leading-relaxed">
              {video.short_description}
            </p>
          )}
        </div>

        {/* Speaker & Date Footer */}
        <div className="pt-3 border-t border-outline-variant/30 flex items-center justify-between text-xs text-on-surface-variant gap-2">
          {video.speaker_name ? (
            <span className="font-medium text-on-surface line-clamp-1 flex items-center gap-1" title={video.speaker_name}>
              <span className="material-symbols-outlined text-[15px] text-primary">
                record_voice_over
              </span>
              <span>{video.speaker_name}</span>
            </span>
          ) : (
            <span className="text-on-surface-variant/70 text-[11px]">{video.provider_label}</span>
          )}

          {video.published_at && (
            <span className="shrink-0 text-[11px]">
              {new Date(video.published_at).toLocaleDateString("vi-VN")}
            </span>
          )}
        </div>
      </div>
    </article>
  );
}
