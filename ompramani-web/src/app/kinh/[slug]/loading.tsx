import React from "react";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";

export default function SutraDetailLoading() {
  return (
    <div className="min-h-screen flex flex-col bg-surface font-body-md text-on-surface antialiased">
      <Header />

      <main className="w-full bg-surface flex-1 pt-16">
        <div className="max-w-[1360px] mx-auto px-4 sm:px-6 py-8 space-y-8 animate-pulse">
          {/* Breadcrumb Skeleton */}
          <div className="h-4 w-48 bg-surface-container rounded-full" />

          {/* Header Card Skeleton */}
          <div className="bg-surface-container-lowest rounded-3xl p-8 border border-outline-variant/30 flex flex-col md:flex-row gap-6">
            <div className="w-44 h-48 bg-surface-container rounded-2xl shrink-0" />
            <div className="flex-1 space-y-4">
              <div className="h-6 w-32 bg-surface-container rounded-full" />
              <div className="h-10 w-3/4 bg-surface-container rounded-xl" />
              <div className="h-4 w-1/2 bg-surface-container rounded" />
              <div className="h-16 w-full bg-surface-container rounded-xl" />
            </div>
          </div>

          {/* Reading Layout Skeleton */}
          <div className="flex flex-col lg:flex-row gap-8 items-start">
            {/* TOC sidebar */}
            <div className="hidden lg:block w-72 h-96 bg-surface-container-lowest rounded-3xl p-5 border border-outline-variant/30" />

            {/* Reading area */}
            <div className="flex-1 w-full bg-surface-container-lowest rounded-3xl p-10 border border-outline-variant/30 space-y-6">
              <div className="h-8 w-1/2 bg-surface-container rounded-lg" />
              <div className="space-y-3 pt-4">
                <div className="h-4 w-full bg-surface-container rounded" />
                <div className="h-4 w-full bg-surface-container rounded" />
                <div className="h-4 w-4/5 bg-surface-container rounded" />
              </div>
            </div>
          </div>
        </div>
      </main>

      <Footer />
    </div>
  );
}
