"use client";

import React, { useState } from "react";
import Image, { ImageProps } from "next/image";
import { isCloudinaryUrl } from "@/lib/utils/media";

export interface SafeImageProps extends Omit<ImageProps, "src"> {
  src: string | null | undefined;
  fallback?: React.ReactNode;
  fallbackIcon?: string;
  fallbackText?: string;
}

export function SafeImage({
  src,
  alt = "",
  fallback,
  fallbackIcon = "image",
  fallbackText,
  unoptimized,
  fill,
  className,
  onError,
  ...rest
}: SafeImageProps) {
  const [hasError, setHasError] = useState(false);

  // If source is missing, blank, or has errored out, render graceful spiritual fallback
  if (!src || hasError) {
    if (fallback) {
      return <>{fallback}</>;
    }

    const containerClasses = fill
      ? "absolute inset-0 w-full h-full"
      : "w-full h-full min-h-[120px]";

    return (
      <div
        className={`${containerClasses} flex flex-col items-center justify-center bg-surface-container-low text-outline/50 p-4 select-none ${
          className || ""
        }`}
        role="img"
        aria-label={alt || "Hình ảnh đang cập nhật"}
      >
        <span className="material-symbols-outlined text-[40px] text-primary/50 mb-1">
          {fallbackIcon}
        </span>
        {fallbackText && (
          <span className="font-label-sm text-xs text-outline tracking-wider text-center">
            {fallbackText}
          </span>
        )}
      </div>
    );
  }

  // Use unoptimized for Cloudinary CDN to prevent Next.js image proxy 404s and reduce latency
  const shouldBeUnoptimized =
    typeof unoptimized === "boolean" ? unoptimized : isCloudinaryUrl(src);

  return (
    <Image
      src={src}
      alt={alt}
      fill={fill}
      unoptimized={shouldBeUnoptimized}
      className={className}
      onError={(e) => {
        setHasError(true);
        if (onError) {
          onError(e);
        }
      }}
      {...rest}
    />
  );
}
