"use client";

import React, { useState, useEffect } from "react";
import Link from "next/link";
import Image from "next/image";
import { useRouter, useSearchParams } from "next/navigation";
import { useAuth } from "@/context/AuthContext";
import { sanitizeNextUrl } from "@/lib/utils/sacred";
import { ApiError } from "@/lib/api/client";
import { ApiValidationErrorPayload } from "@/types/auth";

export default function RegisterForm() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const { register, isAuthenticated, isLoading: isAuthLoading } = useAuth();

  const nextParam = searchParams.get("next");
  const targetUrl = sanitizeNextUrl(nextParam);

  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({});

  // If already authenticated, redirect immediately
  useEffect(() => {
    if (!isAuthLoading && isAuthenticated) {
      router.replace(targetUrl);
    }
  }, [isAuthenticated, isAuthLoading, router, targetUrl]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMessage(null);
    setFieldErrors({});

    if (!name.trim() || !email.trim() || !password || !passwordConfirmation) {
      setErrorMessage("Vui lòng điền đầy đủ các thông tin bắt buộc.");
      return;
    }

    if (password !== passwordConfirmation) {
      setErrorMessage("Mật khẩu xác nhận không khớp.");
      return;
    }

    if (password.length < 8) {
      setErrorMessage("Mật khẩu phải chứa ít nhất 8 ký tự.");
      return;
    }

    try {
      setIsSubmitting(true);
      await register({
        name: name.trim(),
        email: email.trim(),
        password,
        password_confirmation: passwordConfirmation,
      });
      router.replace(targetUrl);
    } catch (err) {
      if (err instanceof ApiError) {
        const payload = err.data as ApiValidationErrorPayload | undefined;
        if (payload?.errors) {
          setFieldErrors(payload.errors);
          // Pick first validation error message
          const firstKey = Object.keys(payload.errors)[0];
          setErrorMessage(payload.errors[firstKey][0] || err.message);
        } else {
          setErrorMessage(err.message || "Không thể hoàn tất đăng ký.");
        }
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
            Tạo Tài Khoản
          </h1>
          <p className="font-body-sm text-sm text-outline mt-1">
            Gia nhập cộng đồng tu học và lưu giữ hành trình chuyển hóa
          </p>
        </div>

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
              htmlFor="name"
              className="block font-label-md text-xs font-medium text-on-surface mb-1.5"
            >
              Họ và tên / Pháp danh
            </label>
            <div className="relative">
              <span className="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-outline text-[18px] pointer-events-none">
                person
              </span>
              <input
                id="name"
                type="text"
                required
                autoComplete="name"
                value={name}
                onChange={(e) => setName(e.target.value)}
                placeholder="Ví dụ: Thích Tâm An hoặc Nguyễn Văn An"
                className="w-full pl-10 pr-4 py-2.5 bg-surface-container-low border border-outline-variant/60 rounded-xl text-on-surface placeholder:text-outline/70 font-body-sm text-sm focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition-all"
              />
            </div>
            {fieldErrors.name && (
              <p className="font-body-sm text-xs text-error mt-1">{fieldErrors.name[0]}</p>
            )}
          </div>

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
            {fieldErrors.email && (
              <p className="font-body-sm text-xs text-error mt-1">{fieldErrors.email[0]}</p>
            )}
          </div>

          <div>
            <label
              htmlFor="password"
              className="block font-label-md text-xs font-medium text-on-surface mb-1.5"
            >
              Mật khẩu (ít nhất 8 ký tự, gồm chữ và số)
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
            {fieldErrors.password && (
              <p className="font-body-sm text-xs text-error mt-1">{fieldErrors.password[0]}</p>
            )}
          </div>

          <div>
            <label
              htmlFor="passwordConfirmation"
              className="block font-label-md text-xs font-medium text-on-surface mb-1.5"
            >
              Xác nhận mật khẩu
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
            disabled={isSubmitting}
            className="w-full mt-3 py-3 px-4 rounded-xl bg-primary hover:bg-primary-hover text-on-primary font-label-md text-sm font-semibold shadow-xs flex items-center justify-center gap-2 transition-all disabled:opacity-60 disabled:cursor-not-allowed cursor-pointer"
          >
            {isSubmitting ? (
              <>
                <div className="w-4 h-4 border-2 border-on-primary border-t-transparent rounded-full animate-spin" />
                <span>Đang khởi tạo tài khoản...</span>
              </>
            ) : (
              <>
                <span className="material-symbols-outlined text-[18px]">
                  how_to_reg
                </span>
                <span>Đăng ký</span>
              </>
            )}
          </button>
        </form>

        {/* Footer info */}
        <div className="mt-6 pt-5 border-t border-outline-variant/40 text-center">
          <p className="font-body-sm text-xs text-outline">
            Đã có tài khoản?{" "}
            <Link
              href={nextParam ? `/dang-nhap?next=${encodeURIComponent(nextParam)}` : "/dang-nhap"}
              className="text-primary font-medium hover:underline"
            >
              Đăng nhập ngay
            </Link>
          </p>
        </div>
      </div>
    </div>
  );
}
