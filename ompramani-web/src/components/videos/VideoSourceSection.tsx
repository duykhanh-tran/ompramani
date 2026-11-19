import React from "react";
import { VideoDetail } from "@/types/video";

interface VideoSourceSectionProps {
  video: VideoDetail;
}

export function VideoSourceSection({ video }: VideoSourceSectionProps) {
  return (
    <section
      aria-labelledby="source-section-heading"
      className="bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant/40 space-y-4 shadow-xs"
    >
      <div className="flex items-center justify-between gap-2 border-b border-outline-variant/30 pb-3 flex-wrap">
        <h2
          id="source-section-heading"
          className="font-headline-sm text-base sm:text-lg font-semibold text-on-surface flex items-center gap-2"
        >
          <span className="material-symbols-outlined text-primary text-[20px]">
            source
          </span>
          Nguồn Video &amp; Bản Quyền
        </h2>

        <span className="px-2.5 py-0.5 rounded-full text-xs font-medium bg-surface-container-high text-on-surface-variant">
          {video.copyright_status_label}
        </span>
      </div>

      <div className="space-y-3 font-body-sm text-sm text-on-surface">
        {video.source_channel_name && (
          <div className="flex items-start gap-2">
            <span className="material-symbols-outlined text-primary text-[18px] shrink-0 mt-0.5">
              smart_display
            </span>
            <div>
              <span className="text-xs text-on-surface-variant block font-medium">
                Đơn vị / Kênh phát hành:
              </span>
              <span className="font-semibold">{video.source_channel_name}</span>
            </div>
          </div>
        )}

        {video.source_title && (
          <div className="flex items-start gap-2">
            <span className="material-symbols-outlined text-primary text-[18px] shrink-0 mt-0.5">
              subtitles
            </span>
            <div>
              <span className="text-xs text-on-surface-variant block font-medium">
                Tiêu đề tại nguồn gốc:
              </span>
              <span>{video.source_title}</span>
            </div>
          </div>
        )}

        <div className="flex items-start gap-2">
          <span className="material-symbols-outlined text-primary text-[18px] shrink-0 mt-0.5">
            link
          </span>
          <div className="min-w-0 flex-1">
            <span className="text-xs text-on-surface-variant block font-medium">
              Đường dẫn video chính thức:
            </span>
            <a
              href={video.canonical_source_url}
              target="_blank"
              rel="noopener noreferrer"
              className="text-primary hover:underline text-xs sm:text-sm font-medium inline-flex items-center gap-1 break-all pt-0.5"
            >
              <span>{video.canonical_source_url}</span>
              <span className="material-symbols-outlined text-[13px] shrink-0">
                open_in_new
              </span>
            </a>
          </div>
        </div>
      </div>

      <div className="pt-2 border-t border-outline-variant/20 text-[11px] font-body-xs text-on-surface-variant/80 italic leading-relaxed">
        OmPraMani biên tập và tuyển chọn nội dung trên tinh thần tôn trọng quyền tác giả và quy định của nhà cung cấp nội dung gốc.
      </div>
    </section>
  );
}
