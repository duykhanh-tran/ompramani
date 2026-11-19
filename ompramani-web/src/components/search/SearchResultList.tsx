import React from "react";
import { NormalizedSearchResult } from "@/types/api";
import { SearchResultCard } from "./SearchResultCard";

interface SearchResultListProps {
  items: NormalizedSearchResult[];
  query: string;
}

export function SearchResultList({ items, query }: SearchResultListProps) {
  if (items.length === 0) {
    return null;
  }

  return (
    <section aria-label="Danh sách kết quả tìm kiếm" className="space-y-4">
      {items.map((item, index) => (
        <SearchResultCard
          key={`${item.type}-${item.id}-${index}`}
          item={item}
          query={query}
        />
      ))}
    </section>
  );
}
