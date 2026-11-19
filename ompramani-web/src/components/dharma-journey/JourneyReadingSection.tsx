"use client";

import React, { useState, useEffect } from "react";
import Link from "next/link";
import { SutraReadingProgress } from "@/types/api";
import { getReadingProgresses } from "@/lib/api/reading-progress";
import { formatArticleDate } from "@/lib/utils/sacred";

export function JourneyReadingSection() {
  const [readingList, setReadingList] = useState<SutraReadingProgress[]>([]);
  const [isLoading, setIsLoading] = useState<boolean>(true);

  useEffect(() => {
    let ignore = false;
    getReadingProgresses({ per_page: 3 })
      .then((res) => {
        if (!ignore) {
          setReadingList(res.data || []);
          setIsLoading(false);
        }
      })
      .catch(() => {
        if (!ignore) setIsLoading(false);
      });

    return () => {
      ignore = true;
    };
  }, []);

  return (
    <div className="bg-surface-container-lowest rounded-3xl p-6 sm:p-8 border border-outline-variant/40 shadow-xs space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <div className="flex items-center gap-2 mb-1">
            <span className="w-2 h-2 rounded-full bg-primary" />
            <span className="font-label-caps text-xs text-primary uppercase font-semibold">
              Kinh Điển Đang Đọc
            </span>
          </div>
          <h2 className="font-headline-xl text-xl sm:text-2xl font-bold text-on-surface">
            Tiếp Tục Tụng Đọc
          </h2>
          <p className="font-body-sm text-xs text-on-surface-variant mt-0.5">
            Vị trí đọc dở được ghi nhớ tự động để bạn tiếp tục công phu mỗi ngày
          </p>
        </div>

        <div className="flex items-center gap-3">
          <Link
            href="/tai-khoan/thu-vien-kinh"
            className="text-xs font-label-md text-primary hover:underline flex items-center gap-1"
          >
            <span>Tủ kinh cá nhân</span>
            <span className="material-symbols-outlined text-[16px]">
              chevron_right
            </span>
          </Link>
        </div>
      </div>

      {isLoading ? (
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          {[1, 2, 3].map((n) => (
            <div
              key={n}
              className="h-32 rounded-2xl bg-surface-container-low animate-pulse"
            />
          ))}
        </div>
      ) : readingList.length === 0 ? (
        <div className="py-8 text-center border border-dashed border-outline-variant/50 rounded-2xl p-6 space-y-3 bg-surface-container-low/30">
          <span className="material-symbols-outlined text-outline text-[36px]">
            auto_stories
          </span>
          <p className="font-headline-md text-sm font-semibold text-on-surface">
            Chưa có tiến trình đọc kinh nào
          </p>
          <p className="font-body-sm text-xs text-outline max-w-md mx-auto">
            Khi bạn đọc bất kỳ bộ kinh nào trong thư viện, vị trí phẩm đang đọc sẽ tự động xuất hiện tại đây.
          </p>
          <div className="pt-1">
            <Link
              href="/kinh"
              className="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-primary text-on-primary font-label-md text-xs font-semibold hover:bg-primary/90 transition-all shadow-xs"
            >
              <span>Khám phá Thư viện Kinh</span>
              <span className="material-symbols-outlined text-[16px]">
                arrow_forward
              </span>
            </Link>
          </div>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          {readingList.map((item) => (
            <div
              key={item.id}
              className="bg-surface-container-low rounded-2xl p-4 border border-outline-variant/30 flex flex-col justify-between space-y-3 hover:border-primary/40 transition-colors"
            >
              <div className="space-y-1.5">
                {item.sutra?.tradition && (
                  <span className="inline-block px-2 py-0.5 rounded-full bg-surface-container-lowest text-primary font-label-md text-[10px] font-semibold">
                    {item.sutra.tradition.name}
                  </span>
                )}
                <h3 className="font-headline-md text-sm font-bold text-on-surface line-clamp-1">
                  {item.sutra?.title}
                </h3>
                <p className="font-body-sm text-[11px] text-outline line-clamp-1">
                  {item.sutra?.translator
                    ? `Dịch giả: ${item.sutra.translator}`
                    : item.version?.translator
                      ? `Dịch: ${item.version.translator}`
                      : item.version?.name || "Kinh điển"}
                </p>

                <div className="pt-1 text-xs font-label-md text-primary flex items-center gap-1 truncate">
                  <span className="material-symbols-outlined text-[14px]">
                    bookmark
                  </span>
                  <span className="truncate">Đang đọc: {item.section?.title || "Phẩm kinh"}</span>
                </div>
              </div>

              <div className="flex items-center justify-between pt-2 border-t border-outline-variant/20 text-xs">
                <span className="text-[11px] text-outline font-label-md">
                  {formatArticleDate(item.last_read_at)}
                </span>
                <Link
                  href={item.continue_url || `/kinh/${item.sutra?.slug || ""}`}
                  className="px-3 py-1 rounded-xl bg-primary text-on-primary font-label-md text-xs font-semibold hover:bg-primary/90 transition-all flex items-center gap-1 shadow-xs"
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
  );
}
