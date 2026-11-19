import React from "react";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";

export default function KinhLoading() {
  return (
    <div className="min-h-screen flex flex-col bg-surface font-body-md text-on-surface antialiased">
      <Header />

      <main className="w-full bg-surface flex-1 pt-16">
        <div className="max-w-[1360px] mx-auto px-4 sm:px-6 py-8 space-y-8 animate-pulse">
          {/* Header Skeleton */}
          <div className="space-y-3">
            <div className="h-4 w-32 bg-surface-container rounded-full" />
            <div className="h-10 w-64 bg-surface-container rounded-xl" />
            <div className="h-4 w-96 bg-surface-container rounded-full" />
          </div>

          {/* Filter & Search Skeleton */}
          <div className="flex flex-col md:flex-row items-center justify-between gap-4 py-2 border-y border-outline-variant/30">
            <div className="flex gap-2">
              <div className="h-9 w-20 bg-surface-container rounded-full" />
              <div className="h-9 w-28 bg-surface-container rounded-full" />
              <div className="h-9 w-28 bg-surface-container rounded-full" />
            </div>
            <div className="h-10 w-full md:w-80 bg-surface-container rounded-full" />
          </div>

          {/* Grid Skeleton */}
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 pt-4">
            {[1, 2, 3, 4, 5, 6].map((i) => (
              <div
                key={i}
                className="bg-surface-container-lowest rounded-2xl overflow-hidden border border-outline-variant/30 flex flex-col h-80"
              >
                <div className="h-48 bg-surface-container" />
                <div className="p-5 space-y-3 flex-1 flex flex-col justify-between">
                  <div className="space-y-2">
                    <div className="h-5 w-3/4 bg-surface-container rounded" />
                    <div className="h-3 w-1/2 bg-surface-container rounded" />
                  </div>
                  <div className="h-4 w-full bg-surface-container rounded" />
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
