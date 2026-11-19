import React from "react";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";

export default function ArticleDetailLoading() {
  return (
    <div className="min-h-screen flex flex-col bg-surface font-body-md text-on-surface antialiased">
      <Header />

      <main className="w-full bg-surface flex-1 pt-16">
        <div className="max-w-[1040px] mx-auto px-4 sm:px-6 py-8 sm:py-12 animate-pulse space-y-8">
          {/* Breadcrumbs skeleton */}
          <div className="h-4 w-48 bg-surface-container rounded-md" />

          {/* Header skeleton */}
          <div className="max-w-4xl mx-auto text-center space-y-4">
            <div className="h-6 w-28 bg-surface-container rounded-full mx-auto" />
            <div className="h-10 sm:h-12 w-3/4 bg-surface-container rounded-xl mx-auto" />
            <div className="h-5 w-1/2 bg-surface-container rounded-md mx-auto" />
            <div className="h-4 w-72 bg-surface-container rounded-md mx-auto" />
          </div>

          {/* Banner cover image skeleton */}
          <div className="max-w-4xl mx-auto">
            <div className="w-full aspect-[16/9] sm:aspect-[21/9] bg-surface-container rounded-2xl" />
          </div>

          {/* Content lines skeleton */}
          <div className="max-w-[780px] mx-auto space-y-4 pt-4">
            <div className="h-4 w-full bg-surface-container rounded-md" />
            <div className="h-4 w-11/12 bg-surface-container rounded-md" />
            <div className="h-4 w-full bg-surface-container rounded-md" />
            <div className="h-4 w-4/5 bg-surface-container rounded-md" />
            <div className="h-8 w-1/2 bg-surface-container rounded-lg my-6" />
            <div className="h-4 w-full bg-surface-container rounded-md" />
            <div className="h-4 w-10/12 bg-surface-container rounded-md" />
            <div className="h-4 w-full bg-surface-container rounded-md" />
          </div>
        </div>
      </main>

      <Footer />
    </div>
  );
}
