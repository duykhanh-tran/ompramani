import React from "react";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";

export default function BaiVietLoading() {
  return (
    <div className="min-h-screen flex flex-col bg-surface font-body-md text-on-surface antialiased">
      <Header />

      <main className="w-full bg-surface flex-1 pt-16">
        <div className="max-w-[1360px] mx-auto px-4 sm:px-6 py-8 space-y-8 animate-pulse">
          {/* Breadcrumb skeleton */}
          <div className="h-4 w-40 bg-surface-container rounded-md" />

          {/* Title skeleton */}
          <div className="space-y-3">
            <div className="h-4 w-32 bg-surface-container rounded-md" />
            <div className="h-10 w-80 max-w-full bg-surface-container rounded-lg" />
            <div className="h-4 w-96 max-w-full bg-surface-container rounded-md" />
          </div>

          {/* Filter & Search Toolbar skeleton */}
          <div className="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4 py-4 border-y border-outline-variant/40">
            <div className="flex items-center gap-2 overflow-hidden">
              <div className="h-9 w-28 bg-surface-container rounded-full shrink-0" />
              <div className="h-9 w-24 bg-surface-container rounded-full shrink-0" />
              <div className="h-9 w-32 bg-surface-container rounded-full shrink-0" />
              <div className="h-9 w-24 bg-surface-container rounded-full shrink-0" />
            </div>
            <div className="h-10 w-full lg:w-72 bg-surface-container rounded-full" />
          </div>

          {/* Grid skeleton */}
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            {[1, 2, 3, 4, 5, 6].map((i) => (
              <div
                key={i}
                className="rounded-2xl overflow-hidden bg-surface-container-lowest border border-outline-variant/30 flex flex-col h-[380px]"
              >
                <div className="w-full aspect-[16/10] bg-surface-container" />
                <div className="p-5 flex-1 flex flex-col justify-between space-y-4">
                  <div className="space-y-2">
                    <div className="h-5 w-3/4 bg-surface-container rounded-md" />
                    <div className="h-4 w-full bg-surface-container rounded-md" />
                    <div className="h-4 w-2/3 bg-surface-container rounded-md" />
                  </div>
                  <div className="pt-4 border-t border-outline-variant/20 flex justify-between">
                    <div className="h-3 w-20 bg-surface-container rounded-md" />
                    <div className="h-3 w-24 bg-surface-container rounded-md" />
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </main>

      <Footer />
    </div>
  );
}
