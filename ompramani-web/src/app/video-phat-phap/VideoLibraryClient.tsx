"use client";

import React, { useState, useEffect, useRef, useCallback } from "react";
import { useSearchParams, useRouter, usePathname } from "next/navigation";
import Image from "next/image";
import {
  VideoCategory,
  VideoCategoryItem,
  VideoSummary,
  VideoTopicItem,
} from "@/types/video";
import {
  getVideos,
  getVideoCategories,
  getVideoTopics,
} from "@/lib/api/videos";

import { VideoTopicFilter } from "@/components/videos/VideoTopicFilter";
import { VideoCard } from "@/components/videos/VideoCard";
import { VideoPagination } from "@/components/videos/VideoPagination";
import { VideoEmptyState } from "@/components/videos/VideoEmptyState";

export function VideoLibraryClient() {
  const router = useRouter();
  const pathname = usePathname();
  const searchParams = useSearchParams();

  // URL state
  const paramQ = searchParams.get("q") || "";
  const paramCategory = (searchParams.get("category") as VideoCategory) || undefined;
  const paramTopic = searchParams.get("topic") || undefined;
  const paramSpeaker = searchParams.get("speaker") || undefined;
  const paramSort =
    (searchParams.get("sort") as "newest" | "oldest" | "duration_desc" | "duration_asc") ||
    "newest";
  const paramPage = parseInt(searchParams.get("page") || "1", 10) || 1;

  // Local state
  const [categories, setCategories] = useState<VideoCategoryItem[]>([]);
  const [topics, setTopics] = useState<VideoTopicItem[]>([]);
  const [videos, setVideos] = useState<VideoSummary[]>([]);
  const [totalCount, setTotalCount] = useState<number>(0);
  const [currentPage, setCurrentPage] = useState<number>(paramPage);
  const [lastPage, setLastPage] = useState<number>(1);
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [hasError, setHasError] = useState<boolean>(false);

  // Stale request guard
  const requestIdRef = useRef<number>(0);

  // Update URL helper
  const updateUrl = useCallback(
    (newParams: {
      q?: string;
      category?: string;
      topic?: string;
      speaker?: string;
      sort?: string;
      page?: number;
    }) => {
      const sp = new URLSearchParams();

      const q = newParams.q !== undefined ? newParams.q : paramQ;
      const cat = newParams.category !== undefined ? newParams.category : paramCategory;
      const top = newParams.topic !== undefined ? newParams.topic : paramTopic;
      const spk = newParams.speaker !== undefined ? newParams.speaker : paramSpeaker;
      const sort = newParams.sort !== undefined ? newParams.sort : paramSort;
      const page = newParams.page !== undefined ? newParams.page : currentPage;

      if (q && q.trim()) sp.set("q", q.trim());
      if (cat) sp.set("category", cat);
      if (top) sp.set("topic", top);
      if (spk) sp.set("speaker", spk);
      if (sort && sort !== "newest") sp.set("sort", sort);
      if (page && page > 1) sp.set("page", page.toString());

      const qs = sp.toString();
      router.push(`${pathname}${qs ? `?${qs}` : ""}`);
    },
    [router, pathname, paramQ, paramCategory, paramTopic, paramSpeaker, paramSort, currentPage]
  );

  // Load Categories and Topics once
  useEffect(() => {
    getVideoCategories().then(setCategories);
    getVideoTopics().then(setTopics);
  }, []);

  // Main Data Fetcher
  const fetchVideos = useCallback(async () => {
    const currentReqId = ++requestIdRef.current;
    setIsLoading(true);
    setHasError(false);

    try {
      const res = await getVideos({
        q: paramQ || undefined,
        category: paramCategory,
        topic: paramTopic,
        speaker: paramSpeaker,
        sort: paramSort,
        page: paramPage,
        per_page: 12,
      });

      if (currentReqId !== requestIdRef.current) {
        return;
      }

      setVideos(res.data || []);
      setTotalCount(res.meta?.total || 0);
      setCurrentPage(res.meta?.current_page || paramPage);
      setLastPage(res.meta?.last_page || 1);
    } catch (err) {
      if (currentReqId === requestIdRef.current) {
        console.error("Failed to fetch videos:", err);
        setHasError(true);
      }
    } finally {
      if (currentReqId === requestIdRef.current) {
        setIsLoading(false);
      }
    }
  }, [paramQ, paramCategory, paramTopic, paramSpeaker, paramSort, paramPage]);

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    fetchVideos();
  }, [fetchVideos]);

  // Handlers
  const handleSearch = (query: string) => {
    updateUrl({ q: query, page: 1 });
  };

  const handleCategoryChange = (category?: VideoCategory) => {
    updateUrl({ category: category || "", page: 1 });
  };

  const handleTopicChange = (topicSlug?: string) => {
    updateUrl({ topic: topicSlug || "", page: 1 });
  };

  const handleSortChange = (
    sort?: "newest" | "oldest" | "duration_desc" | "duration_asc"
  ) => {
    updateUrl({ sort: sort || "newest", page: 1 });
  };

  const handlePageChange = (page: number) => {
    updateUrl({ page });
    window.scrollTo({ top: 120, behavior: "smooth" });
  };

  const handleClearFilters = () => {
    router.push(pathname);
  };

  const hasActiveFilters = Boolean(paramQ || paramCategory || paramTopic || paramSpeaker);

  return (
    <div className="w-full">
      {/* Sacred Buddhist Videos Hero Banner - Full Width & Majestic Height */}
      <section className="relative w-full overflow-hidden min-h-[420px] sm:min-h-[500px] md:min-h-[560px] lg:min-h-[600px] flex items-center border-b border-outline-variant/30 group">
        {/* 1. Ảnh nằm dưới cùng (tràn mép 100vw) */}
        <Image
          src="/images/buddhist-videos-banner.jpg"
          alt="Thư Viện Video Phật Pháp"
          fill
          priority
          sizes="100vw"
          className="object-cover object-[center_38%] group-hover:scale-102 transition-transform duration-700"
        />

        {/* Lớp gradient tự nhiên loang dần từ trái sang phải để tôn chữ, không tạo viền hộp */}
        <div className="absolute inset-0 bg-gradient-to-r from-stone-950/80 via-stone-900/45 via-45% to-transparent pointer-events-none" />
        <div className="absolute inset-0 bg-gradient-to-t from-stone-950/45 via-transparent to-transparent pointer-events-none" />

        {/* Căn lề nội dung theo khung chuẩn 1360px của trang */}
        <div className="max-w-[1360px] mx-auto px-4 sm:px-6 w-full py-12 sm:py-16 md:py-20 relative z-10">
          {/* Chữ nằm trực tiếp trên banner (bỏ hoàn toàn background của chữ) */}
          <div className="max-w-xl sm:max-w-2xl space-y-4">
            <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-amber-300 shadow-xs">
              <span className="w-2 h-2 rounded-full bg-amber-400 animate-pulse" />
              <span className="font-label-caps text-xs uppercase tracking-wider font-semibold">
                Pháp Âm Diệu Dụng
              </span>
            </div>
            <h1 className="font-headline-xl text-3xl sm:text-5xl lg:text-6xl font-bold text-white tracking-tight font-serif drop-shadow-[0_2px_8px_rgba(0,0,0,0.7)] leading-[1.15]">
              Thư Viện Video Phật Pháp
            </h1>
            <p className="font-body-md text-base sm:text-lg text-white/90 leading-relaxed drop-shadow-[0_1px_4px_rgba(0,0,0,0.8)] max-w-xl font-normal">
              Khám phá các pháp thoại, bài giảng và nội dung giáo dục Phật giáo được tuyển chọn từ những nguồn xác minh chính thống.
            </p>


          </div>
        </div>
      </section>

      {/* Nội dung danh sách video */}
      <div className="max-w-[1360px] mx-auto px-4 sm:px-6 py-8 space-y-8">
        {/* Topics & Sort Bar (Đã bỏ thanh lọc chuyên mục theo yêu cầu) */}
        <div className="flex items-center justify-between gap-4 flex-wrap pb-4 border-b border-outline-variant/30">
          <VideoTopicFilter
            topics={topics}
            selectedTopic={paramTopic}
            selectedSort={paramSort}
            onSelectTopic={handleTopicChange}
            onSelectSort={handleSortChange}
          />

          {/* Result Count Summary */}
          <div className="flex items-center gap-3 text-xs sm:text-sm text-on-surface-variant">
            <span>
              {isLoading ? (
                "Đang tải danh sách video..."
              ) : (
                <>
                  Tìm thấy <strong className="text-on-surface font-semibold">{totalCount}</strong> video Phật pháp
                </>
              )}
            </span>

            {hasActiveFilters && (
              <button
                type="button"
                onClick={handleClearFilters}
                className="text-primary hover:underline font-medium cursor-pointer"
              >
                Xóa tất cả bộ lọc
              </button>
            )}
          </div>
        </div>

        {/* Content State */}
        {hasError ? (
          <div className="p-12 text-center bg-surface-container-lowest rounded-3xl border border-outline-variant/40 space-y-4 max-w-md mx-auto">
            <span className="material-symbols-outlined text-[44px] text-error/70">
              cloud_off
            </span>
            <h3 className="font-headline-sm text-lg font-semibold text-on-surface">
              Chưa thể tải danh sách video
            </h3>
            <p className="font-body-sm text-xs sm:text-sm text-on-surface-variant">
              Đã có lỗi xảy ra trong quá trình kết nối máy chủ.
            </p>
            <button
              type="button"
              onClick={fetchVideos}
              className="px-5 py-2.5 rounded-full bg-primary text-on-primary text-xs font-semibold hover:bg-primary/90 transition-colors cursor-pointer"
            >
              Thử lại
            </button>
          </div>
        ) : isLoading ? (
          /* Loading Skeleton Grid (6 cards) */
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            {Array.from({ length: 6 }).map((_, idx) => (
              <div
                key={idx}
                className="bg-surface-container-lowest rounded-2xl border border-outline-variant/30 overflow-hidden space-y-3 animate-pulse p-4"
              >
                <div className="aspect-video w-full bg-surface-container-high rounded-xl" />
                <div className="h-4 w-24 bg-surface-container-high rounded" />
                <div className="h-5 w-4/5 bg-surface-container-high rounded" />
                <div className="h-4 w-2/3 bg-surface-container-high rounded" />
              </div>
            ))}
          </div>
        ) : videos.length === 0 ? (
          <VideoEmptyState
            hasFilters={hasActiveFilters}
            onClearFilters={handleClearFilters}
          />
        ) : (
          /* Video Grid */
          <div className="space-y-8">
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
              {videos.map((video) => (
                <VideoCard key={video.public_id} video={video} />
              ))}
            </div>

            {/* Pagination */}
            <VideoPagination
              currentPage={currentPage}
              lastPage={lastPage}
              onPageChange={handlePageChange}
            />
          </div>
        )}
      </div>
    </div>
  );
}
