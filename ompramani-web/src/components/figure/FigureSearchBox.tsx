"use client";

import React, { useState, useTransition } from "react";
import { useRouter, useSearchParams } from "next/navigation";

export function FigureSearchBox() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const currentSearchParam = searchParams.get("search") || "";
  const [prevSearchParam, setPrevSearchParam] = useState(currentSearchParam);
  const [query, setQuery] = useState(currentSearchParam);
  const [isPending, startTransition] = useTransition();

  if (currentSearchParam !== prevSearchParam) {
    setPrevSearchParam(currentSearchParam);
    setQuery(currentSearchParam);
  }

  const handleSearch = (term: string) => {
    setQuery(term);

    startTransition(() => {
      const params = new URLSearchParams(searchParams.toString());
      if (term.trim()) {
        params.set("search", term.trim());
      } else {
        params.delete("search");
      }
      params.delete("page");

      const str = params.toString();
      router.push(str ? `/phat-bo-tat?${str}` : "/phat-bo-tat");
    });
  };

  const handleClear = () => {
    setQuery("");
    const params = new URLSearchParams(searchParams.toString());
    params.delete("search");
    params.delete("page");
    const str = params.toString();
    router.push(str ? `/phat-bo-tat?${str}` : "/phat-bo-tat");
  };

  return (
    <div className="relative w-full max-w-md">
      <span className="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-outline text-[20px] pointer-events-none">
        search
      </span>
      <input
        type="text"
        suppressHydrationWarning
        value={query}
        onChange={(e) => handleSearch(e.target.value)}
        placeholder="Tìm danh hiệu Phật, Bồ Tát (VD: Quán Âm, Dược Sư)..."
        aria-label="Tìm danh hiệu Phật và Bồ Tát"
        className="w-full pl-10 pr-10 py-2.5 bg-surface-container-low focus:bg-surface-container-lowest border border-outline-variant/60 rounded-full text-on-surface placeholder:text-outline font-body-sm text-[14px] focus:outline-none focus:ring-2 focus:ring-primary/30 transition-all shadow-xs"
      />
      {query && (
        <button
          type="button"
          suppressHydrationWarning
          onClick={handleClear}
          className="absolute right-3.5 top-1/2 -translate-y-1/2 text-outline hover:text-on-surface"
          aria-label="Xóa từ khóa tìm kiếm"
        >
          <span className="material-symbols-outlined text-[18px]">close</span>
        </button>
      )}
      {isPending && (
        <span className="absolute right-9 top-1/2 -translate-y-1/2 w-3.5 h-3.5 border-2 border-primary border-t-transparent rounded-full animate-spin"></span>
      )}
    </div>
  );
}
