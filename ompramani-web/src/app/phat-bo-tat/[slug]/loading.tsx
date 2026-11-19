import React from "react";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";

export default function FigureDetailLoading() {
  return (
    <div className="min-h-screen flex flex-col bg-surface font-body-md text-on-surface antialiased">
      <Header />

      <main className="w-full bg-surface flex-1 pt-16">
        <div className="max-w-[1360px] mx-auto px-4 sm:px-6 py-8 space-y-8">
          <div className="h-4 w-56 bg-surface-container rounded animate-pulse" />

          <div className="bg-surface-container-lowest rounded-3xl p-6 sm:p-10 border border-outline-variant/20 flex flex-col md:flex-row gap-8">
            <div className="w-60 h-72 rounded-2xl bg-surface-container animate-pulse shrink-0 mx-auto md:mx-0" />
            <div className="flex-1 space-y-4">
              <div className="h-6 w-32 bg-surface-container rounded-full animate-pulse" />
              <div className="h-10 w-3/4 bg-surface-container rounded-xl animate-pulse" />
              <div className="h-5 w-48 bg-surface-container rounded animate-pulse" />
              <div className="h-20 w-full bg-surface-container rounded-xl animate-pulse" />
            </div>
          </div>

          <div className="bg-surface-container-lowest rounded-3xl p-6 sm:p-10 border border-outline-variant/20 space-y-4">
            <div className="h-8 w-56 bg-surface-container rounded animate-pulse" />
            <div className="h-32 w-full bg-surface-container rounded-xl animate-pulse" />
          </div>
        </div>
      </main>

      <Footer />
    </div>
  );
}
