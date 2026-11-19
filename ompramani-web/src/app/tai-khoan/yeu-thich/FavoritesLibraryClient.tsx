"use client";

import React, { useState, useEffect } from "react";
import { useRouter } from "next/navigation";
import Link from "next/link";
import { useAuth } from "@/context/AuthContext";
import { FavoriteContentType, UserFavoriteItem } from "@/types/favorite";
import { getFavorites, removeFavorite } from "@/lib/api/favorites";
import { FavoriteFilterTabs } from "@/components/favorites/FavoriteFilterTabs";
import { FavoriteCard } from "@/components/favorites/FavoriteCard";
import { FavoriteEmptyState } from "@/components/favorites/FavoriteEmptyState";
import { FavoritePagination } from "@/components/favorites/FavoritePagination";

export function FavoritesLibraryClient() {
  const router = useRouter();
  const { isAuthenticated, isLoading: isAuthLoading } = useAuth();

  const [currentType, setCurrentType] = useState<FavoriteContentType>("all");
  const [currentPage, setCurrentPage] = useState<number>(1);
  const [favorites, setFavorites] = useState<UserFavoriteItem[]>([]);
  const [lastPage, setLastPage] = useState<number>(1);
  const [totalItems, setTotalItems] = useState<number>(0);
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  // Redirect guest to login
  useEffect(() => {
    if (!isAuthLoading && !isAuthenticated) {
      router.push("/dang-nhap?next=/tai-khoan/yeu-thich");
    }
  }, [isAuthLoading, isAuthenticated, router]);

  // Fetch data when filter or page changes
  useEffect(() => {
    let isMounted = true;
    if (!isAuthenticated) return;

    getFavorites({
      type: currentType,
      page: currentPage,
      per_page: 12,
    })
      .then((res) => {
        if (isMounted) {
          setFavorites(res.data || []);
          setLastPage(res.meta?.last_page || 1);
          setTotalItems(res.meta?.total || 0);
          setIsLoading(false);
        }
      })
      .catch(() => {
        if (isMounted) {
          setErrorMessage("Chưa thể tải nội dung yêu thích, vui lòng thử lại sau.");
          setIsLoading(false);
        }
      });

    return () => {
      isMounted = false;
    };
  }, [isAuthenticated, currentType, currentPage]);

  // Handle Tab Switch
  const handleSelectType = (type: FavoriteContentType) => {
    setIsLoading(true);
    setErrorMessage(null);
    setCurrentType(type);
    setCurrentPage(1);
  };

  // Handle Remove
  const handleRemove = async (fav: UserFavoriteItem) => {
    setFavorites((prev) => prev.filter((item) => item.id !== fav.id));
    setTotalItems((prev) => Math.max(0, prev - 1));

    try {
      await removeFavorite(fav.type, fav.content_public_id);
    } catch {
      // Re-fetch if network error
      const res = await getFavorites({
        type: currentType,
        page: currentPage,
        per_page: 12,
      }).catch(() => null);
      if (res?.data) {
        setFavorites(res.data);
      }
    }
  };

  const handleRetry = () => {
    setIsLoading(true);
    setErrorMessage(null);
    getFavorites({
      type: currentType,
      page: currentPage,
      per_page: 12,
    })
      .then((res) => {
        setFavorites(res.data || []);
        setLastPage(res.meta?.last_page || 1);
        setTotalItems(res.meta?.total || 0);
        setIsLoading(false);
      })
      .catch(() => {
        setErrorMessage("Chưa thể tải nội dung yêu thích, vui lòng thử lại sau.");
        setIsLoading(false);
      });
  };

  if (isAuthLoading) {
    return (
      <div className="w-full max-w-6xl mx-auto p-6 space-y-6 animate-pulse">
        <div className="h-28 rounded-3xl bg-surface-container-low" />
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <div className="h-72 rounded-3xl bg-surface-container-low" />
          <div className="h-72 rounded-3xl bg-surface-container-low" />
          <div className="h-72 rounded-3xl bg-surface-container-low" />
        </div>
      </div>
    );
  }

  if (!isAuthenticated) {
    return null;
  }

  return (
    <div className="w-full max-w-6xl mx-auto space-y-8">
      {/* Breadcrumb & Navigation */}
      <nav
        aria-label="Breadcrumb"
        className="flex items-center gap-2 text-[13px] font-label-md text-outline"
      >
        <Link href="/" className="hover:text-primary transition-colors">
          Trang chủ
        </Link>
        <span className="material-symbols-outlined text-[14px]">
          chevron_right
        </span>
        <Link href="/tai-khoan" className="hover:text-primary transition-colors">
          Tài khoản
        </Link>
        <span className="material-symbols-outlined text-[14px]">
          chevron_right
        </span>
        <span className="text-on-surface font-semibold">Nội dung yêu thích</span>
      </nav>

      {/* Header Banner */}
      <header className="bg-surface-container-lowest rounded-3xl p-6 sm:p-8 border border-outline-variant/30 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div className="space-y-2">
          <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 text-xs font-label-md font-medium border border-rose-200/50">
            <span className="material-symbols-outlined text-[16px]">
              favorite
            </span>
            <span>Thư viện cá nhân</span>
          </div>
          <h1 className="font-headline-xl text-2xl sm:text-3xl font-bold text-on-surface tracking-tight">
            Nội Dung Yêu Thích
          </h1>
          <p className="font-body-md text-sm text-on-surface-variant max-w-2xl leading-relaxed">
            Những nội dung bạn đã lưu để thuận tiện xem lại.
          </p>
        </div>

        <div className="flex items-center gap-3 shrink-0">
          <div className="px-4 py-2 rounded-2xl bg-surface-container-low border border-outline-variant/30 text-center">
            <span className="block font-headline-sm text-lg font-bold text-primary">
              {totalItems}
            </span>
            <span className="text-[11px] font-label-md text-on-surface-variant">
              Mục đã lưu
            </span>
          </div>
        </div>
      </header>

      {/* Filter Tabs */}
      <div className="pt-2">
        <FavoriteFilterTabs
          currentType={currentType}
          onSelectType={handleSelectType}
          disabled={isLoading}
        />
      </div>

      {/* Content Area */}
      {isLoading ? (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 animate-pulse">
          {Array.from({ length: 6 }).map((_, idx) => (
            <div
              key={idx}
              className="h-80 rounded-3xl bg-surface-container-low border border-outline-variant/20"
            />
          ))}
        </div>
      ) : errorMessage ? (
        <div className="text-center py-12 px-4 bg-surface-container-lowest rounded-3xl border border-outline-variant/30 space-y-4">
          <span className="material-symbols-outlined text-4xl text-rose-500">
            error_outline
          </span>
          <p className="text-sm font-body-md text-on-surface-variant">
            {errorMessage}
          </p>
          <button
            type="button"
            onClick={handleRetry}
            className="px-5 py-2 rounded-full bg-primary text-on-primary font-label-md text-xs font-semibold hover:bg-primary/90 transition-colors cursor-pointer"
          >
            Thử lại
          </button>
        </div>
      ) : favorites.length === 0 ? (
        <div className="bg-surface-container-lowest rounded-3xl border border-outline-variant/30">
          <FavoriteEmptyState currentType={currentType} />
        </div>
      ) : (
        <div className="space-y-6">
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            {favorites.map((item) => (
              <FavoriteCard
                key={`${item.type}-${item.id}`}
                favorite={item}
                onRemove={handleRemove}
              />
            ))}
          </div>

          <FavoritePagination
            currentPage={currentPage}
            lastPage={lastPage}
            onPageChange={setCurrentPage}
            disabled={isLoading}
          />
        </div>
      )}
    </div>
  );
}
