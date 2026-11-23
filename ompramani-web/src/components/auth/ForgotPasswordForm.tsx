"use client";

import React, { useState } from "react";
import Link from "next/link";
import { forgotPassword } from "@/lib/api/auth";
import { ApiError } from "@/lib/api/client";

export default function ForgotPasswordForm() {
  const [email, setEmail] = useState("");
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isSubmitted, setIsSubmitted] = useState(false);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMessage(null);

    if (!email.trim()) {
      setErrorMessage("Vui lòng nhập địa chỉ email của bạn.");
      return;
    }

    try {
      setIsSubmitting(true);
      await forgotPassword({ email: email.trim() });
      setIsSubmitted(true);
    } catch (err) {
      if (err instanceof ApiError) {
        setErrorMessage(err.message || "Không thể gửi yêu cầu đặt lại mật khẩu.");
      } else {
        setErrorMessage("Không thể kết nối đến máy chủ. Vui lòng thử lại sau.");
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="w-full max-w-md mx-auto">
      <div className="bg-surface-container-lowest border border-outline-variant/60 rounded-3xl p-6 sm:p-8 shadow-[0_4px_24px_rgba(140,109,45,0.06)] relative overflow-hidden">
        {/* Subtle decorative motif */}
        <div className="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-full blur-2xl pointer-events-none -mr-10 -mt-10" />

        {/* Header */}
        <div className="text-center mb-6">
          <div className="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-gradient-to-br from-primary-fixed to-secondary-container p-2 shadow-xs mb-3 text-primary">
            <span className="material-symbols-outlined text-[28px]">lock_reset</span>
          </div>
          <h1 className="font-headline-md text-2xl font-bold text-primary tracking-tight">
            Quên Mật Khẩu
          </h1>
          <p className="font-body-sm text-sm text-outline mt-1">
            Nhập email tài khoản để nhận hướng dẫn khôi phục mật khẩu
          </p>
        </div>

        {isSubmitted ? (
          <div className="text-center py-4 space-y-4 animate-in fade-in">
            <div className="w-12 h-12 rounded-full bg-primary/10 text-primary mx-auto flex items-center justify-center">
              <span className="material-symbols-outlined text-[28px]">mark_email_read</span>
            </div>
            <div className="space-y-2">
              <h2 className="font-headline-md text-lg font-semibold text-on-surface">
                Đã gửi yêu cầu khôi phục
              </h2>
              <p className="font-body-sm text-xs text-on-surface-variant leading-relaxed">
                Nếu địa chỉ email <strong className="text-primary">{email}</strong> tồn tại trong hệ thống, chúng tôi đã gửi liên kết đặt lại mật khẩu đến hòm thư của bạn.
              </p>
              <p className="font-body-sm text-[11px] text-outline">
                (Vui lòng kiểm tra hộp thư đến hoặc mục thư rác / spam)
              </p>
            </div>

            <div className="pt-4">
              <Link
                href="/dang-nhap"
                className="w-full inline-flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl bg-primary hover:bg-primary-hover text-on-primary font-label-md text-sm font-semibold shadow-xs transition-colors"
              >
                <span className="material-symbols-outlined text-[18px]">arrow_back</span>
                <span>Quay lại trang Đăng nhập</span>
              </Link>
            </div>
          </div>
        ) : (
          <>
            {/* Error Alert */}
            {errorMessage && (
              <div className="mb-5 p-3.5 rounded-2xl bg-error/10 border border-error/25 text-error text-xs font-body-sm flex items-start gap-2.5 animate-in fade-in">
                <span className="material-symbols-outlined text-[18px] shrink-0 mt-0.5">
                  error
                </span>
                <span className="leading-relaxed">{errorMessage}</span>
              </div>
            )}

            {/* Form */}
            <form onSubmit={handleSubmit} className="space-y-4">
              <div>
                <label
                  htmlFor="email"
                  className="block font-label-md text-xs font-medium text-on-surface mb-1.5"
                >
                  Địa chỉ Email đăng ký
                </label>
                <div className="relative">
                  <span className="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-outline text-[18px] pointer-events-none">
                    mail
                  </span>
                  <input
                    id="email"
                    type="email"
                    required
                    autoComplete="email"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    placeholder="phattu@example.com"
                    className="w-full pl-10 pr-4 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-on-surface placeholder:text-outline/70 font-body-sm text-sm focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition-all"
                  />
                </div>
              </div>

              <button
                type="submit"
                disabled={isSubmitting}
                className="w-full mt-3 py-3 px-4 rounded-xl bg-primary hover:bg-primary-hover text-on-primary font-label-md text-sm font-semibold shadow-xs flex items-center justify-center gap-2 transition-all disabled:opacity-60 disabled:cursor-not-allowed cursor-pointer"
              >
                {isSubmitting ? (
                  <>
                    <div className="w-4 h-4 border-2 border-on-primary border-t-transparent rounded-full animate-spin" />
                    <span>Đang gửi yêu cầu...</span>
                  </>
                ) : (
                  <>
                    <span className="material-symbols-outlined text-[18px]">send</span>
                    <span>Gửi liên kết khôi phục</span>
                  </>
                )}
              </button>
            </form>

            {/* Footer info */}
            <div className="mt-6 pt-5 border-t border-outline-variant/40 text-center">
              <Link
                href="/dang-nhap"
                className="font-body-sm text-xs text-primary font-medium hover:underline inline-flex items-center gap-1"
              >
                <span className="material-symbols-outlined text-[16px]">arrow_back</span>
                <span>Quay lại trang Đăng nhập</span>
              </Link>
            </div>
          </>
        )}
      </div>
    </div>
  );
}
