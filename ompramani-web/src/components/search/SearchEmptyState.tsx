"use client";

import React from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";

interface SearchEmptyStateProps {
  query?: string;
  hasFilters?: boolean;
}

const SEARCH_PROMPTS = [
  {
    category: "Kinh Điển",
    icon: "menu_book",
    examples: ["Kinh A Di Đà", "Kinh Bát Nhã", "Kinh Kim Cương", "Kinh Pháp Cú"],
  },
  {
    category: "Thần Chú",
    icon: "graphic_eq",
    examples: ["Chú Đại Bi", "Chú Lăng Nghiêm", "Chú Dược Sư", "Om Mani"],
  },
  {
    category: "Chư Phật & Bồ Tát",
    icon: "self_improvement",
    examples: ["Quán Thế Âm", "Thích Ca Mâu Ni", "Địa Tạng Vương", "Dược Sư Lưu Ly"],
  },
  {
    category: "Pháp Khí & Tu Tập",
    icon: "spa",
    examples: ["Tràng hạt", "Chuông mõ", "Thiền định", "Niệm Phật"],
  },
];

export function SearchEmptyState({
  query,
  hasFilters,
}: SearchEmptyStateProps) {
  const router = useRouter();
  const hasQuery = Boolean(query && query.trim());

  const handlePromptClick = (example: string) => {
    router.push(`/tim-kiem?q=${encodeURIComponent(example)}`);
  };

  if (!hasQuery) {
    return (
      <div className="py-12 px-4 max-w-3xl mx-auto space-y-10">
        <div className="text-center space-y-3">
          <div className="w-16 h-16 rounded-full bg-surface-container flex items-center justify-center mx-auto text-primary shadow-xs">
            <span className="material-symbols-outlined text-[32px]">
              auto_stories
            </span>
          </div>
          <h2 className="font-headline-lg text-2xl font-bold text-on-surface">
            Bắt đầu tra cứu kho tàng chánh pháp
          </h2>
          <p className="font-body-md text-on-surface-variant max-w-lg mx-auto">
            Nhập tên kinh tạng, thần chú, tôn tượng Chư Phật &amp; Bồ Tát hoặc phương tiện tu tập vào ô tìm kiếm phía trên.
          </p>
        </div>

        {/* Guided Prompts */}
        <div className="space-y-4 pt-4 border-t border-outline-variant/30">
          <div className="text-center">
            <span className="font-label-caps text-xs text-outline uppercase tracking-wider">
              Gợi ý từ khóa tìm kiếm phổ biến
            </span>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            {SEARCH_PROMPTS.map((group, idx) => (
              <div
                key={idx}
                className="p-4 rounded-2xl bg-surface-container-lowest border border-outline-variant/40 space-y-2.5"
              >
                <div className="flex items-center gap-2 text-primary font-semibold text-sm">
                  <span className="material-symbols-outlined text-[18px]">
                    {group.icon}
                  </span>
                  <span>{group.category}</span>
                </div>

                <div className="flex flex-wrap gap-1.5">
                  {group.examples.map((ex, exIdx) => (
                    <button
                      key={exIdx}
                      type="button"
                      onClick={() => handlePromptClick(ex)}
                      className="px-2.5 py-1 rounded-lg text-xs bg-surface-container-low hover:bg-surface-container hover:text-primary text-on-surface-variant border border-outline-variant/30 transition-colors"
                    >
                      {ex}
                    </button>
                  ))}
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    );
  }

  // State: Query provided, but 0 results found
  return (
    <div className="py-12 px-4 max-w-lg mx-auto text-center space-y-6 bg-surface-container-lowest rounded-3xl border border-outline-variant/30 my-8 shadow-xs">
      <div className="w-16 h-16 rounded-full bg-surface-container flex items-center justify-center mx-auto text-primary">
        <span className="material-symbols-outlined text-[32px]">
          search_off
        </span>
      </div>

      <div className="space-y-2">
        <h2 className="font-headline-lg text-xl sm:text-2xl font-bold text-on-surface">
          Không tìm thấy kết quả phù hợp
        </h2>
        <p className="font-body-sm text-sm text-on-surface-variant leading-relaxed">
          Không tìm thấy nội dung nào khớp với từ khóa{" "}
          <strong className="text-primary font-semibold">“{query}”</strong> trên hệ thống.
        </p>
      </div>

      <div className="p-4 rounded-xl bg-surface-container-low text-left space-y-2 text-xs text-on-surface-variant border border-outline-variant/30">
        <div className="font-semibold text-on-surface flex items-center gap-1.5">
          <span className="material-symbols-outlined text-[16px] text-primary">
            lightbulb
          </span>
          <span>Gợi ý tìm kiếm:</span>
        </div>
        <ul className="list-disc list-inside space-y-1 pl-1">
          <li>Kiểm tra lại chính tả hoặc thử viết không dấu / có dấu tiếng Việt.</li>
          <li>Thử với từ khóa ngắn gọn hơn (ví dụ: <code className="text-primary">Quán Âm</code> thay vì câu dài).</li>
          {hasFilters && (
            <li>
              Thử chọn bộ lọc loại nội dung về <strong>“Tất cả”</strong> để mở rộng phạm vi.
            </li>
          )}
        </ul>
      </div>

      <div className="pt-2 flex items-center justify-center gap-3">
        {hasFilters && (
          <Link
            href={`/tim-kiem?q=${encodeURIComponent(query || "")}`}
            className="px-4 py-2 rounded-full bg-surface-container text-on-surface hover:text-primary font-label-md text-xs font-semibold border border-outline-variant/40 transition-colors"
          >
            Bỏ tất cả bộ lọc
          </Link>
        )}
        <Link
          href="/kinh"
          className="px-5 py-2.5 rounded-full bg-primary text-on-primary font-label-md text-xs sm:text-sm font-semibold hover:bg-on-primary-container transition-colors shadow-xs"
        >
          Khám phá Thư Viện Kinh
        </Link>
      </div>
    </div>
  );
}
