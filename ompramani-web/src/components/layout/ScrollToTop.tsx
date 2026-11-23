"use client";

import React, { useEffect, useState } from "react";

export function ScrollToTop() {
  const [isVisible, setIsVisible] = useState(false);

  useEffect(() => {
    const toggleVisibility = () => {
      // Show button once user scrolls down past 280px
      if (window.scrollY > 280) {
        setIsVisible(true);
      } else {
        setIsVisible(false);
      }
    };

    // Check initial position on mount
    toggleVisibility();

    window.addEventListener("scroll", toggleVisibility, { passive: true });
    return () => window.removeEventListener("scroll", toggleVisibility);
  }, []);

  const scrollToTop = () => {
    window.scrollTo({
      top: 0,
      behavior: "smooth",
    });
  };

  return (
    <div
      className={`fixed bottom-6 right-6 sm:bottom-8 sm:right-8 z-40 transition-all duration-300 ease-out ${
        isVisible
          ? "opacity-100 translate-y-0 pointer-events-auto scale-100"
          : "opacity-0 translate-y-4 pointer-events-none scale-90"
      }`}
    >
      <button
        type="button"
        onClick={scrollToTop}
        tabIndex={isVisible ? 0 : -1}
        aria-label="Cuộn lên đầu trang"
        title="Cuộn lên đầu trang"
        className="group relative flex items-center justify-center w-11 h-11 sm:w-12 sm:h-12 rounded-full bg-gradient-to-tr from-primary to-primary-container text-on-primary shadow-lg shadow-primary/25 hover:shadow-xl hover:shadow-primary/35 hover:-translate-y-1 active:translate-y-0 active:scale-95 transition-all duration-200 border border-on-primary/20 focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus:outline-hidden cursor-pointer"
      >
        {/* Subtle glow effect */}
        <span className="absolute inset-0 rounded-full bg-white/15 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none" />

        {/* Upward Arrow Icon */}
        <svg
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          strokeWidth="2.5"
          strokeLinecap="round"
          strokeLinejoin="round"
          className="w-5 h-5 sm:w-5.5 sm:h-5.5 transition-transform duration-200 group-hover:-translate-y-0.5"
          aria-hidden="true"
        >
          <path d="M12 19V5" />
          <path d="M5 12l7-7 7 7" />
        </svg>

        {/* Tooltip on desktop */}
        <span className="hidden sm:block absolute right-full mr-3 px-2.5 py-1 text-xs font-medium text-white bg-on-surface/90 backdrop-blur-xs rounded-lg shadow-md whitespace-nowrap opacity-0 translate-x-1 pointer-events-none group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-200">
          Lên đầu trang
        </span>
      </button>
    </div>
  );
}
