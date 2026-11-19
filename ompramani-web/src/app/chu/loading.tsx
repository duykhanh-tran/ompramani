import React from "react";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";

export default function ChuLoading() {
  return (
    <div className="min-h-screen flex flex-col bg-surface font-body-md text-on-surface antialiased">
      <Header />

      <main className="w-full bg-surface flex-1 pt-16">
        <div className="max-w-[1360px] mx-auto px-4 sm:px-6 py-8 space-y-8">
          <div className="h-4 w-48 bg-surface-container rounded animate-pulse" />

          <div className="space-y-3">
            <div className="h-4 w-36 bg-surface-container rounded animate-pulse" />
            <div className="h-10 w-80 bg-surface-container rounded-xl animate-pulse" />
            <div className="h-5 w-full max-w-xl bg-surface-container rounded animate-pulse" />
          </div>

          <div className="flex flex-col md:flex-row justify-between gap-4 py-4 border-y border-outline-variant/30">
            <div className="flex gap-2">
              <div className="h-9 w-24 bg-surface-container rounded-full animate-pulse" />
              <div className="h-9 w-24 bg-surface-container rounded-full animate-pulse" />
            </div>
            <div className="h-10 w-64 bg-surface-container rounded-full animate-pulse" />
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            {Array.from({ length: 6 }).map((_, idx) => (
              <div
                key={idx}
                className="bg-surface-container-lowest rounded-2xl overflow-hidden border border-outline-variant/20 space-y-4 pb-5"
              >
                <div className="h-48 bg-surface-container animate-pulse" />
                <div className="px-5 space-y-2.5">
                  <div className="h-6 w-3/4 bg-surface-container rounded animate-pulse" />
                  <div className="h-4 w-1/2 bg-surface-container rounded animate-pulse" />
                  <div className="h-12 w-full bg-surface-container rounded animate-pulse" />
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
