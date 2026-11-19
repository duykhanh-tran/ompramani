"use client";

import React, { useState } from "react";

interface TempleShareActionProps {
  slug: string;
  name: string;
}

export function TempleShareAction({ slug, name }: TempleShareActionProps) {
  const [copied, setCopied] = useState<boolean>(false);

  const handleShare = async () => {
    const url = typeof window !== "undefined"
      ? `${window.location.origin}/tim-chua/${slug}`
      : `/tim-chua/${slug}`;

    if (navigator.share) {
      try {
        await navigator.share({
          title: `${name} — OmPraMani`,
          text: `Khám phá thông tin tự viện ${name} trên OmPraMani.`,
          url,
        });
        return;
      } catch (err) {
        // Fallback to clipboard if share cancelled or failed
        if ((err as Error).name === "AbortError") {
          return;
        }
      }
    }

    try {
      await navigator.clipboard.writeText(url);
      setCopied(true);
      setTimeout(() => setCopied(false), 2500);
    } catch {
      // In case clipboard write fails
      prompt("Sao chép liên kết địa điểm:", url);
    }
  };

  return (
    <button
      type="button"
      onClick={handleShare}
      className="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-label-md font-medium bg-surface-container-low hover:bg-surface-container-high border border-outline-variant/50 text-on-surface transition-colors cursor-pointer"
      title="Chia sẻ đường dẫn địa điểm"
      aria-label={`Chia sẻ thông tin ${name}`}
    >
      <span className="material-symbols-outlined text-[17px] text-primary">
        {copied ? "check" : "share"}
      </span>
      <span>{copied ? "Đã chép link" : "Chia sẻ"}</span>
    </button>
  );
}
