"use client";

import React, { useState, useEffect } from "react";
import { useRouter, usePathname } from "next/navigation";
import { useAuth } from "@/context/AuthContext";
import { addFavorite, removeFavorite, getFavoriteStatus } from "@/lib/api/favorites";

export interface FavoriteButtonProps {
  contentType: "sutra" | "article" | "audio" | "video";
  contentPublicId: string;
  initialIsFavorited?: boolean;
  variant?: "icon" | "pill" | "compact";
  showLabel?: boolean;
  className?: string;
  onToggle?: (isFavorited: boolean) => void;
}

export function FavoriteButton({
  contentType,
  contentPublicId,
  initialIsFavorited,
  variant = "icon",
  showLabel = false,
  className = "",
  onToggle,
}: FavoriteButtonProps) {
  const router = useRouter();
  const pathname = usePathname();
  const { isAuthenticated, isLoading: isAuthLoading } = useAuth();

  const [overrideFavorited, setOverrideFavorited] = useState<boolean | null>(null);
  const [isPending, setIsPending] = useState<boolean>(false);
  const [feedback, setFeedback] = useState<string | null>(null);

  const isFavorited = overrideFavorited !== null
    ? overrideFavorited
    : (initialIsFavorited ?? false);

  // If initial status was not provided, fetch asynchronously on mount
  useEffect(() => {
    let active = true;

    if (initialIsFavorited === undefined && isAuthenticated && contentPublicId) {
      getFavoriteStatus(contentType, contentPublicId)
        .then((fav) => {
          if (active) {
            setOverrideFavorited(fav);
          }
        })
        .catch(() => {});
    }

    return () => {
      active = false;
    };
  }, [isAuthenticated, contentType, contentPublicId, initialIsFavorited]);

  const handleToggle = async (e: React.MouseEvent) => {
    e.preventDefault();
    e.stopPropagation();

    if (isPending) return;

    if (!isAuthenticated) {
      const returnUrl = encodeURIComponent(pathname || "/");
      router.push(`/dang-nhap?next=${returnUrl}`);
      return;
    }

    const nextState = !isFavorited;
    setOverrideFavorited(nextState);
    setIsPending(true);

    try {
      if (nextState) {
        await addFavorite(contentType, contentPublicId);
        setFeedback("Đã lưu yêu thích");
      } else {
        await removeFavorite(contentType, contentPublicId);
        setFeedback("Đã bỏ yêu thích");
      }

      onToggle?.(nextState);
      setTimeout(() => setFeedback(null), 2500);
    } catch {
      // Revert on error
      setOverrideFavorited(!nextState);
      setFeedback("Không thể cập nhật, vui lòng thử lại");
      setTimeout(() => setFeedback(null), 2500);
    } finally {
      setIsPending(false);
    }
  };

  const labelText = isFavorited ? "Bỏ yêu thích" : "Lưu yêu thích";
  const ariaLabel = isFavorited ? "Bỏ khỏi yêu thích" : "Thêm vào yêu thích";

  if (variant === "pill") {
    return (
      <button
        type="button"
        disabled={isPending || isAuthLoading}
        onClick={handleToggle}
        aria-label={ariaLabel}
        className={`inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-label-md font-medium transition-all cursor-pointer ${
          isFavorited
            ? "bg-rose-50 dark:bg-rose-950/30 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-900/40 shadow-xs"
            : "bg-surface-container-low hover:bg-surface-container text-on-surface-variant hover:text-on-surface border border-outline-variant/30"
        } ${className}`}
        title={feedback || labelText}
      >
        <span
          className={`material-symbols-outlined text-[18px] transition-transform ${
            isFavorited ? "text-rose-600 fill-current" : ""
          } ${isPending ? "animate-pulse" : ""}`}
          style={isFavorited ? { fontVariationSettings: "'FILL' 1" } : undefined}
        >
          favorite
        </span>
        <span>{feedback || (showLabel ? labelText : isFavorited ? "Đã lưu" : "Lưu")}</span>
      </button>
    );
  }

  if (variant === "compact") {
    return (
      <button
        type="button"
        disabled={isPending || isAuthLoading}
        onClick={handleToggle}
        aria-label={ariaLabel}
        className={`inline-flex items-center justify-center w-8 h-8 rounded-full transition-all cursor-pointer ${
          isFavorited
            ? "bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400"
            : "bg-surface/80 hover:bg-surface text-on-surface-variant hover:text-rose-500 shadow-xs backdrop-blur-xs"
        } ${className}`}
        title={feedback || labelText}
      >
        <span
          className={`material-symbols-outlined text-[18px] ${
            isPending ? "animate-pulse" : ""
          }`}
          style={isFavorited ? { fontVariationSettings: "'FILL' 1" } : undefined}
        >
          favorite
        </span>
      </button>
    );
  }

  // Default icon button
  return (
    <button
      type="button"
      disabled={isPending || isAuthLoading}
      onClick={handleToggle}
      aria-label={ariaLabel}
      className={`relative inline-flex items-center justify-center p-2 rounded-xl transition-all cursor-pointer ${
        isFavorited
          ? "text-rose-600 bg-rose-50/80 dark:bg-rose-950/30 dark:text-rose-400"
          : "text-on-surface-variant hover:text-rose-500 hover:bg-surface-container-low"
      } ${className}`}
      title={feedback || labelText}
    >
      <span
        className={`material-symbols-outlined text-[20px] transition-transform ${
          isPending ? "animate-pulse" : ""
        }`}
        style={isFavorited ? { fontVariationSettings: "'FILL' 1" } : undefined}
      >
        favorite
      </span>
      {showLabel && (
        <span className="ml-1.5 text-xs font-label-md font-medium">
          {feedback || labelText}
        </span>
      )}
    </button>
  );
}
