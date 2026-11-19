"use client";

import React, { useState, useEffect, useMemo } from "react";
import Link from "next/link";
import Image from "next/image";
import { useAuth } from "@/context/AuthContext";
import { SutraReadingProgress } from "@/types/api";
import { getReadingProgresses } from "@/lib/api/reading-progress";
import { formatArticleDate } from "@/lib/utils/sacred";
import { resolveMediaUrl } from "@/lib/utils/media";

export function ContinueReadingSection() {
  const { isAuthenticated, isLoading: isAuthLoading } = useAuth();
  const [progressList, setProgressList] = useState<SutraReadingProgress[]>([]);
  const [isLoading, setIsLoading] = useState<boolean>(isAuthenticated);

  useEffect(() => {
    let ignore = false;
    if (isAuthenticated) {
      getReadingProgresses({ per_page: 3 })
        .then((res) => {
          if (!ignore) {
            setProgressList(res.data || []);
            setIsLoading(false);
          }
        })
        .catch(() => {
          if (!ignore) {
            setIsLoading(false);
          }
        });
    }

    return () => {
      ignore = true;
    };
  }, [isAuthenticated]);

  const displayCards = useMemo(() => {
    const list: Array<{
      key: string;
      title: string;
      subtitle: string;
      thumbnail: string;
      tradition?: string;
      sectionLabel: string;
      sectionTitle: string;
      lastRead?: string;
      href: string;
      buttonText: string;
      isRecommended?: boolean;
    }> = [];

    // Only add user's real reading progress from backend
    for (const item of progressList) {
      const sutraSlug = item.sutra?.slug || "";
      const rawThumb = item.sutra?.thumbnail;
      const thumbUrl = resolveMediaUrl(rawThumb) || rawThumb || "";
      list.push({
        key: `progress-${item.id}`,
        title: item.sutra?.title || "Kinh điển",
        subtitle:
          item.sutra?.translator
            ? `Dịch giả: ${item.sutra.translator}`
            : item.version?.translator
              ? `Bản dịch: ${item.version.translator}`
              : item.version?.name || "Kinh văn Đại Thừa",
        thumbnail: thumbUrl,
        tradition: item.sutra?.tradition?.name || "Đang đọc",
        sectionLabel: "Đang đọc dở",
        sectionTitle: item.section?.title || "Phẩm kinh đang tụng niệm",
        lastRead: formatArticleDate(item.last_read_at),
        href: item.continue_url || `/kinh/${sutraSlug}`,
        buttonText: "Đọc tiếp",
        isRecommended: false,
      });
    }

    return list.slice(0, 3);
  }, [progressList]);

  return (
    <section className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <span className="font-label-caps text-label-caps text-primary uppercase">
            Tiến trình tu học
          </span>
          <h2 className="font-headline-xl text-headline-xl text-on-surface font-semibold tracking-tight">
            Tiếp Tục Tụng Kinh
          </h2>
        </div>
        <Link
          href={isAuthenticated ? "/tai-khoan/thu-vien-kinh" : "/kinh"}
          className="font-label-lg text-label-lg text-primary hover:underline flex items-center gap-1"
        >
          <span>{isAuthenticated ? "Tủ kinh của tôi" : "Xem tất cả kinh văn"}</span>
          <span className="material-symbols-outlined text-[18px]">
            chevron_right
          </span>
        </Link>
      </div>

      {isAuthLoading || (isAuthenticated && isLoading) ? (
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          {[1, 2, 3].map((n) => (
            <div
              key={n}
              className="bg-surface-container-lowest rounded-2xl h-56 animate-pulse border border-outline-variant/30"
            />
          ))}
        </div>
      ) : !isAuthenticated ? (
        /* Guest state: invitation to log in or browse */
        <div className="bg-surface-container-lowest rounded-3xl p-8 sm:p-10 border border-outline-variant/40 shadow-xs flex flex-col md:flex-row items-center justify-between gap-6">
          <div className="space-y-2 text-center md:text-left">
            <div className="flex items-center justify-center md:justify-start gap-2 text-primary">
              <span className="material-symbols-outlined text-[24px]">
                auto_stories
              </span>
              <span className="font-label-caps text-xs uppercase font-semibold">
                Lưu Dấu Hành Trình
              </span>
            </div>
            <h3 className="font-headline-md text-xl font-bold text-on-surface">
              Ghi nhớ từng phẩm kinh bạn đang đọc dở
            </h3>
            <p className="font-body-md text-sm text-on-surface-variant max-w-xl">
              Đăng nhập tài khoản Phật tử để hệ thống tự động lưu vị trí trang và phẩm kinh,
              giúp bạn dễ dàng tiếp tục công phu tụng niệm mỗi ngày.
            </p>
          </div>

          <div className="flex items-center gap-3 shrink-0">
            <Link
              href="/dang-nhap"
              className="px-5 py-2.5 rounded-2xl bg-primary text-on-primary font-label-md text-xs font-semibold hover:bg-primary/90 transition-all shadow-xs"
            >
              Đăng nhập ngay
            </Link>
            <Link
              href="/kinh"
              className="px-5 py-2.5 rounded-2xl bg-surface-container-low hover:bg-surface-container text-on-surface font-label-md text-xs font-semibold transition-all border border-outline-variant/40"
            >
              Duyệt kinh văn
            </Link>
          </div>
        </div>
      ) : displayCards.length === 0 ? (
        /* Authenticated but no reading history yet */
        <div className="bg-surface-container-lowest rounded-3xl p-8 sm:p-10 border border-outline-variant/40 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-6">
          <div className="space-y-2 text-center sm:text-left">
            <div className="flex items-center justify-center sm:justify-start gap-2 text-primary">
              <span className="material-symbols-outlined text-[22px]">
                menu_book
              </span>
              <span className="font-label-caps text-xs uppercase font-semibold">
                Bắt Đầu Tu Học
              </span>
            </div>
            <h3 className="font-headline-md text-lg font-bold text-on-surface">
              Bạn chưa có tiến trình đọc kinh nào
            </h3>
            <p className="font-body-md text-sm text-on-surface-variant max-w-lg">
              Hãy chọn một bộ kinh trong thư viện để bắt đầu đọc. Vị trí đọc của bạn
              sẽ được tự động lưu lại tại đây.
            </p>
          </div>

          <Link
            href="/kinh"
            className="px-5 py-2.5 rounded-2xl bg-primary text-on-primary font-label-md text-xs font-semibold hover:bg-primary/90 transition-all flex items-center gap-1.5 shadow-xs shrink-0"
          >
            <span>Khám phá Thư viện Kinh</span>
            <span className="material-symbols-outlined text-[16px]">
              arrow_forward
            </span>
          </Link>
        </div>
      ) : (
        /* Authenticated with reading cards (progress + recommended) */
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          {displayCards.map((card) => (
            <div
              key={card.key}
              className="bg-surface-container-lowest rounded-2xl overflow-hidden shadow-xs hover:shadow-md transition-all border border-outline-variant/35 hover:border-primary/40 flex flex-col justify-between group"
            >
              {/* Card Image / Banner */}
              <div className="h-56 bg-[#F8F5F0] relative overflow-hidden flex items-center justify-center border-b border-outline-variant/20">
                {card.thumbnail ? (
                  <>
                    <Image
                      src={card.thumbnail}
                      alt=""
                      fill
                      className="object-cover blur-xl opacity-30 scale-110 pointer-events-none"
                      aria-hidden="true"
                    />
                    <Image
                      src={card.thumbnail}
                      alt={card.title}
                      fill
                      sizes="(max-width: 768px) 100vw, 33vw"
                      className="object-contain p-2 relative z-[1] group-hover:scale-[1.03] transition-transform duration-500 drop-shadow-sm"
                    />
                  </>
                ) : (
                  <div className="w-full h-full flex flex-col items-center justify-center text-primary/70 bg-gradient-to-br from-surface-container to-surface-container-high">
                    <span className="material-symbols-outlined text-[48px]">
                      menu_book
                    </span>
                  </div>
                )}
                <div className="absolute inset-0 bg-gradient-to-t from-black/45 via-transparent to-transparent pointer-events-none" />

                {card.tradition && (
                  <span
                    className={`absolute top-3 left-3 px-3 py-1 rounded-full font-label-md text-xs font-semibold backdrop-blur-md shadow-2xs ${
                      card.isRecommended
                        ? "bg-secondary-container/90 text-on-secondary-container"
                        : "bg-surface-container-lowest/90 text-primary"
                    }`}
                  >
                    {card.tradition}
                  </span>
                )}
              </div>

              {/* Content & Progress */}
              <div className="p-5 flex flex-col justify-between flex-1 space-y-4">
                <div>
                  <h3 className="font-headline-md text-base sm:text-lg font-bold text-on-surface group-hover:text-primary transition-colors line-clamp-1">
                    {card.title}
                  </h3>
                  <p className="font-body-sm text-xs text-outline line-clamp-1 mt-1">
                    {card.subtitle}
                  </p>
                </div>

                {/* Section Marker */}
                <div className="p-3 rounded-xl bg-surface-container-low border border-outline-variant/30 flex items-center gap-2.5">
                  <span className="material-symbols-outlined text-primary text-[20px] shrink-0">
                    auto_stories
                  </span>
                  <div className="min-w-0 flex-1">
                    <span className="font-label-caps text-[10px] text-outline uppercase block font-medium">
                      {card.sectionLabel}
                    </span>
                    <span className="font-label-md text-xs font-semibold text-on-surface truncate block">
                      {card.sectionTitle}
                    </span>
                  </div>
                </div>

                {/* Footer Action */}
                <div className="flex items-center justify-between pt-1 text-xs text-outline border-t border-outline-variant/20">
                  <span className="font-label-md text-[11px] text-outline">
                    {card.lastRead}
                  </span>
                  <Link
                    href={card.href}
                    className="px-4 py-2 rounded-xl bg-primary text-on-primary font-label-md text-xs font-semibold hover:bg-primary/90 transition-all flex items-center gap-1 shadow-xs"
                  >
                    <span>{card.buttonText}</span>
                    <span className="material-symbols-outlined text-[14px]">
                      arrow_forward
                    </span>
                  </Link>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}
    </section>
  );
}
