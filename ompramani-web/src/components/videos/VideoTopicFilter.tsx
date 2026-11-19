"use client";

import React from "react";
import { VideoTopicItem } from "@/types/video";

interface VideoTopicFilterProps {
  topics: VideoTopicItem[];
  selectedTopic?: string;
  selectedSort?: string;
  onSelectTopic: (topicSlug?: string) => void;
  onSelectSort: (sort?: "newest" | "oldest" | "duration_desc" | "duration_asc") => void;
}

export function VideoTopicFilter({
  topics,
  selectedTopic,
  selectedSort = "newest",
  onSelectTopic,
  onSelectSort,
}: VideoTopicFilterProps) {
  return (
    <div className="flex items-center justify-between gap-3 flex-wrap">
      {/* Topics dropdown if available */}
      <div className="flex items-center gap-2 flex-wrap">
        {topics.length > 0 && (
          <div className="flex items-center gap-1.5">
            <span className="text-xs text-on-surface-variant font-medium">Chủ đề:</span>
            <select
              value={selectedTopic || ""}
              onChange={(e) => onSelectTopic(e.target.value || undefined)}
              aria-label="Lọc theo chủ đề tu học"
              className="px-3 py-1.5 bg-surface-container-lowest text-on-surface rounded-xl border border-outline-variant/60 text-xs font-label-md focus:border-primary focus:outline-hidden cursor-pointer shadow-xs"
            >
              <option value="">Tất cả chủ đề</option>
              {topics.map((t) => (
                <option key={t.id} value={t.slug}>
                  {t.name}
                </option>
              ))}
            </select>
          </div>
        )}
      </div>

      {/* Sort options */}
      <div className="flex items-center gap-1.5">
        <span className="text-xs text-on-surface-variant font-medium">Sắp xếp:</span>
        <select
          value={selectedSort}
          onChange={(e) =>
            onSelectSort(
              (e.target.value as "newest" | "oldest" | "duration_desc" | "duration_asc") || "newest"
            )
          }
          aria-label="Sắp xếp danh sách video"
          className="px-3 py-1.5 bg-surface-container-lowest text-on-surface rounded-xl border border-outline-variant/60 text-xs font-label-md focus:border-primary focus:outline-hidden cursor-pointer shadow-xs"
        >
          <option value="newest">Mới nhất</option>
          <option value="oldest">Cũ nhất</option>
          <option value="duration_desc">Thời lượng dài nhất</option>
          <option value="duration_asc">Thời lượng ngắn nhất</option>
        </select>
      </div>
    </div>
  );
}
