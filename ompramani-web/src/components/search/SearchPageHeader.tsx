import React from "react";

interface SearchPageHeaderProps {
  query?: string;
  totalResults?: number;
}

export function SearchPageHeader({
  query,
  totalResults,
}: SearchPageHeaderProps) {
  const hasQuery = Boolean(query && query.trim());

  return (
    <div className="space-y-2">
      <div className="flex items-center gap-2">
        <span className="w-2 h-2 rounded-full bg-primary" />
        <span className="font-label-caps text-label-caps text-primary uppercase">
          Cổng Tra Cứu Chánh Pháp
        </span>
      </div>

      <h1 className="font-headline-xl text-3xl sm:text-4xl font-bold text-on-surface tracking-tight">
        {hasQuery ? (
          <>
            Kết quả cho <span className="text-primary font-serif">“{query}”</span>
          </>
        ) : (
          "Tìm Kiếm Toàn Diện"
        )}
      </h1>

      <p className="font-body-md text-on-surface-variant max-w-2xl">
        {hasQuery && typeof totalResults === "number" ? (
          <>
            Đã tìm thấy <strong className="text-primary font-medium">{totalResults}</strong> kết quả phù hợp trên toàn hệ thống.
          </>
        ) : (
          "Tra cứu toàn diện Kinh tạng, Thần chú, Chư Phật & Bồ Tát, Bài viết pháp thoại và Phương tiện tu tập."
        )}
      </p>
    </div>
  );
}
