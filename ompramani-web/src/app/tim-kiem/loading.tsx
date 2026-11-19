import React from "react";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";

export default function TimKiemLoading() {
  return (
    <div className="min-h-screen flex flex-col bg-surface font-body-md text-on-surface antialiased">
      <Header />

      <main className="w-full bg-surface flex-1 pt-16">
        <div className="max-w-[1120px] mx-auto px-4 sm:px-6 py-8 space-y-8 animate-pulse">
          {/* Breadcrumb skeleton */}
          <div className="h-4 w-32 bg-surface-container rounded-md" />

          {/* Heading skeleton */}
          <div className="space-y-3">
            <div className="h-4 w-28 bg-surface-container rounded-md" />
            <div className="h-10 w-72 bg-surface-container rounded-lg" />
            <div className="h-4 w-96 max-w-full bg-surface-container rounded-md" />
          </div>

          {/* Main search input skeleton */}
          <div className="h-14 w-full max-w-3xl mx-auto bg-surface-container rounded-full" />

          {/* Filters skeleton */}
          <div className="py-4 border-y border-outline-variant/30 space-y-3">
            <div className="flex items-center gap-2 overflow-hidden">
              <div className="h-9 w-20 bg-surface-container rounded-full" />
              <div className="h-9 w-24 bg-surface-container rounded-full" />
              <div className="h-9 w-24 bg-surface-container rounded-full" />
              <div className="h-9 w-28 bg-surface-container rounded-full" />
              <div className="h-9 w-24 bg-surface-container rounded-full" />
            </div>
          </div>

          {/* Result cards skeleton */}
          <div className="space-y-4">
            {[1, 2, 3, 4].map((i) => (
              <div
                key={i}
                className="p-5 rounded-2xl bg-surface-container-lowest border border-outline-variant/30 flex flex-col sm:flex-row items-start gap-4"
              >
                <div className="w-full sm:w-28 sm:h-28 aspect-video sm:aspect-square bg-surface-container rounded-xl shrink-0" />
                <div className="flex-1 w-full space-y-3">
                  <div className="flex gap-2">
                    <div className="h-5 w-20 bg-surface-container rounded-full" />
                    <div className="h-5 w-24 bg-surface-container rounded-full" />
                  </div>
                  <div className="h-6 w-3/4 bg-surface-container rounded-md" />
                  <div className="h-4 w-full bg-surface-container rounded-md" />
                  <div className="h-4 w-2/3 bg-surface-container rounded-md" />
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
