"use client";

import React, { useState, useEffect } from "react";

interface TempleSearchBarProps {
  value: string;
  onChange: (value: string) => void;
  onSubmit?: () => void;
  placeholder?: string;
}

export function TempleSearchBar({
  value,
  onChange,
  onSubmit,
  placeholder = "Tìm tên chùa, thiền viện, tịnh xá, đạo tràng...",
}: TempleSearchBarProps) {
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

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onChange(query);
    if (onSubmit) onSubmit();
  };

  return (
    <form
      onSubmit={handleSubmit}
      role="search"
      className="relative flex items-center w-full"
    >
      <div className="relative w-full flex items-center">
        <span
          className="material-symbols-outlined absolute left-4 text-on-surface-variant text-[22px] pointer-events-none select-none"
          aria-hidden="true"
        >
          search
        </span>

        <input
          type="text"
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          onKeyDown={handleKeyDown}
          placeholder={placeholder}
          aria-label="Tìm kiếm tự viện và đạo tràng"
          className="w-full pl-12 pr-24 py-3.5 bg-surface-container-lowest border border-outline-variant/60 rounded-xl text-on-surface placeholder:text-on-surface-variant/70 font-body-md text-body-md shadow-xs focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary transition-all"
        />

        <div className="absolute right-3 flex items-center gap-1.5">
          {query.trim().length > 0 && (
            <button
              type="button"
              onClick={handleClear}
              aria-label="Xóa nội dung tìm kiếm"
              className="p-1 rounded-full text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high transition-colors"
            >
              <span className="material-symbols-outlined text-[18px]">close</span>
            </button>
          )}

          <button
            type="submit"
            aria-label="Thực hiện tìm kiếm"
            className="px-3.5 py-1.5 bg-primary text-on-primary font-label-md text-label-md font-medium rounded-lg hover:bg-primary/90 transition-colors shadow-xs"
          >
            Tìm
          </button>
        </div>
      </div>
    </form>
  );
}
