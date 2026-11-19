"use client";

import React, { useState, useEffect, useRef, useTransition } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import Link from "next/link";
import { getSearchSuggestions } from "@/lib/api/search";
import { SearchSuggestionItem } from "@/types/api";
import {
  resolveSuggestionHref,
  getSuggestionTypeLabel,
} from "@/lib/utils/sacred";

export function SearchMainInput() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const currentQ = searchParams.get("q") || "";

  const [inputVal, setInputVal] = useState(currentQ);
  const [prevUrlQ, setPrevUrlQ] = useState(currentQ);
  const [suggestions, setSuggestions] = useState<SearchSuggestionItem[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [isDropdownOpen, setIsDropdownOpen] = useState(false);
  const [activeIndex, setActiveIndex] = useState(-1);
  const [isPending, startTransition] = useTransition();

  const containerRef = useRef<HTMLDivElement>(null);
  const abortControllerRef = useRef<AbortController | null>(null);

  // Sync if URL search changes from back/forward
  if (currentQ !== prevUrlQ) {
    setPrevUrlQ(currentQ);
    setInputVal(currentQ);
  }

  const handleInputChange = (val: string) => {
    setInputVal(val);
    setActiveIndex(-1);
    if (val.trim().length < 2) {
      setSuggestions([]);
      setIsDropdownOpen(false);
      setIsLoading(false);
    }
  };

  // Handle autocomplete debounce with AbortController
  useEffect(() => {
    const trimmed = inputVal.trim();
    if (trimmed.length < 2) {
      return;
    }

    if (abortControllerRef.current) {
      abortControllerRef.current.abort();
    }
    const controller = new AbortController();
    abortControllerRef.current = controller;

    const timer = setTimeout(async () => {
      setIsLoading(true);
      try {
        const res = await getSearchSuggestions(trimmed, controller.signal, 6);
        if (!controller.signal.aborted) {
          setSuggestions(res);
          setIsDropdownOpen(true);
          setActiveIndex(-1);
        }
      } catch (err) {
        if (
          err instanceof Error &&
          (err.name === "AbortError" || err.message.toLowerCase().includes("abort"))
        ) {
          return;
        }
        if (!controller.signal.aborted) {
          setSuggestions([]);
        }
      } finally {
        if (!controller.signal.aborted) {
          setIsLoading(false);
        }
      }
    }, 300);

    return () => {
      clearTimeout(timer);
      controller.abort();
    };
  }, [inputVal]);

  // Click outside listener
  useEffect(() => {
    function handleClickOutside(event: MouseEvent) {
      if (
        containerRef.current &&
        !containerRef.current.contains(event.target as Node)
      ) {
        setIsDropdownOpen(false);
      }
    }
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  const executeSearch = (queryText: string) => {
    const q = queryText.trim();
    setIsDropdownOpen(false);
    if (!q) return;

    startTransition(() => {
      const params = new URLSearchParams(searchParams.toString());
      params.set("q", q);
      params.delete("page"); // Reset page on new query
      router.push(`/tim-kiem?${params.toString()}`);
    });
  };

  const handleClear = () => {
    setInputVal("");
    setSuggestions([]);
    setIsDropdownOpen(false);
    startTransition(() => {
      const params = new URLSearchParams(searchParams.toString());
      params.delete("q");
      params.delete("page");
      const str = params.toString();
      router.push(str ? `/tim-kiem?${str}` : "/tim-kiem");
    });
  };

  const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
    if (!isDropdownOpen || suggestions.length === 0) {
      if (e.key === "Enter") {
        e.preventDefault();
        executeSearch(inputVal);
      }
      return;
    }

    switch (e.key) {
      case "ArrowDown":
        e.preventDefault();
        setActiveIndex((prev) => (prev < suggestions.length - 1 ? prev + 1 : -1));
        break;
      case "ArrowUp":
        e.preventDefault();
        setActiveIndex((prev) => (prev > -1 ? prev - 1 : suggestions.length - 1));
        break;
      case "Enter":
        e.preventDefault();
        if (activeIndex >= 0 && activeIndex < suggestions.length) {
          const item = suggestions[activeIndex];
          setIsDropdownOpen(false);
          router.push(resolveSuggestionHref(item));
        } else {
          executeSearch(inputVal);
        }
        break;
      case "Escape":
        setIsDropdownOpen(false);
        setActiveIndex(-1);
        break;
      default:
        break;
    }
  };

  return (
    <div className="relative w-full max-w-3xl mx-auto" ref={containerRef}>
      <form
        role="search"
        onSubmit={(e) => {
          e.preventDefault();
          executeSearch(inputVal);
        }}
        className="relative flex items-center"
      >
        <span className="material-symbols-outlined absolute left-4.5 text-outline text-[24px] pointer-events-none select-none">
          search
        </span>

        <input
          type="text"
          role="combobox"
          aria-autocomplete="list"
          aria-expanded={isDropdownOpen && suggestions.length > 0}
          aria-controls="main-search-suggestions"
          aria-activedescendant={
            activeIndex >= 0 ? `main-suggestion-${activeIndex}` : undefined
          }
          suppressHydrationWarning
          value={inputVal}
          onChange={(e) => handleInputChange(e.target.value)}
          onKeyDown={handleKeyDown}
          onFocus={() => {
            if (suggestions.length > 0) setIsDropdownOpen(true);
          }}
          placeholder="Nhập tên kinh tạng, thần chú, tôn tượng Phật, bài viết hoặc pháp khí..."
          aria-label="Nhập từ khóa tìm kiếm"
          className="w-full pl-12 pr-28 py-3.5 bg-surface-container-low hover:bg-surface-container-lowest focus:bg-surface-container-lowest border border-outline-variant/60 rounded-full text-on-surface placeholder:text-outline font-body-lg text-base focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary transition-all shadow-sm"
        />

        {/* Clear & Loading indicators */}
        <div className="absolute right-3.5 flex items-center gap-2">
          {(isLoading || isPending) && (
            <span className="w-4 h-4 border-2 border-primary border-t-transparent rounded-full animate-spin"></span>
          )}

          {inputVal && (
            <button
              type="button"
              suppressHydrationWarning
              onClick={handleClear}
              className="p-1 rounded-full text-outline hover:text-on-surface hover:bg-surface-container transition-colors"
              aria-label="Xóa từ khóa tìm kiếm"
            >
              <span className="material-symbols-outlined text-[20px]">close</span>
            </button>
          )}

          <button
            type="submit"
            suppressHydrationWarning
            className="px-4 py-2 rounded-full bg-primary text-on-primary text-xs sm:text-sm font-label-md font-semibold hover:bg-on-primary-container transition-colors shadow-xs"
          >
            Tìm
          </button>
        </div>
      </form>

      {/* Autocomplete Suggestions Dropdown */}
      {isDropdownOpen && suggestions.length > 0 && (
        <div
          id="main-search-suggestions"
          role="listbox"
          className="absolute top-full left-0 right-0 mt-2 bg-surface-container-lowest border border-outline-variant/60 rounded-2xl shadow-xl p-2 z-50 max-h-80 overflow-y-auto"
        >
          <div className="font-label-caps text-[10px] text-outline px-3 py-1.5 uppercase">
            Gợi ý liên quan
          </div>

          {suggestions.map((item, idx) => (
            <Link
              key={idx}
              id={`main-suggestion-${idx}`}
              role="option"
              aria-selected={activeIndex === idx}
              href={resolveSuggestionHref(item)}
              onClick={() => {
                setIsDropdownOpen(false);
              }}
              className={`flex items-center justify-between p-2.5 rounded-xl transition-colors group ${
                activeIndex === idx
                  ? "bg-surface-container text-primary"
                  : "hover:bg-surface-container-low"
              }`}
            >
              <div className="min-w-0 pr-3">
                <div className="font-label-lg text-sm font-medium text-on-surface group-hover:text-primary truncate">
                  {item.title}
                </div>
                {item.subtitle && (
                  <div className="font-body-sm text-xs text-outline truncate mt-0.5">
                    {item.subtitle}
                  </div>
                )}
              </div>
              <span className="px-2.5 py-0.5 rounded-full text-[11px] font-label-caps bg-surface-container text-primary shrink-0 uppercase">
                {getSuggestionTypeLabel(item.type)}
              </span>
            </Link>
          ))}
        </div>
      )}
    </div>
  );
}
