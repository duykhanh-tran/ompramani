import React, { Suspense } from "react";
import type { Metadata } from "next";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";
import ResetPasswordForm from "@/components/auth/ResetPasswordForm";

export const metadata: Metadata = {
  title: "Đặt Lại Mật Khẩu | OmPraMani",
  description: "Thiết lập mật khẩu mới cho tài khoản OmPraMani",
  robots: {
    index: false,
    follow: false,
  },
};

export default function DatLaiMatKhauPage() {
  return (
    <div className="min-h-screen flex flex-col bg-surface">
      <Header />
      <main id="main-content" className="flex-1 flex items-center justify-center px-4 py-24 sm:py-28">
        <Suspense
          fallback={
            <div className="w-full max-w-md mx-auto p-8 rounded-3xl bg-surface-container-lowest border border-outline-variant/40 animate-pulse flex flex-col items-center">
              <div className="w-12 h-12 rounded-2xl bg-surface-container-low mb-4" />
              <div className="w-32 h-6 rounded-md bg-surface-container-low mb-2" />
              <div className="w-48 h-4 rounded-md bg-surface-container-low mb-6" />
              <div className="w-full h-10 rounded-xl bg-surface-container-low mb-4" />
              <div className="w-full h-10 rounded-xl bg-surface-container-low" />
            </div>
          }
        >
          <ResetPasswordForm />
        </Suspense>
      </main>
      <Footer />
    </div>
  );
}
