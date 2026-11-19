"use client";

import React from "react";
import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { useAuth } from "@/context/AuthContext";

export default function EmailVerificationResult() {
  const searchParams = useSearchParams();
  const verifiedParam = searchParams.get("verified");
  const errorParam = searchParams.get("error");
  const { isAuthenticated } = useAuth();

  const isSuccess = verifiedParam === "success" || verifiedParam === "already";
  const isAlready = verifiedParam === "already";

  return (
    <div className="w-full max-w-md mx-auto">
      <div className="bg-surface-container-lowest border border-outline-variant/60 rounded-3xl p-6 sm:p-8 shadow-[0_4px_24px_rgba(140,109,45,0.06)] text-center relative overflow-hidden">
        <div className="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-full blur-2xl pointer-events-none -mr-10 -mt-10" />

        <div className="mb-6">
          <div
            className={`w-14 h-14 rounded-2xl mx-auto flex items-center justify-center mb-4 ${
              isSuccess
                ? "bg-secondary-container text-primary"
                : "bg-error/10 text-error"
            }`}
          >
            <span className="material-symbols-outlined text-[32px]">
              {isSuccess ? "verified" : "error"}
            </span>
          </div>

          <h1 className="font-headline-md text-2xl font-bold text-on-surface">
            {isSuccess
              ? isAlready
                ? "Email Đã Được Xác Thực"
                : "Xác Thực Thành Công"
              : "Xác Thực Không Thành Công"}
          </h1>

          <p className="font-body-sm text-sm text-outline mt-2 leading-relaxed">
            {isSuccess
              ? isAlready
                ? "Địa chỉ email này đã hoàn tất xác thực trước đó. Bạn có thể an tâm tiếp tục hành trình tu học."
                : "Địa chỉ email của bạn đã được xác thực thành công trên hệ thống OmPraMani."
              : errorParam === "invalid_hash"
                ? "Mã xác thực không hợp lệ hoặc liên kết đã hết hiệu lực bảo mật."
                : errorParam === "user_not_found"
                  ? "Không tìm thấy tài khoản người dùng tương ứng với liên kết này."
                  : "Có lỗi xảy ra trong quá trình xác thực liên kết email."}
          </p>
        </div>

        <div className="pt-2">
          <Link
            href={isAuthenticated ? "/tai-khoan" : "/dang-nhap"}
            className="w-full inline-flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-primary hover:bg-primary-hover text-on-primary font-label-md text-sm font-semibold shadow-xs transition-colors"
          >
            <span className="material-symbols-outlined text-[18px]">
              {isAuthenticated ? "account_circle" : "login"}
            </span>
            <span>
              {isAuthenticated ? "Xem thông tin tài khoản" : "Đăng nhập ngay"}
            </span>
          </Link>
        </div>
      </div>
    </div>
  );
}
