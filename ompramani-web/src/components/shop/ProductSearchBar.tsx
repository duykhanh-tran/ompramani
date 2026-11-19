"use client";

import React, { useEffect, useState } from "react";

interface ProductSearchBarProps {
  value: string;
  onChange: (value: string) => void;
  placeholder?: string;
}

export function ProductSearchBar({
  value,
  onChange,
  placeholder = "Tìm kiếm pháp khí, tràng hạt, kinh sách...",
}: ProductSearchBarProps) {
  const [internalValue, setInternalValue] = useState(value);

  // Sync internal value when parent prop changes
  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    setInternalValue(value);
  }, [value]);

  // Debounce search input
  useEffect(() => {
    const handler = setTimeout(() => {
      if (internalValue !== value) {
        onChange(internalValue);
      }
    }, 350);

    return () => clearTimeout(handler);
  }, [internalValue, value, onChange]);

  return (
    <div className="relative w-full">
      <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-on-surface-variant">
        <span className="material-symbols-outlined text-[20px]">search</span>
      </div>

      <input
        type="text"
        value={internalValue}
        onChange={(e) => setInternalValue(e.target.value)}
        placeholder={placeholder}
        className="w-full pl-10 pr-10 py-2.5 rounded-2xl bg-surface-container-lowest border border-outline-variant/40 text-on-surface placeholder:text-on-surface-variant/60 text-sm focus:outline-hidden focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all shadow-xs"
      />

      {internalValue && (
        <button
          type="button"
          onClick={() => {
            setInternalValue("");
            onChange("");
          }}
          className="absolute inset-y-0 right-0 pr-3.5 flex items-center text-on-surface-variant/70 hover:text-on-surface cursor-pointer"
          aria-label="Xóa từ khóa tìm kiếm"
        >
          <span className="material-symbols-outlined text-[18px]">cancel</span>
        </button>
      )}
    </div>
  );
}
