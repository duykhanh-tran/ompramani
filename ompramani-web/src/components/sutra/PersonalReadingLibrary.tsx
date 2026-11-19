/* eslint-disable @next/next/no-img-element */
"use client";

import React, { useState, useEffect, useCallback } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useAuth } from "@/context/AuthContext";
import {
  SutraReadingProgress,
  SutraBookmark,
} from "@/types/api";
import {
  getReadingProgresses,
  deleteReadingProgress,
} from "@/lib/api/reading-progress";
import {
  getSutraBookmarks,
  deleteSutraBookmark,
} from "@/lib/api/sutra-bookmarks";
import { formatArticleDate } from "@/lib/utils/sacred";

type LibraryTab = "reading" | "saved_sutras" | "saved_sections";

export default function PersonalReadingLibrary() {
  const router = useRouter();
  const { isAuthenticated, isLoading: isAuthLoading } = useAuth();

  const [activeTab, setActiveTab] = useState<LibraryTab>("reading");

  // Data states
  const [readingList, setReadingList] = useState<SutraReadingProgress[]>([]);
  const [savedSutras, setSavedSutras] = useState<SutraBookmark[]>([]);
  const [savedSections, setSavedSections] = useState<SutraBookmark[]>([]);

  // Loading states
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [actionError, setActionError] = useState<string | null>(null);
  const [actionSuccess, setActionSuccess] = useState<string | null>(null);

  // Redirect if not logged in
  useEffect(() => {
    if (!isAuthLoading && !isAuthenticated) {
      router.replace("/dang-nhap?next=/tai-khoan/thu-vien-kinh");
    }
  }, [isAuthLoading, isAuthenticated, router]);

  // Load reading progress
  const loadReadingProgress = useCallback(async () => {
    setIsLoading(true);
    try {
      const res = await getReadingProgresses({ per_page: 50 });
      setReadingList(res.data || []);
    } catch {
      // fallback
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Load saved sutras
  const loadSavedSutras = useCallback(async () => {
    setIsLoading(true);
    try {
      const res = await getSutraBookmarks({ type: "sutra", per_page: 50 });
      setSavedSutras(res.data || []);
    } catch {
      // fallback
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Load saved sections
  const loadSavedSections = useCallback(async () => {
    setIsLoading(true);
    try {
      const res = await getSutraBookmarks({ type: "section", per_page: 50 });
      setSavedSections(res.data || []);
    } catch {
      // fallback
    } finally {
      setIsLoading(false);
    }
  }, []);

  useEffect(() => {
    if (!isAuthenticated) return;

    if (activeTab === "reading") {
      loadReadingProgress();
    } else if (activeTab === "saved_sutras") {
      loadSavedSutras();
    } else if (activeTab === "saved_sections") {
      loadSavedSections();
    }
  }, [activeTab, isAuthenticated, loadReadingProgress, loadSavedSutras, loadSavedSections]);

  // Delete reading progress item
  const handleDeleteProgress = async (item: SutraReadingProgress) => {
    if (!confirm(`Bạn có chắc muốn xóa lịch sử đọc kinh "${item.sutra?.title || ""}"?`)) {
      return;
    }

    try {
      await deleteReadingProgress(item.sutra.slug, item.version?.id);
      setReadingList((prev) => prev.filter((p) => p.id !== item.id));
      setActionSuccess("Đã xóa lịch sử đọc kinh");
      setTimeout(() => setActionSuccess(null), 3000);
    } catch {
      setActionError("Không thể xóa lúc này. Vui lòng thử lại.");
      setTimeout(() => setActionError(null), 3000);
    }
  };

  // Delete bookmark item
  const handleDeleteBookmark = async (bookmarkId: number, isSection = false) => {
    try {
      await deleteSutraBookmark(bookmarkId);
      if (isSection) {
        setSavedSections((prev) => prev.filter((b) => b.id !== bookmarkId));
        setActionSuccess("Đã bỏ đánh dấu phẩm");
      } else {
        setSavedSutras((prev) => prev.filter((b) => b.id !== bookmarkId));
        setActionSuccess("Đã bỏ lưu kinh");
      }
      setTimeout(() => setActionSuccess(null), 3000);
    } catch {
      setActionError("Không thể bỏ lưu lúc này. Vui lòng thử lại.");
      setTimeout(() => setActionError(null), 3000);
    }
  };

  if (isAuthLoading) {
    return (
      <div className="w-full max-w-4xl mx-auto p-6 space-y-6 animate-pulse">
        <div className="h-28 rounded-3xl bg-surface-container-low" />
        <div className="h-64 rounded-3xl bg-surface-container-low" />
      </div>
    );
  }

  return (
    <div className="w-full max-w-4xl mx-auto space-y-6">
      {/* Header Banner */}
      <div className="relative overflow-hidden rounded-3xl bg-surface-container-lowest border border-outline-variant/50 p-6 sm:p-8 shadow-[0_4px_24px_rgba(140,109,45,0.06)]">
        <div className="absolute top-0 right-0 w-64 h-64 bg-primary/5 rounded-full blur-3xl pointer-events-none" />

        <div className="flex flex-col sm:flex-row items-center sm:items-start justify-between gap-6 text-center sm:text-left">
          <div className="space-y-2">
            <div className="flex items-center justify-center sm:justify-start gap-2">
              <span className="w-2.5 h-2.5 rounded-full bg-primary" />
              <span className="font-label-caps text-xs text-primary uppercase tracking-wider font-semibold">
                Tủ Kinh Cá Nhân
              </span>
            </div>
            <h1 className="font-headline-xl text-2xl sm:text-3xl font-bold text-on-surface">
              Thư Viện Kinh Của Bạn
            </h1>
            <p className="font-body-md text-sm text-on-surface-variant max-w-xl">
              Nơi lưu giữ từng bước đường đọc tụng, ghi dấu những kinh văn trang nghiêm
              và các phẩm giáo lý bạn đang tu học mỗi ngày.
            </p>
          </div>

          <div className="flex items-center gap-3 shrink-0">
            <Link
              href="/kinh"
              className="px-4 py-2 rounded-2xl bg-primary text-on-primary font-label-md text-xs font-semibold hover:bg-primary/90 transition-all flex items-center gap-1.5 shadow-xs"
            >
              <span className="material-symbols-outlined text-[16px]">
                menu_book
              </span>
              <span>Duyệt Toàn Bộ Kinh Văn</span>
            </Link>
          </div>
        </div>
      </div>

      {/* Tab Navigation */}
      <div className="flex flex-wrap items-center gap-2 border-b border-outline-variant/40 pb-2">
        <button
          type="button"
          onClick={() => setActiveTab("reading")}
          className={`flex items-center gap-2 px-4 py-2.5 rounded-2xl font-label-md text-xs font-semibold transition-all cursor-pointer ${
            activeTab === "reading"
              ? "bg-primary text-on-primary shadow-xs"
              : "bg-surface-container-low text-on-surface hover:bg-surface-container hover:text-primary"
          }`}
        >
          <span className="material-symbols-outlined text-[18px]">
            auto_stories
          </span>
          <span>Đang Đọc ({readingList.length})</span>
        </button>

        <button
          type="button"
          onClick={() => setActiveTab("saved_sutras")}
          className={`flex items-center gap-2 px-4 py-2.5 rounded-2xl font-label-md text-xs font-semibold transition-all cursor-pointer ${
            activeTab === "saved_sutras"
              ? "bg-primary text-on-primary shadow-xs"
              : "bg-surface-container-low text-on-surface hover:bg-surface-container hover:text-primary"
          }`}
        >
          <span className="material-symbols-outlined text-[18px]">
            bookmark
          </span>
          <span>Kinh Đã Lưu ({savedSutras.length})</span>
        </button>

        <button
          type="button"
          onClick={() => setActiveTab("saved_sections")}
          className={`flex items-center gap-2 px-4 py-2.5 rounded-2xl font-label-md text-xs font-semibold transition-all cursor-pointer ${
            activeTab === "saved_sections"
              ? "bg-primary text-on-primary shadow-xs"
              : "bg-surface-container-low text-on-surface hover:bg-surface-container hover:text-primary"
          }`}
        >
          <span className="material-symbols-outlined text-[18px]">
            bookmarks
          </span>
          <span>Phẩm Đã Đánh Dấu ({savedSections.length})</span>
        </button>

        <Link
          href="/tai-khoan"
          className="flex items-center gap-1.5 px-3 py-2 text-outline hover:text-primary font-label-md text-xs transition-colors ml-auto"
        >
          <span className="material-symbols-outlined text-[16px]">
            arrow_back
          </span>
          <span>Về Tài Khoản</span>
        </Link>
      </div>

      {/* Notifications */}
      {actionSuccess && (
        <div className="p-3 rounded-2xl bg-primary/10 border border-primary/20 text-primary text-xs font-label-md flex items-center gap-2 animate-in fade-in">
          <span className="material-symbols-outlined text-[16px]">check_circle</span>
          <span>{actionSuccess}</span>
        </div>
      )}
      {actionError && (
        <div className="p-3 rounded-2xl bg-error/10 border border-error/20 text-error text-xs font-label-md flex items-center gap-2 animate-in fade-in">
          <span className="material-symbols-outlined text-[16px]">error</span>
          <span>{actionError}</span>
        </div>
      )}

      {/* TAB CONTENT */}
      {isLoading ? (
        <div className="py-16 text-center">
          <span className="w-8 h-8 border-2 border-primary border-t-transparent rounded-full animate-spin inline-block" />
          <p className="mt-3 font-body-sm text-xs text-outline">Đang tải tủ kinh...</p>
        </div>
      ) : (
        <>
          {/* TAB 1: ĐANG ĐỌC (READING PROGRESS) */}
          {activeTab === "reading" && (
            <div className="space-y-4">
              {readingList.length === 0 ? (
                <div className="bg-surface-container-lowest rounded-3xl p-12 text-center border border-outline-variant/30 space-y-4">
                  <span className="material-symbols-outlined text-outline text-[48px]">
                    auto_stories
                  </span>
                  <h3 className="font-headline-md text-lg font-semibold text-on-surface">
                    Chưa có tiến trình đọc kinh
                  </h3>
                  <p className="font-body-md text-sm text-on-surface-variant max-w-md mx-auto">
                    Khi bạn mở bất kỳ bản kinh nào để đọc, hệ thống sẽ tự động ghi nhớ
                    phẩm bạn đang đọc dở để bạn có thể tiếp tục bất cứ lúc nào.
                  </p>
                  <div className="pt-2">
                    <Link
                      href="/kinh"
                      className="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-primary text-on-primary font-label-md text-xs font-semibold hover:bg-primary/90 transition-all shadow-xs"
                    >
                      <span>Bắt đầu đọc kinh văn</span>
                      <span className="material-symbols-outlined text-[16px]">
                        arrow_forward
                      </span>
                    </Link>
                  </div>
                </div>
              ) : (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  {readingList.map((item) => (
                    <div
                      key={item.id}
                      className="bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/40 hover:border-outline-variant transition-all shadow-xs flex flex-col justify-between space-y-4"
                    >
                      <div className="space-y-2">
                        <div className="flex items-start justify-between gap-3">
                          <div className="flex-1 min-w-0">
                            {item.sutra?.tradition && (
                              <span className="inline-block px-2 py-0.5 rounded-full bg-surface-container text-primary font-label-md text-[10px] font-semibold mb-1">
                                {item.sutra.tradition.name}
                              </span>
                            )}
                            <h3 className="font-headline-md text-base font-bold text-on-surface line-clamp-1">
                              {item.sutra?.title}
                            </h3>
                            <p className="font-body-sm text-xs text-outline line-clamp-1">
                              {item.sutra?.translator
                                ? `Dịch giả: ${item.sutra.translator}`
                                : item.version?.translator
                                  ? `Bản dịch: ${item.version.translator}`
                                  : item.version?.name || "Kinh điển"}
                            </p>
                          </div>

                          <button
                            type="button"
                            onClick={() => handleDeleteProgress(item)}
                            className="w-8 h-8 rounded-xl flex items-center justify-center text-outline hover:text-error hover:bg-error/10 transition-colors"
                            title="Xóa khỏi danh sách đang đọc"
                          >
                            <span className="material-symbols-outlined text-[18px]">
                              delete
                            </span>
                          </button>
                        </div>

                        {/* Current section (NO fake completion percentage!) */}
                        <div className="p-3 rounded-xl bg-surface-container-low border border-outline-variant/30 flex items-center gap-2.5">
                          <span className="material-symbols-outlined text-primary text-[20px] shrink-0">
                            bookmark
                          </span>
                          <div className="min-w-0 flex-1">
                            <span className="font-label-caps text-[10px] text-outline uppercase block">
                              Đang đọc
                            </span>
                            <span className="font-label-md text-xs font-semibold text-on-surface truncate block">
                              {item.section?.title || "Phẩm kinh"}
                            </span>
                          </div>
                        </div>
                      </div>

                      <div className="flex items-center justify-between pt-2 border-t border-outline-variant/20 text-xs text-outline">
                        <span className="flex items-center gap-1 font-label-md text-[11px]">
                          <span className="material-symbols-outlined text-[14px]">
                            schedule
                          </span>
                          <span>Đọc lần cuối: {formatArticleDate(item.last_read_at)}</span>
                        </span>

                        <Link
                          href={item.continue_url || `/kinh/${item.sutra?.slug || ""}`}
                          className="px-4 py-1.5 rounded-xl bg-primary text-on-primary font-label-md text-xs font-semibold hover:bg-primary/90 transition-all flex items-center gap-1 shadow-xs"
                        >
                          <span>Đọc tiếp</span>
                          <span className="material-symbols-outlined text-[14px]">
                            arrow_forward
                          </span>
                        </Link>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          )}

          {/* TAB 2: KINH ĐÃ LƯU (SAVED SUTRAS) */}
          {activeTab === "saved_sutras" && (
            <div className="space-y-4">
              {savedSutras.length === 0 ? (
                <div className="bg-surface-container-lowest rounded-3xl p-12 text-center border border-outline-variant/30 space-y-4">
                  <span className="material-symbols-outlined text-outline text-[48px]">
                    bookmark_border
                  </span>
                  <h3 className="font-headline-md text-lg font-semibold text-on-surface">
                    Chưa có bản kinh nào được lưu
                  </h3>
                  <p className="font-body-md text-sm text-on-surface-variant max-w-md mx-auto">
                    Bạn có thể lưu các bản kinh tâm đắc vào tủ sách bằng cách nhấn nút
                    &quot;Lưu kinh&quot; ở trang chi tiết của mỗi bộ kinh.
                  </p>
                  <div className="pt-2">
                    <Link
                      href="/kinh"
                      className="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-primary text-on-primary font-label-md text-xs font-semibold hover:bg-primary/90 transition-all shadow-xs"
                    >
                      <span>Khám phá Thư viện Kinh</span>
                      <span className="material-symbols-outlined text-[16px]">
                        arrow_forward
                      </span>
                    </Link>
                  </div>
                </div>
              ) : (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  {savedSutras.map((item) => (
                    <div
                      key={item.id}
                      className="bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/40 hover:border-outline-variant transition-all shadow-xs flex flex-col justify-between space-y-4"
                    >
                      <div className="flex gap-4">
                        {item.sutra.thumbnail ? (
                          <img
                            src={item.sutra.thumbnail}
                            alt={item.sutra.title}
                            className="w-16 h-20 rounded-xl object-cover shrink-0 bg-surface-container border border-outline-variant/30"
                          />
                        ) : (
                          <div className="w-16 h-20 rounded-xl bg-surface-container text-primary flex items-center justify-center shrink-0 border border-outline-variant/30">
                            <span className="material-symbols-outlined text-[28px]">
                              menu_book
                            </span>
                          </div>
                        )}

                        <div className="flex-1 min-w-0 space-y-1">
                          {item.sutra.tradition && (
                            <span className="inline-block px-2 py-0.5 rounded-full bg-surface-container text-primary font-label-md text-[10px] font-semibold">
                              {item.sutra.tradition.name}
                            </span>
                          )}
                          <h3 className="font-headline-md text-base font-bold text-on-surface line-clamp-1">
                            {item.sutra.title}
                          </h3>
                          {item.sutra.short_description && (
                            <p className="font-body-sm text-xs text-on-surface-variant line-clamp-2">
                              {item.sutra.short_description}
                            </p>
                          )}
                        </div>
                      </div>

                      <div className="flex items-center justify-between pt-2 border-t border-outline-variant/20 text-xs">
                        <button
                          type="button"
                          onClick={() => handleDeleteBookmark(item.id, false)}
                          className="text-outline hover:text-error flex items-center gap-1 font-label-md transition-colors"
                        >
                          <span className="material-symbols-outlined text-[16px]">
                            bookmark_remove
                          </span>
                          <span>Bỏ lưu</span>
                        </button>

                        <Link
                          href={item.target_url || `/kinh/${item.sutra.slug}`}
                          className="px-4 py-1.5 rounded-xl bg-primary-container text-on-primary-container hover:bg-primary hover:text-on-primary font-label-md text-xs font-semibold transition-all flex items-center gap-1"
                        >
                          <span>Đọc kinh</span>
                          <span className="material-symbols-outlined text-[14px]">
                            arrow_forward
                          </span>
                        </Link>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          )}

          {/* TAB 3: PHẨM ĐÃ ĐÁNH DẤU (SAVED SECTIONS) */}
          {activeTab === "saved_sections" && (
            <div className="space-y-4">
              {savedSections.length === 0 ? (
                <div className="bg-surface-container-lowest rounded-3xl p-12 text-center border border-outline-variant/30 space-y-4">
                  <span className="material-symbols-outlined text-outline text-[48px]">
                    bookmarks
                  </span>
                  <h3 className="font-headline-md text-lg font-semibold text-on-surface">
                    Chưa có phẩm nào được đánh dấu
                  </h3>
                  <p className="font-body-md text-sm text-on-surface-variant max-w-md mx-auto">
                    Trong khi đọc một phẩm kinh tâm đắc, bạn có thể nhấn biểu tượng
                    đánh dấu trên thanh công cụ để lưu lại xem lại sau.
                  </p>
                  <div className="pt-2">
                    <Link
                      href="/kinh"
                      className="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-primary text-on-primary font-label-md text-xs font-semibold hover:bg-primary/90 transition-all shadow-xs"
                    >
                      <span>Khám phá Thư viện Kinh</span>
                      <span className="material-symbols-outlined text-[16px]">
                        arrow_forward
                      </span>
                    </Link>
                  </div>
                </div>
              ) : (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  {savedSections.map((item) => (
                    <div
                      key={item.id}
                      className="bg-surface-container-lowest rounded-2xl p-5 border border-outline-variant/40 hover:border-outline-variant transition-all shadow-xs flex flex-col justify-between space-y-4"
                    >
                      <div className="space-y-2">
                        <div className="flex items-start justify-between gap-3">
                          <div className="flex-1 min-w-0">
                            <span className="font-label-caps text-[10px] text-primary uppercase block">
                              {item.sutra.title}
                            </span>
                            <h3 className="font-headline-md text-base font-bold text-on-surface line-clamp-1 mt-0.5">
                              {item.section?.title || "Phẩm kinh"}
                            </h3>
                            {item.version && (
                              <p className="font-body-sm text-xs text-outline line-clamp-1">
                                {item.version.name}
                              </p>
                            )}
                          </div>

                          <button
                            type="button"
                            onClick={() => handleDeleteBookmark(item.id, true)}
                            className="w-8 h-8 rounded-xl flex items-center justify-center text-outline hover:text-error hover:bg-error/10 transition-colors"
                            title="Bỏ đánh dấu phẩm này"
                          >
                            <span className="material-symbols-outlined text-[18px]">
                              bookmark_remove
                            </span>
                          </button>
                        </div>
                      </div>

                      <div className="flex items-center justify-between pt-2 border-t border-outline-variant/20 text-xs text-outline">
                        <span className="flex items-center gap-1 font-label-md text-[11px]">
                          <span className="material-symbols-outlined text-[14px]">
                            schedule
                          </span>
                          <span>Đánh dấu: {formatArticleDate(item.created_at)}</span>
                        </span>

                        <Link
                          href={item.target_url || `/kinh/${item.sutra?.slug || ""}`}
                          className="px-4 py-1.5 rounded-xl bg-primary text-on-primary font-label-md text-xs font-semibold hover:bg-primary/90 transition-all flex items-center gap-1 shadow-xs"
                        >
                          <span>Đọc phẩm này</span>
                          <span className="material-symbols-outlined text-[14px]">
                            arrow_forward
                          </span>
                        </Link>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          )}
        </>
      )}
    </div>
  );
}
