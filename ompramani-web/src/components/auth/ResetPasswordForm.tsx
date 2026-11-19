"use client";

import React, { useState } from "react";
import Link from "next/link";
import Image from "next/image";
import { useSearchParams } from "next/navigation";
import { resetPassword } from "@/lib/api/auth";
import { ApiError } from "@/lib/api/client";

export default function ResetPasswordForm() {
  const searchParams = useSearchParams();
  const tokenParam = searchParams.get("token") || "";
  const emailParam = searchParams.get("email") || "";

  const [email, setEmail] = useState(emailParam);
  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isSuccess, setIsSuccess] = useState(false);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMessage(null);

    if (!tokenParam) {
      setErrorMessage("Liên kết thiếu mã xác thực token. Vui lòng yêu cầu gửi lại email khôi phục mật khẩu.");
      return;
    }

    if (!email.trim() || !password || !passwordConfirmation) {
      setErrorMessage("Vui lòng điền đầy đủ các thông tin bắt buộc.");
      return;
    }

    if (password !== passwordConfirmation) {
      setErrorMessage("Mật khẩu xác nhận không khớp.");
      return;
    }

    if (password.length < 8) {
      setErrorMessage("Mật khẩu mới phải có ít nhất 8 ký tự.");
      return;
    }

    try {
      setIsSubmitting(true);
      await resetPassword({
        token: tokenParam,
        email: email.trim(),
        password,
        password_confirmation: passwordConfirmation,
      });
      setIsSuccess(true);
    } catch (err) {
      if (err instanceof ApiError) {
        setErrorMessage(
          err.message || "Mã xác thực không hợp lệ hoặc đã hết hạn. Vui lòng yêu cầu lại."
        );
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
          <div className="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-gradient-to-br from-primary-fixed to-secondary-container p-2 shadow-xs mb-3">
            <Image
              src="https://lh3.googleusercontent.com/aida-public/AB6AXuA0yHXbepcasIOm7sdXStFD3jiTbEWkLEAuhYWHZoReJePvbIXopc820f28MkNX-wB7UZi4KzW1h5duIxv32pDxQIQCJs93zhGbWD2RuO41QWZZdoHgw9FUZ6G_5NLYvZHXMWde_BDeXdHmHhOmCpaNqKjG9fS9CESHLSlHBD4666w6Ib9scXz19o_ydlmVm8s5DqEMixxNsiVmq7vEIoiocrfJVJZOk9Jok8kDE6A"
              alt="Om Mani"
              width={40}
              height={40}
              className="w-full h-full object-contain"
            />
          </div>
          <h1 className="font-headline-md text-2xl font-bold text-primary tracking-tight">
            Đặt Lại Mật Khẩu
          </h1>
          <p className="font-body-sm text-sm text-outline mt-1">
            Thiết lập mật khẩu mới cho tài khoản của bạn
          </p>
        </div>

        {isSuccess ? (
          <div className="text-center py-4 space-y-4 animate-in fade-in">
            <div className="w-12 h-12 rounded-full bg-primary/10 text-primary mx-auto flex items-center justify-center">
              <span className="material-symbols-outlined text-[28px]">check_circle</span>
            </div>
            <div className="space-y-2">
              <h2 className="font-headline-md text-lg font-semibold text-on-surface">
                Thành công!
              </h2>
              <p className="font-body-sm text-xs text-on-surface-variant leading-relaxed">
                Mật khẩu tài khoản của bạn đã được cập nhật thành công. Bạn có thể sử dụng mật khẩu mới để đăng nhập ngay bây giờ.
              </p>
            </div>

            <div className="pt-4">
              <Link
                href="/dang-nhap"
                className="w-full inline-flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl bg-primary hover:bg-primary-hover text-on-primary font-label-md text-sm font-semibold shadow-xs transition-colors"
              >
                <span className="material-symbols-outlined text-[18px]">login</span>
                <span>Đăng nhập ngay</span>
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

            {!tokenParam && (
              <div className="mb-5 p-3.5 rounded-2xl bg-secondary/10 border border-secondary/25 text-on-surface text-xs font-body-sm flex items-start gap-2.5">
                <span className="material-symbols-outlined text-[18px] text-secondary shrink-0 mt-0.5">
                  warning
                </span>
                <div className="space-y-1">
                  <p className="font-medium">Mã đặt lại không tìm thấy trong URL.</p>
                  <p className="text-outline">
                    Vui lòng mở liên kết từ email nhận được, hoặc{" "}
                    <Link href="/quen-mat-khau" className="text-primary underline">
                      yêu cầu gửi lại email mới
                    </Link>
                    .
                  </p>
                </div>
              </div>
            )}

            {/* Form */}
            <form onSubmit={handleSubmit} className="space-y-4">
              <div>
                <label
                  htmlFor="email"
                  className="block font-label-md text-xs font-medium text-on-surface mb-1.5"
                >
                  Địa chỉ Email
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

              <div>
                <label
                  htmlFor="password"
                  className="block font-label-md text-xs font-medium text-on-surface mb-1.5"
                >
                  Mật khẩu mới (ít nhất 8 ký tự, gồm chữ và số)
                </label>
                <div className="relative">
                  <span className="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-outline text-[18px] pointer-events-none">
                    lock
                  </span>
                  <input
                    id="password"
                    type={showPassword ? "text" : "password"}
                    required
                    autoComplete="new-password"
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    placeholder="••••••••"
                    className="w-full pl-10 pr-10 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-on-surface placeholder:text-outline/70 font-body-sm text-sm focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition-all"
                  />
                  <button
                    type="button"
                    onClick={() => setShowPassword(!showPassword)}
                    className="absolute right-3 top-1/2 -translate-y-1/2 text-outline hover:text-on-surface p-0.5"
                    aria-label={showPassword ? "Ẩn mật khẩu" : "Hiện mật khẩu"}
                  >
                    <span className="material-symbols-outlined text-[18px]">
                      {showPassword ? "visibility_off" : "visibility"}
                    </span>
                  </button>
                </div>
              </div>

              <div>
                <label
                  htmlFor="passwordConfirmation"
                  className="block font-label-md text-xs font-medium text-on-surface mb-1.5"
                >
                  Xác nhận mật khẩu mới
                </label>
                <div className="relative">
                  <span className="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-outline text-[18px] pointer-events-none">
                    lock_reset
                  </span>
                  <input
                    id="passwordConfirmation"
                    type={showPassword ? "text" : "password"}
                    required
                    autoComplete="new-password"
                    value={passwordConfirmation}
                    onChange={(e) => setPasswordConfirmation(e.target.value)}
                    placeholder="••••••••"
                    className="w-full pl-10 pr-4 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-on-surface placeholder:text-outline/70 font-body-sm text-sm focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition-all"
                  />
                </div>
              </div>

              <button
                type="submit"
                disabled={isSubmitting || !tokenParam}
                className="w-full mt-3 py-3 px-4 rounded-xl bg-primary hover:bg-primary-hover text-on-primary font-label-md text-sm font-semibold shadow-xs flex items-center justify-center gap-2 transition-all disabled:opacity-60 disabled:cursor-not-allowed cursor-pointer"
              >
                {isSubmitting ? (
                  <>
                    <div className="w-4 h-4 border-2 border-on-primary border-t-transparent rounded-full animate-spin" />
                    <span>Đang cập nhật mật khẩu...</span>
                  </>
                ) : (
                  <>
                    <span className="material-symbols-outlined text-[18px]">
                      password
                    </span>
                    <span>Xác nhận mật khẩu mới</span>
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
