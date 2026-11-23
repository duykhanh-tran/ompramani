/* eslint-disable @next/next/no-img-element */
"use client";

import React, { useState, useEffect, useRef } from "react";
import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { useAuth } from "@/context/AuthContext";
import { getSearchSuggestions } from "@/lib/api/search";
import { SearchSuggestionItem } from "@/types/api";
import {
  resolveSuggestionHref,
  getSuggestionTypeLabel,
} from "@/lib/utils/sacred";
import { NotificationBell } from "@/components/notification/NotificationBell";
import { CartHeaderBadge } from "@/components/cart/CartHeaderBadge";

function HeaderContent() {
  const pathname = usePathname();
  const router = useRouter();
  const { user, isAuthenticated, isLoading: isAuthLoading, logout } = useAuth();
  const [searchQuery, setSearchQuery] = useState("");
  const [suggestions, setSuggestions] = useState<SearchSuggestionItem[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [isDropdownOpen, setIsDropdownOpen] = useState(false);
  const [userMenuOpen, setUserMenuOpen] = useState(false);
  const [activeIndex, setActiveIndex] = useState<number>(-1);
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const [mobileSearchOpen, setMobileSearchOpen] = useState(false);
  const [servicesOpen, setServicesOpen] = useState(false);
  const [mobileServicesOpen, setMobileServicesOpen] = useState(true);
  const searchContainerRef = useRef<HTMLDivElement>(null);
  const userMenuRef = useRef<HTMLDivElement>(null);
  const servicesDropdownRef = useRef<HTMLDivElement>(null);
  const servicesTimeoutRef = useRef<NodeJS.Timeout | null>(null);
  const abortControllerRef = useRef<AbortController | null>(null);

  const handleServicesMouseEnter = () => {
    if (servicesTimeoutRef.current) {
      clearTimeout(servicesTimeoutRef.current);
      servicesTimeoutRef.current = null;
    }
    setServicesOpen(true);
  };

  const handleServicesMouseLeave = () => {
    servicesTimeoutRef.current = setTimeout(() => {
      setServicesOpen(false);
    }, 150);
  };

  const handleSearchChange = (value: string) => {
    setSearchQuery(value);
    setActiveIndex(-1);
    if (value.trim().length < 2) {
      setSuggestions([]);
      setIsDropdownOpen(false);
      setIsLoading(false);
    }
  };

  const handleSubmitSearch = (customQuery?: string) => {
    const q = (customQuery ?? searchQuery).trim();
    if (!q) return;
    setIsDropdownOpen(false);
    setMobileSearchOpen(false);
    router.push(`/tim-kiem?q=${encodeURIComponent(q)}`);
  };

  const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
    if (!isDropdownOpen || suggestions.length === 0) {
      if (e.key === "Enter") {
        e.preventDefault();
        handleSubmitSearch();
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
          setMobileSearchOpen(false);
          router.push(resolveSuggestionHref(item));
        } else {
          handleSubmitSearch();
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

  useEffect(() => {
    const trimmed = searchQuery.trim();
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
        const results = await getSearchSuggestions(trimmed, controller.signal);
        if (!controller.signal.aborted) {
          setSuggestions(results);
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
        console.error("Lỗi tìm kiếm gợi ý:", err);
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
  }, [searchQuery]);

  // Click outside to close suggestion dropdown and user menu
  useEffect(() => {
    function handleClickOutside(event: MouseEvent) {
      if (
        searchContainerRef.current &&
        !searchContainerRef.current.contains(event.target as Node)
      ) {
        setIsDropdownOpen(false);
      }
      if (
        userMenuRef.current &&
        !userMenuRef.current.contains(event.target as Node)
      ) {
        setUserMenuOpen(false);
      }
      if (
        servicesDropdownRef.current &&
        !servicesDropdownRef.current.contains(event.target as Node)
      ) {
        setServicesOpen(false);
      }
    }
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);


  // Clean up hover timeout
  useEffect(() => {
    return () => {
      if (servicesTimeoutRef.current) {
        clearTimeout(servicesTimeoutRef.current);
      }
    };
  }, []);

  const isLinkActive = (href: string) => {
    if (href === "/") return pathname === "/";
    return pathname === href || pathname.startsWith(`${href}/`);
  };

  const primaryNavLinks = [
    { label: "Thư Viện Kinh", href: "/kinh" },
    { label: "Phật & Bồ Tát", href: "/phat-bo-tat" },
  ];

  const mergedServices = [
    {
      label: "Cầu Nguyện",
      href: "/cau-nguyen",
      description: "Phòng lời nguyện & hồi hướng cộng đồng",
      icon: "volunteer_activism",
    },
    {
      label: "Chùa Việt Nam",
      href: "/tim-chua",
      description: "Tra cứu tự viện, tịnh xá & đạo tràng",
      icon: "temple_buddhist",
    },
    {
      label: "Lịch Vạn Niên",
      href: "/lich-van-nien",
      description: "Lịch âm dương, ngày vía & thập trai",
      icon: "calendar_month",
    },
    {
      label: "Video Bài Giảng",
      href: "/video-phat-phap",
      description: "Pháp thoại, bài giảng & video Phật giáo",
      icon: "play_circle",
    },
    {
      label: "Bài Viết",
      href: "/bai-viet",
      description: "Tin tức Phật sự, giáo lý & pháp thoại",
      icon: "article",
    },
  ];

  const isServicesActive = mergedServices.some((item) => isLinkActive(item.href));

  return (
    <header className="fixed top-0 left-0 right-0 z-50 bg-surface-container-lowest/80 backdrop-blur-md border-b border-outline-variant/40 shadow-[0_1px_8px_rgba(140,109,45,0.04)] transition-all">
      <div className="h-16 max-w-[1360px] mx-auto px-4 sm:px-6 flex items-center justify-between gap-4">
        {/* Brand Logo */}
        <Link href="/" className="flex items-center gap-2 shrink-0 group">
          <div className="flex flex-col">
            <span className="font-headline-md text-[18px] sm:text-[20px] font-bold text-primary tracking-tight leading-none group-hover:opacity-90 transition-opacity">
              Om Mani
            </span>
            <span className="font-label-md text-[10px] text-outline tracking-wider mt-0.5 uppercase">
              Tâm An Sự Lành
            </span>
          </div>
        </Link>

        {/* Desktop Nav */}
        <nav className="hidden lg:flex items-center gap-5 xl:gap-8 font-label-lg text-label-lg">
          {primaryNavLinks.map((link) => {
            const active = isLinkActive(link.href);
            return (
              <Link
                key={link.href}
                href={link.href}
                aria-current={active ? "page" : undefined}
                className={
                  active
                    ? "text-primary font-semibold relative py-1 after:absolute after:bottom-0 after:left-0 after:right-0 after:h-0.5 after:bg-primary after:rounded-full"
                    : "text-on-surface-variant hover:text-primary transition-colors py-1"
                }
              >
                {link.label}
              </Link>
            );
          })}

          {/* Merged Group: Phật Sự & Tiện Ích */}
          <div
            className="relative"
            ref={servicesDropdownRef}
            onMouseEnter={handleServicesMouseEnter}
            onMouseLeave={handleServicesMouseLeave}
          >
            <button
              type="button"
              onClick={() => setServicesOpen((prev) => !prev)}
              onKeyDown={(e) => {
                if (e.key === "Escape") {
                  setServicesOpen(false);
                } else if (e.key === "ArrowDown" && !servicesOpen) {
                  e.preventDefault();
                  setServicesOpen(true);
                }
              }}
              aria-expanded={servicesOpen}
              aria-haspopup="true"
              className={`flex items-center gap-1 font-label-lg text-label-lg transition-colors py-1 cursor-pointer ${
                isServicesActive
                  ? "text-primary font-semibold relative after:absolute after:bottom-0 after:left-0 after:right-0 after:h-0.5 after:bg-primary after:rounded-full"
                  : "text-on-surface-variant hover:text-primary"
              }`}
            >
              <span>Phật Sự &amp; Tiện Ích</span>
              <span
                className={`material-symbols-outlined text-[18px] transition-transform duration-200 ${
                  servicesOpen ? "rotate-180 text-primary" : "text-outline"
                }`}
              >
                keyboard_arrow_down
              </span>
            </button>

            {/* Dropdown Menu */}
            {servicesOpen && (
              <div
                role="menu"
                aria-label="Menu Phật Sự & Tiện Ích"
                className="absolute top-full left-1/2 -translate-x-1/2 mt-1 w-72 sm:w-80 rounded-2xl bg-surface-container-lowest border border-outline-variant/60 shadow-xl shadow-black/5 p-2 z-50 animate-in fade-in zoom-in-95 duration-150"
              >
                {mergedServices.map((item) => {
                  const active = isLinkActive(item.href);
                  return (
                    <Link
                      key={item.href}
                      href={item.href}
                      role="menuitem"
                      onClick={() => setServicesOpen(false)}
                      className={`flex items-center gap-3 p-2.5 rounded-xl transition-all group ${
                        active
                          ? "bg-primary/10 text-primary font-medium"
                          : "hover:bg-surface-container-low text-on-surface hover:text-primary"
                      }`}
                    >
                      <div
                        className={`w-9 h-9 rounded-xl flex items-center justify-center shrink-0 transition-colors ${
                          active
                            ? "bg-primary text-on-primary shadow-xs"
                            : "bg-surface-container-low text-primary group-hover:bg-primary group-hover:text-on-primary shadow-2xs"
                        }`}
                      >
                        <span className="material-symbols-outlined text-[20px]">
                          {item.icon}
                        </span>
                      </div>
                      <div className="flex flex-col min-w-0">
                        <span className="font-label-md text-sm font-semibold truncate group-hover:text-primary transition-colors">
                          {item.label}
                        </span>
                        <span className="font-body-sm text-[11px] text-outline truncate group-hover:text-on-surface-variant">
                          {item.description}
                        </span>
                      </div>
                    </Link>
                  );
                })}
              </div>
            )}
          </div>

          {/* Cửa Hàng */}
          <Link
            href="/cua-hang"
            aria-current={isLinkActive("/cua-hang") ? "page" : undefined}
            className={
              isLinkActive("/cua-hang")
                ? "text-primary font-semibold relative py-1 after:absolute after:bottom-0 after:left-0 after:right-0 after:h-0.5 after:bg-primary after:rounded-full"
                : "text-on-surface-variant hover:text-primary transition-colors py-1"
            }
          >
            Cửa Hàng
          </Link>
        </nav>

        {/* Search & Actions */}
        <div className="flex items-center gap-2 sm:gap-3 shrink-0">
          {/* Desktop Search input with dropdown */}
          <div className="relative hidden lg:block w-48 xl:w-56" ref={searchContainerRef}>
            <span className="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[16px] pointer-events-none">
              search
            </span>
            <input
              type="text"
              role="combobox"
              aria-autocomplete="list"
              aria-expanded={isDropdownOpen && suggestions.length > 0}
              aria-controls="header-search-suggestions"
              aria-activedescendant={
                activeIndex >= 0 ? `header-suggestion-${activeIndex}` : undefined
              }
              suppressHydrationWarning
              value={searchQuery}
              onChange={(e) => handleSearchChange(e.target.value)}
              onKeyDown={handleKeyDown}
              onFocus={() => {
                if (suggestions.length > 0) setIsDropdownOpen(true);
              }}
              placeholder="Tìm kinh, chú, Phật..."
              className="w-full pl-8 pr-7 py-1.5 bg-surface-container-low/70 hover:bg-surface-container-low focus:bg-surface-container-lowest border border-outline-variant/50 rounded-full text-on-surface placeholder:text-outline font-body-sm text-[12px] focus:outline-none focus:ring-1 focus:ring-primary/40 transition-all"
            />
            {isLoading && (
              <span className="absolute right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 border-2 border-primary border-t-transparent rounded-full animate-spin"></span>
            )}

            {/* Suggestions Dropdown */}
            {isDropdownOpen && suggestions.length > 0 && (
              <div
                id="header-search-suggestions"
                role="listbox"
                className="absolute top-full left-0 right-0 mt-2 bg-surface-container-lowest border border-outline-variant/60 rounded-xl shadow-lg p-2 max-h-80 overflow-y-auto z-50 min-w-[280px]"
              >
                <div className="font-label-caps text-[10px] text-outline px-2 py-1 uppercase">
                  Gợi ý tìm kiếm
                </div>
                {suggestions.map((item, idx) => (
                  <Link
                    key={idx}
                    id={`header-suggestion-${idx}`}
                    role="option"
                    aria-selected={activeIndex === idx}
                    href={resolveSuggestionHref(item)}
                    onClick={() => {
                      setIsDropdownOpen(false);
                      setSearchQuery("");
                    }}
                    className={`flex items-center justify-between p-2 rounded-lg transition-colors group ${
                      activeIndex === idx
                        ? "bg-surface-container text-primary"
                        : "hover:bg-surface-container-low"
                    }`}
                  >
                    <div className="min-w-0 pr-2">
                      <div className="font-label-md text-label-md font-medium text-on-surface group-hover:text-primary truncate">
                        {item.title}
                      </div>
                      {item.subtitle && (
                        <div className="font-body-sm text-[11px] text-outline truncate">
                          {item.subtitle}
                        </div>
                      )}
                    </div>
                    <span className="px-2 py-0.5 rounded-full text-[10px] font-label-caps bg-surface-container text-primary shrink-0 uppercase">
                      {getSuggestionTypeLabel(item.type)}
                    </span>
                  </Link>
                ))}

                {/* See All Results CTA */}
                <button
                  type="button"
                  suppressHydrationWarning
                  onClick={() => handleSubmitSearch()}
                  className="w-full text-left p-2.5 mt-1 rounded-lg border-t border-outline-variant/30 text-xs font-label-md text-primary hover:bg-surface-container-low transition-colors flex items-center justify-between"
                >
                  <span className="flex items-center gap-1.5 truncate">
                    <span className="material-symbols-outlined text-[16px]">search</span>
                    <span>Xem tất cả kết quả cho <strong>“{searchQuery.trim()}”</strong></span>
                  </span>
                  <span className="material-symbols-outlined text-[16px] shrink-0">arrow_forward</span>
                </button>
              </div>
            )}
          </div>

          {/* Mobile search toggle button */}
          <button
            type="button"
            suppressHydrationWarning
            onClick={() => setMobileSearchOpen(!mobileSearchOpen)}
            className="lg:hidden w-9 h-9 min-w-[36px] min-h-[36px] rounded-full border border-outline-variant/60 flex items-center justify-center text-on-surface-variant hover:text-primary transition-colors focus:outline-hidden focus:ring-2 focus:ring-primary/40"
            title="Tìm kiếm"
            aria-label="Tìm kiếm"
          >
            <span className="material-symbols-outlined text-[18px]">search</span>
          </button>

          {/* Shopping Cart button */}
          <CartHeaderBadge />

          {/* User profile / Auth button */}
          <div className="flex items-center pl-1 sm:pl-2 border-l border-outline-variant/40">
            {isAuthLoading ? (
              <div className="w-8 h-8 rounded-full bg-surface-container-low animate-pulse" />
            ) : isAuthenticated && user ? (
              <div className="flex items-center gap-1 sm:gap-2">
                <NotificationBell />
                <div className="relative" ref={userMenuRef}>
                <button
                  type="button"
                  suppressHydrationWarning
                  onClick={() => setUserMenuOpen(!userMenuOpen)}
                  className="flex items-center gap-2 focus:outline-none group p-1 rounded-full hover:bg-surface-container-low transition-colors"
                  aria-expanded={userMenuOpen}
                  aria-label="Menu tài khoản"
                >
                  <div className="w-7 h-7 rounded-full bg-gradient-to-br from-primary to-primary-container text-on-primary flex items-center justify-center font-semibold text-xs ring-1 ring-primary/30 group-hover:ring-primary transition-all shadow-2xs overflow-hidden">
                    {user.profile?.avatar_url ? (
                      <img
                        src={user.profile.avatar_url}
                        alt={user.name}
                        className="w-full h-full object-cover"
                      />
                    ) : (
                      user.name.charAt(0).toUpperCase()
                    )}
                  </div>
                  <span className="font-label-md text-label-md font-medium text-on-surface hidden sm:inline group-hover:text-primary transition-colors max-w-[110px] truncate">
                    {user.name}
                  </span>
                  <span className="material-symbols-outlined text-[16px] text-outline group-hover:text-primary transition-colors hidden sm:inline">
                    arrow_drop_down
                  </span>
                </button>

                {userMenuOpen && (
                  <div className="absolute right-0 mt-2 w-56 rounded-2xl bg-surface-container-lowest border border-outline-variant/50 shadow-lg p-2 z-50 animate-in fade-in zoom-in-95 duration-100">
                    <div className="px-3 py-2 border-b border-outline-variant/30 mb-1">
                      <p className="font-label-md font-semibold text-on-surface truncate">
                        {user.name}
                      </p>
                      <p className="font-body-sm text-[11px] text-outline truncate">
                        {user.email}
                      </p>
                    </div>

                    <Link
                      href="/tai-khoan"
                      onClick={() => setUserMenuOpen(false)}
                      className="flex items-center gap-2.5 px-3 py-2 rounded-xl text-on-surface hover:text-primary hover:bg-surface-container-low font-label-md text-xs transition-colors"
                    >
                      <span className="material-symbols-outlined text-[18px] text-outline">
                        account_circle
                      </span>
                      <span>Tài khoản tu học</span>
                    </Link>
                    <Link
                      href="/gio-hang"
                      onClick={() => setUserMenuOpen(false)}
                      className="flex items-center gap-2.5 px-3 py-2 rounded-xl text-on-surface hover:text-primary hover:bg-surface-container-low font-label-md text-xs transition-colors"
                    >
                      <span className="material-symbols-outlined text-[18px] text-outline">
                        shopping_bag
                      </span>
                      <span>Giỏ hàng vật phẩm</span>
                    </Link>
                    {user.role === "admin" && (
                      <a
                        href="http://localhost:8000/admin"
                        target="_blank"
                        rel="noreferrer"
                        onClick={() => setUserMenuOpen(false)}
                        className="flex items-center gap-2.5 px-3 py-2 rounded-xl text-on-surface hover:text-primary hover:bg-surface-container-low font-label-md text-xs transition-colors"
                      >
                        <span className="material-symbols-outlined text-[18px] text-outline">
                          admin_panel_settings
                        </span>
                        <span>Quản trị hệ thống</span>
                      </a>
                    )}

                    <button
                      type="button"
                      suppressHydrationWarning
                      onClick={async () => {
                        setUserMenuOpen(false);
                        await logout();
                      }}
                      className="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-error hover:bg-error/10 font-label-md text-xs transition-colors text-left"
                    >
                      <span className="material-symbols-outlined text-[18px]">
                        logout
                      </span>
                      <span>Đăng xuất</span>
                    </button>
                  </div>
                )}
              </div>
            </div>
            ) : (
              <Link
                href="/dang-nhap"
                className="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-surface-container-low hover:bg-surface-container border border-outline-variant/60 text-primary font-label-md text-xs font-semibold hover:border-primary/40 transition-all shadow-2xs"
              >
                <span className="material-symbols-outlined text-[16px]">
                  login
                </span>
                <span>Đăng nhập</span>
              </Link>
            )}
          </div>

          {/* Mobile hamburger menu toggle */}
          <button
            type="button"
            suppressHydrationWarning
            onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
            className="lg:hidden w-10 h-10 min-w-[40px] min-h-[40px] rounded-lg flex items-center justify-center text-on-surface-variant hover:text-primary transition-colors focus:outline-hidden focus:ring-2 focus:ring-primary/40"
            aria-label="Toggle navigation menu"
          >
            <span className="material-symbols-outlined text-[24px]">
              {mobileMenuOpen ? "close" : "menu"}
            </span>
          </button>
        </div>
      </div>

      {/* Mobile search bar expandable */}
      {mobileSearchOpen && (
        <div className="lg:hidden border-t border-outline-variant/40 bg-surface-container-lowest p-3 px-4 shadow-inner">
          <div className="relative">
            <span className="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[18px]">
              search
            </span>
            <input
              type="text"
              suppressHydrationWarning
              value={searchQuery}
              onChange={(e) => handleSearchChange(e.target.value)}
              onKeyDown={handleKeyDown}
              placeholder="Tìm kinh điển, chư Phật, chùa..."
              className="w-full pl-9 pr-4 py-2 bg-surface-container-low border border-outline-variant/60 rounded-full text-on-surface placeholder:text-outline font-body-sm text-[13px] focus:outline-none focus:ring-1 focus:ring-primary"
              autoFocus
            />
          </div>
          {isDropdownOpen && suggestions.length > 0 && (
            <div className="mt-2 bg-surface-container-lowest border border-outline-variant/60 rounded-xl p-2 max-h-64 overflow-y-auto">
              {suggestions.map((item, idx) => (
                <Link
                  key={idx}
                  href={resolveSuggestionHref(item)}
                  onClick={() => {
                    setIsDropdownOpen(false);
                    setMobileSearchOpen(false);
                    setSearchQuery("");
                  }}
                  className="flex items-center justify-between p-2 rounded-lg hover:bg-surface-container-low"
                >
                  <span className="font-label-md text-on-surface truncate">
                    {item.title}
                  </span>
                  <span className="px-2 py-0.5 rounded-full text-[10px] bg-surface-container text-primary">
                    {getSuggestionTypeLabel(item.type)}
                  </span>
                </Link>
              ))}

              <button
                type="button"
                suppressHydrationWarning
                onClick={() => handleSubmitSearch()}
                className="w-full text-left p-2.5 mt-1 rounded-lg border-t border-outline-variant/30 text-xs font-label-md text-primary hover:bg-surface-container-low transition-colors flex items-center justify-between"
              >
                <span className="flex items-center gap-1.5 truncate">
                  <span className="material-symbols-outlined text-[16px]">search</span>
                  <span>Xem tất cả kết quả cho <strong>“{searchQuery.trim()}”</strong></span>
                </span>
                <span className="material-symbols-outlined text-[16px] shrink-0">arrow_forward</span>
              </button>
            </div>
          )}
        </div>
      )}

      {/* Mobile drawer navigation */}
      {mobileMenuOpen && (
        <div className="lg:hidden border-t border-outline-variant/40 bg-surface-container-lowest shadow-lg px-6 py-4 flex flex-col gap-2 max-h-[calc(100vh-4rem)] overflow-y-auto">
          {primaryNavLinks.map((link) => {
            const active = isLinkActive(link.href);
            return (
              <Link
                key={link.href}
                href={link.href}
                onClick={() => setMobileMenuOpen(false)}
                className={`font-label-lg text-label-lg py-2.5 transition-colors flex items-center justify-between border-b border-outline-variant/20 ${
                  active
                    ? "text-primary font-bold"
                    : "text-on-surface hover:text-primary"
                }`}
              >
                <span>{link.label}</span>
                <span className="material-symbols-outlined text-[18px] text-outline">
                  chevron_right
                </span>
              </Link>
            );
          })}

          {/* Merged Group: Phật Sự & Tiện Ích (Accordion) */}
          <div className="border-b border-outline-variant/20 py-1">
            <button
              type="button"
              onClick={() => setMobileServicesOpen((prev) => !prev)}
              className="w-full flex items-center justify-between py-2.5 font-label-lg text-label-lg text-on-surface hover:text-primary transition-colors text-left"
            >
              <span className={isServicesActive ? "text-primary font-bold" : ""}>
                Phật Sự &amp; Tiện Ích
              </span>
              <span
                className={`material-symbols-outlined text-[20px] text-outline transition-transform duration-200 ${
                  mobileServicesOpen ? "rotate-180 text-primary" : ""
                }`}
              >
                keyboard_arrow_down
              </span>
            </button>

            {mobileServicesOpen && (
              <div className="pl-2 pr-1 py-1.5 space-y-1 bg-surface-container-low/50 rounded-xl mb-2">
                {mergedServices.map((item) => {
                  const active = isLinkActive(item.href);
                  return (
                    <Link
                      key={item.href}
                      href={item.href}
                      onClick={() => setMobileMenuOpen(false)}
                      className={`flex items-center gap-3 p-2 rounded-lg transition-colors ${
                        active
                          ? "bg-primary/10 text-primary font-semibold"
                          : "text-on-surface-variant hover:text-primary hover:bg-surface-container-low"
                      }`}
                    >
                      <div
                        className={`w-7 h-7 rounded-lg flex items-center justify-center shrink-0 ${
                          active
                            ? "bg-primary text-on-primary"
                            : "bg-surface-container text-primary"
                        }`}
                      >
                        <span className="material-symbols-outlined text-[16px]">
                          {item.icon}
                        </span>
                      </div>
                      <div className="flex flex-col min-w-0">
                        <span className="text-sm font-label-md truncate">{item.label}</span>
                        <span className="text-[11px] text-outline truncate">{item.description}</span>
                      </div>
                    </Link>
                  );
                })}
              </div>
            )}
          </div>

          {/* Cửa Hàng */}
          <Link
            href="/cua-hang"
            onClick={() => setMobileMenuOpen(false)}
            className={`font-label-lg text-label-lg py-2.5 transition-colors flex items-center justify-between border-b border-outline-variant/20 ${
              isLinkActive("/cua-hang")
                ? "text-primary font-bold"
                : "text-on-surface hover:text-primary"
            }`}
          >
            <span>Cửa Hàng</span>
            <span className="material-symbols-outlined text-[18px] text-outline">
              chevron_right
            </span>
          </Link>

          {/* Mobile Auth actions */}
          <div className="pt-3 mt-1 border-t border-outline-variant/30">
            {isAuthLoading ? (
              <div className="h-10 rounded-xl bg-surface-container-low animate-pulse" />
            ) : isAuthenticated && user ? (
              <div className="flex flex-col gap-2">
                <div className="flex items-center gap-2.5 px-2 py-1.5 rounded-xl bg-surface-container-low">
                  <div className="w-8 h-8 rounded-full bg-primary text-on-primary flex items-center justify-center font-bold text-xs shrink-0 overflow-hidden">
                    {user.profile?.avatar_url ? (
                      <img
                        src={user.profile.avatar_url}
                        alt={user.name}
                        className="w-full h-full object-cover"
                      />
                    ) : (
                      user.name.charAt(0).toUpperCase()
                    )}
                  </div>
                  <div className="flex flex-col truncate">
                    <span className="font-label-md text-sm font-semibold text-on-surface truncate">
                      {user.name}
                    </span>
                    <span className="font-body-sm text-xs text-outline truncate">
                      {user.email}
                    </span>
                  </div>
                </div>

                <Link
                  href="/tai-khoan"
                  onClick={() => setMobileMenuOpen(false)}
                  className="flex items-center gap-2.5 py-2 px-3 rounded-xl hover:bg-surface-container-low font-label-md text-sm text-on-surface transition-colors"
                >
                  <span className="material-symbols-outlined text-[18px] text-outline">
                    account_circle
                  </span>
                  <span>Tài khoản tu học</span>
                </Link>
                <Link
                  href="/gio-hang"
                  onClick={() => setMobileMenuOpen(false)}
                  className="flex items-center gap-2.5 py-2 px-3 rounded-xl hover:bg-surface-container-low font-label-md text-sm text-on-surface transition-colors"
                >
                  <span className="material-symbols-outlined text-[18px] text-outline">
                    shopping_bag
                  </span>
                  <span>Giỏ hàng vật phẩm</span>
                </Link>
                {user.role === "admin" && (
                  <a
                    href="http://localhost:8000/admin"
                    target="_blank"
                    rel="noreferrer"
                    onClick={() => setMobileMenuOpen(false)}
                    className="flex items-center gap-2.5 py-2 px-3 rounded-xl hover:bg-surface-container-low font-label-md text-sm text-on-surface transition-colors"
                  >
                    <span className="material-symbols-outlined text-[18px] text-outline">
                      admin_panel_settings
                    </span>
                    <span>Quản trị hệ thống</span>
                  </a>
                )}

                <button
                  type="button"
                  suppressHydrationWarning
                  onClick={async () => {
                    setMobileMenuOpen(false);
                    await logout();
                  }}
                  className="flex items-center gap-2.5 py-2 px-3 rounded-xl text-error hover:bg-error/10 font-label-md text-sm text-left transition-colors"
                >
                  <span className="material-symbols-outlined text-[18px]">
                    logout
                  </span>
                  <span>Đăng xuất</span>
                </button>
              </div>
            ) : (
              <div className="flex flex-col gap-2">
                <Link
                  href="/dang-nhap"
                  onClick={() => setMobileMenuOpen(false)}
                  className="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl bg-primary text-on-primary font-label-md text-sm font-semibold shadow-xs hover:bg-primary-hover transition-colors"
                >
                  <span className="material-symbols-outlined text-[18px]">
                    login
                  </span>
                  <span>Đăng nhập</span>
                </Link>
                <Link
                  href="/dang-ky"
                  onClick={() => setMobileMenuOpen(false)}
                  className="w-full flex items-center justify-center gap-2 py-2 px-4 rounded-xl border border-outline-variant/60 hover:bg-surface-container-low font-label-md text-sm font-medium text-on-surface transition-colors"
                >
                  <span>Tạo tài khoản mới</span>
                </Link>
              </div>
            )}
          </div>
        </div>
      )}
    </header>
  );
}

export function Header() {
  return (
    <React.Suspense
      fallback={
        <header className="fixed top-0 left-0 right-0 z-50 bg-surface-container-lowest/80 backdrop-blur-md border-b border-outline-variant/40 shadow-[0_1px_8px_rgba(140,109,45,0.04)] h-16">
          <div className="h-16 max-w-[1360px] mx-auto px-4 sm:px-6 flex items-center justify-between gap-4">
            <Link href="/" className="flex items-center gap-2 shrink-0">
              <div className="flex flex-col">
                <span className="font-headline-md text-[18px] sm:text-[20px] font-bold text-primary tracking-tight leading-none">
                  Om Mani
                </span>
                <span className="font-label-md text-[10px] text-outline tracking-wider mt-0.5 uppercase">
                  Tâm An Sự Lành
                </span>
              </div>
            </Link>
          </div>
        </header>
      }
    >
      <HeaderContent />
    </React.Suspense>
  );
}
