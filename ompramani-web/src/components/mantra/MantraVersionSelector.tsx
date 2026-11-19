"use client";

import React from "react";
import { useRouter } from "next/navigation";
import { MantraVersion } from "@/types/api";

interface MantraVersionSelectorProps {
  mantraSlug: string;
  versions: MantraVersion[];
  activeVersionId: number;
}

export function MantraVersionSelector({
  mantraSlug,
  versions,
  activeVersionId,
}: MantraVersionSelectorProps) {
  const router = useRouter();

  if (versions.length <= 1) {
    const single = versions[0];
    if (!single) return null;
    return (
      <div className="flex flex-wrap items-center gap-2 text-[13px] font-label-md text-on-surface-variant bg-surface-container-low px-4 py-2.5 rounded-xl border border-outline-variant/30">
        <span className="material-symbols-outlined text-primary text-[18px]">
          translate
        </span>
        <span className="font-semibold text-on-surface">{single.name}</span>
        {single.script && (
          <span className="px-2 py-0.5 rounded bg-surface-container-lowest text-on-surface-variant text-[11px] border border-outline-variant/30">
            Văn tự: {single.script}
          </span>
        )}
        {single.language && (
          <span className="uppercase text-[10px] px-2 py-0.5 rounded bg-surface-container text-primary font-bold">
            {single.language}
          </span>
        )}
        {single.source && (
          <span className="text-outline">· Nguồn: {single.source}</span>
        )}
      </div>
    );
  }

  const handleSelectVersion = (versionId: number) => {
    router.push(`/chu/${mantraSlug}?version=${versionId}`);
  };

  return (
    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-surface-container-low p-4 rounded-2xl border border-outline-variant/30">
      <div className="flex items-center gap-2 text-sm font-label-lg font-semibold text-on-surface">
        <span className="material-symbols-outlined text-primary text-[20px]">
          auto_stories
        </span>
        <span>Phiên bản / Bản âm truyền:</span>
      </div>

      <div className="flex flex-wrap items-center gap-2" role="tablist">
        {versions.map((ver) => {
          const isSelected = ver.id === activeVersionId;
          return (
            <button
              key={ver.id}
              type="button"
              role="tab"
              aria-selected={isSelected}
              suppressHydrationWarning
              onClick={() => handleSelectVersion(ver.id)}
              className={`px-3.5 py-2 rounded-xl font-label-md text-[13px] transition-all flex items-center gap-1.5 cursor-pointer ${
                isSelected
                  ? "bg-primary text-on-primary font-semibold shadow-xs"
                  : "bg-surface-container-lowest text-on-surface hover:text-primary border border-outline-variant/40"
              }`}
            >
              <span>{ver.name}</span>
              {(ver.script || ver.language) && (
                <span
                  className={`text-[11px] ${
                    isSelected ? "text-primary-fixed" : "text-outline"
                  }`}
                >
                  ({[ver.script, ver.language?.toUpperCase()].filter(Boolean).join(" · ")})
                </span>
              )}
              {ver.is_default && (
                <span
                  className={`text-[10px] px-1.5 py-0.2 rounded-full ${
                    isSelected
                      ? "bg-secondary-container text-on-secondary-container"
                      : "bg-surface-container text-primary"
                  }`}
                >
                  Mặc định
                </span>
              )}
            </button>
          );
        })}
      </div>
    </div>
  );
}
