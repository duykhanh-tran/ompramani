"use client";

import React, { useState, useEffect } from "react";

interface VideoSearchBarProps {
  value: string;
  onChange: (query: string) => void;
  onSubmit?: () => void;
  placeholder?: string;
}

export function VideoSearchBar({
  value,
  onChange,
  onSubmit,
  placeholder = "Tìm theo tiêu đề pháp thoại, bài giảng, giảng sư...",
}: VideoSearchBarProps) {
  const [query, setQuery] = useState(value);

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    setQuery(value);
  }, [value]);

  const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
    if (e.key === "Enter") {
      e.preventDefault();
      onChange(query);
      if (onSubmit) onSubmit();
    }
  };

  const handleClear = () => {
    setQuery("");
    onChange("");
    if (onSubmit) onSubmit();
  };

  const handleFormSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onChange(query);
    if (onSubmit) onSubmit();
  };

  return (
    <form
      onSubmit={handleFormSubmit}
      className="relative flex items-center w-full"
      role="search"
    >
      <div className="relative flex-1">
        <span className="absolute left-4 top-1/2 -translate-y-1/2 material-symbols-outlined text-outline text-[22px] pointer-events-none">
          search
        </span>

        <input
          type="search"
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          onKeyDown={handleKeyDown}
          placeholder={placeholder}
          aria-label="Tìm kiếm video Phật pháp"
          className="w-full pl-12 pr-10 py-3.5 bg-surface-container-lowest text-on-surface rounded-2xl border border-outline-variant/60 focus:border-primary focus:outline-hidden focus:ring-2 focus:ring-primary/20 text-sm sm:text-base placeholder:text-outline-variant/80 transition-all shadow-xs"
        />

        {query && (
          <button
            type="button"
            onClick={handleClear}
            className="absolute right-3.5 top-1/2 -translate-y-1/2 p-1 rounded-full text-outline hover:text-on-surface hover:bg-surface-container-high transition-colors cursor-pointer"
            aria-label="Xóa từ khóa tìm kiếm"
          >
            <span className="material-symbols-outlined text-[18px]">close</span>
          </button>
        )}
      </div>

      <button
        type="submit"
        className="ml-2.5 px-5 sm:px-6 py-3.5 rounded-2xl bg-primary text-on-primary hover:bg-primary/90 font-label-md text-sm font-semibold transition-colors cursor-pointer shrink-0 shadow-xs flex items-center gap-1.5"
      >
        <span>Tìm</span>
      </button>
    </form>
  );
}
