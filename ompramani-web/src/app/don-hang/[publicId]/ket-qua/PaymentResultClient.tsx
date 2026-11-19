"use client";

import React, { useEffect, useState, useRef, useCallback } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useAuth } from "@/context/AuthContext";
import { getPaymentStatus, retrySepayPayment, switchOrderToCod } from "@/lib/api/shop";
import { PaymentStatusData } from "@/types/shop";
import { FireworksCelebration } from "@/components/common/FireworksCelebration";

interface PaymentResultClientProps {
  publicId: string;
}

export function PaymentResultClient({ publicId }: PaymentResultClientProps) {
  const router = useRouter();
  const { isAuthenticated, isLoading: authLoading } = useAuth();

  const [paymentData, setPaymentData] = useState<PaymentStatusData | null>(null);
  const [status, setStatus] = useState<
    "loading" | "verifying" | "paid" | "failed" | "cancelled" | "needs_reconciliation" | "error"
  >("loading");
  const [pollCount, setPollCount] = useState(0);
  const [isManualChecking, setIsManualChecking] = useState(false);
  const [actionError, setActionError] = useState<string | null>(null);
  const [actionLoading, setActionLoading] = useState(false);

  const maxPollAttempts = 10;
  const pollTimerRef = useRef<NodeJS.Timeout | null>(null);

  const fetchStatus = useCallback(
    async (isManual = false) => {
      if (isManual) {
        setIsManualChecking(true);
        setActionError(null);
      }

      try {
        const data = await getPaymentStatus(publicId);
        setPaymentData(data);

        const currentStatus = data.payment_status;

        if (currentStatus === "paid") {
          setStatus("paid");
          return "paid";
        } else if (currentStatus === "failed") {
          setStatus("failed");
          return "failed";
        } else if (currentStatus === "cancelled") {
          setStatus("cancelled");
          return "cancelled";
        } else if (currentStatus === "needs_reconciliation") {
          setStatus("needs_reconciliation");
          return "needs_reconciliation";
        } else {
          // Status is 'pending' or 'unpaid'
          setStatus("verifying");
          return "verifying";
        }
      } catch (err) {
        console.error("Failed to check payment status:", err);
        if (isManual) {
          setActionError("Không thể kết nối đến máy chủ. Quý đạo hữu vui lòng thử lại sau ít phút.");
        }
        return "error";
      } finally {
        if (isManual) {
          setIsManualChecking(false);
        }
      }
    },
    [publicId]
  );

  // Initial fetch and polling loop
  useEffect(() => {
    let isMounted = true;

    if (authLoading) return;
    if (!isAuthenticated) {
      router.replace(`/dang-nhap?redirect=/don-hang/${publicId}/ket-qua`);
      return;
    }

    const runPoll = async () => {
      const result = await fetchStatus(false);
      if (!isMounted) return;

      if (result === "verifying") {
        setPollCount((prev) => {
          const next = prev + 1;
          if (next < maxPollAttempts) {
            pollTimerRef.current = setTimeout(runPoll, 3000);
          }
          return next;
        });
      }
    };

    runPoll();

    return () => {
      isMounted = false;
      if (pollTimerRef.current) {
        clearTimeout(pollTimerRef.current);
      }
    };
  }, [publicId, isAuthenticated, authLoading, router, fetchStatus]);

  // Handle Retry Payment via SePay
  const handleRetrySepay = async () => {
    setActionLoading(true);
    setActionError(null);
    try {
      const res = await retrySepayPayment(publicId);
      if (res.payment_data) {
        // Dynamically submit form to SePay sandbox
        const form = document.createElement("form");
        form.method = "POST";
        form.action = res.payment_data.checkout_url;
        form.style.display = "none";

        Object.entries(res.payment_data.fields).forEach(([k, v]) => {
          const input = document.createElement("input");
          input.type = "hidden";
          input.name = k;
          input.value = String(v);
          form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
      }
    } catch (err: unknown) {
      console.error("Retry SePay payment failed:", err);
      const msg = err instanceof Error ? err.message : "Không thể tạo phiên thanh toán mới.";
      setActionError(msg);
      setActionLoading(false);
    }
  };

  // Handle Switch to COD
  const handleSwitchToCod = async () => {
    setActionLoading(true);
    setActionError(null);
    try {
      await switchOrderToCod(publicId);
      router.push(`/don-hang/${publicId}`);
    } catch (err: unknown) {
      console.error("Switch to COD failed:", err);
      const msg = err instanceof Error ? err.message : "Không thể chuyển sang COD lúc này.";
      setActionError(msg);
      setActionLoading(false);
    }
  };

  if (status === "loading" || authLoading) {
    return (
      <div className="max-w-xl mx-auto py-16 px-4 space-y-6 animate-pulse text-center">
        <div className="w-16 h-16 mx-auto rounded-full bg-surface-container-high" />
        <div className="h-8 bg-surface-container-high rounded-2xl w-48 mx-auto" />
        <div className="h-24 bg-surface-container-low rounded-3xl" />
      </div>
    );
  }

  return (
    <div className="max-w-2xl mx-auto py-8 sm:py-14 px-4 space-y-6">
      {/* Hiệu ứng pháo hoa khi thanh toán thành công */}
      {status === "paid" && <FireworksCelebration durationMs={7000} />}

      {/* Thông báo lỗi thao tác nếu có */}
      {actionError && (
        <div className="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/30 border border-rose-300 dark:border-rose-800 text-sm text-rose-900 dark:text-rose-200 flex items-center gap-3">
          <span className="material-symbols-outlined text-rose-600 text-xl shrink-0">error</span>
          <span className="flex-1">{actionError}</span>
          <button
            type="button"
            onClick={() => setActionError(null)}
            className="text-xs text-rose-700 hover:text-rose-900 cursor-pointer"
          >
            Đóng
          </button>
        </div>
      )}

      {/* 1. Trạng thái: THÀNH CÔNG */}
      {status === "paid" && (
        <div className="p-8 sm:p-10 rounded-3xl border border-primary/20 bg-surface-container-low text-center space-y-6 shadow-sm relative overflow-hidden">
          <div className="absolute -top-24 left-1/2 -translate-x-1/2 w-96 h-96 bg-primary/10 rounded-full blur-3xl pointer-events-none" />

          <div className="w-20 h-20 mx-auto rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 ring-8 ring-emerald-50 dark:ring-emerald-950/20 flex items-center justify-center shadow-md relative z-10">
            <span className="material-symbols-outlined text-4xl">check_circle</span>
          </div>

          <div className="space-y-2 relative z-10">
            <span className="text-xs uppercase tracking-widest font-bold text-emerald-800 dark:text-emerald-400">
              GHI NHẬN THANH TOÁN THÀNH CÔNG
            </span>
            <h1 className="font-serif text-2xl sm:text-3xl font-bold text-on-surface">
              Hoan Hỷ! Thanh Toán VietQR Thành Công
            </h1>
            <p className="font-mono text-base sm:text-lg font-bold text-primary">
              Mã đơn hàng: {paymentData?.order_number}
            </p>
          </div>

          <p className="text-xs sm:text-sm text-on-surface-variant max-w-lg mx-auto leading-relaxed relative z-10">
            Nam Mô A Di Đà Phật. OmPraMani đã ghi nhận khoản thanh toán{" "}
            <strong>{paymentData?.formatted_total}</strong> qua cổng SePay. Đơn hàng của quý đạo
            hữu đã được xác nhận và bộ phận phát hành sẽ sớm đóng gói chuyển giao.
          </p>

          <div className="flex flex-wrap items-center justify-center gap-3 pt-4 border-t border-outline-variant/30 relative z-10">
            <Link
              href={`/don-hang/${publicId}`}
              className="inline-flex items-center gap-2 px-6 py-2.5 rounded-2xl bg-primary text-on-primary font-medium text-sm shadow-sm hover:bg-primary/90 transition-all cursor-pointer"
            >
              <span className="material-symbols-outlined text-base">receipt_long</span>
              <span>Xem Chi Tiết Đơn Hàng</span>
            </Link>
            <Link
              href="/tai-khoan/don-hang"
              className="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-surface-container text-on-surface font-medium text-sm hover:bg-surface-container-high transition-all cursor-pointer"
            >
              <span className="material-symbols-outlined text-base">format_list_bulleted</span>
              <span>Đơn Hàng Của Tôi</span>
            </Link>
            <Link
              href="/cua-hang"
              className="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-surface-container text-on-surface font-medium text-sm hover:bg-surface-container-high transition-all cursor-pointer"
            >
              <span className="material-symbols-outlined text-base">storefront</span>
              <span>Về Cửa Hàng</span>
            </Link>
          </div>
        </div>
      )}

      {/* 2. Trạng thái: ĐANG XÁC MINH (Polling) */}
      {status === "verifying" && (
        <div className="p-8 sm:p-10 rounded-3xl border border-amber-300/40 bg-surface-container-low text-center space-y-6 shadow-sm">
          <div className="w-20 h-20 mx-auto rounded-full bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300 ring-8 ring-amber-50 dark:ring-amber-950/20 flex items-center justify-center shadow-md">
            <span className="material-symbols-outlined text-4xl animate-spin">
              progress_activity
            </span>
          </div>

          <div className="space-y-2">
            <span className="text-xs uppercase tracking-widest font-bold text-amber-800 dark:text-amber-400">
              ĐANG XÁC MINH GIAO DỊCH ({pollCount}/{maxPollAttempts})
            </span>
            <h1 className="font-serif text-2xl sm:text-3xl font-bold text-on-surface">
              Hệ Thống Đang Kiểm Tra Tín Hiệu Thanh Toán
            </h1>
            <p className="font-mono text-base font-bold text-primary">
              Mã đơn hàng: {paymentData?.order_number}
            </p>
          </div>

          <p className="text-xs sm:text-sm text-on-surface-variant max-w-lg mx-auto leading-relaxed">
            Ngân hàng và cổng thanh toán SePay đang xử lý giao dịch của quý đạo hữu. Hệ thống sẽ tự
            động cập nhật ngay khi nhận được tín hiệu xác nhận (IPN). Quý đạo hữu có thể an tâm
            chờ trong giây lát.
          </p>

          <div className="flex flex-wrap items-center justify-center gap-3 pt-4 border-t border-outline-variant/30">
            <button
              type="button"
              onClick={() => fetchStatus(true)}
              disabled={isManualChecking}
              className="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-primary text-on-primary font-medium text-sm shadow-sm hover:bg-primary/90 transition-all cursor-pointer disabled:opacity-50"
            >
              <span
                className={`material-symbols-outlined text-base ${
                  isManualChecking ? "animate-spin" : ""
                }`}
              >
                refresh
              </span>
              <span>{isManualChecking ? "Đang Kiểm Tra..." : "Kiểm Tra Lại Ngay"}</span>
            </button>

            {paymentData?.can_switch_to_cod && (
              <button
                type="button"
                onClick={handleSwitchToCod}
                disabled={actionLoading}
                className="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-surface-container text-on-surface font-medium text-sm hover:bg-surface-container-high transition-all cursor-pointer disabled:opacity-50"
              >
                <span className="material-symbols-outlined text-base">local_shipping</span>
                <span>Chuyển Sang Thanh Toán COD</span>
              </button>
            )}

            <Link
              href="/tai-khoan/don-hang"
              className="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-surface-container text-on-surface font-medium text-sm hover:bg-surface-container-high transition-all cursor-pointer"
            >
              <span className="material-symbols-outlined text-base">format_list_bulleted</span>
              <span>Xem Đơn Hàng Của Tôi</span>
            </Link>
          </div>
        </div>
      )}

      {/* 3. Trạng thái: CẦN ĐỐI SOÁT */}
      {status === "needs_reconciliation" && (
        <div className="p-8 sm:p-10 rounded-3xl border border-rose-300/40 bg-surface-container-low text-center space-y-6 shadow-sm">
          <div className="w-20 h-20 mx-auto rounded-full bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300 ring-8 ring-rose-50 dark:ring-rose-950/20 flex items-center justify-center shadow-md">
            <span className="material-symbols-outlined text-4xl">rule</span>
          </div>

          <div className="space-y-2">
            <span className="text-xs uppercase tracking-widest font-bold text-rose-800 dark:text-rose-400">
              GIAO DỊCH CẦN ĐỐI SOÁT
            </span>
            <h1 className="font-serif text-2xl sm:text-3xl font-bold text-on-surface">
              Giao Dịch Đã Ghi Nhận - Đang Chờ Đối Soát
            </h1>
            <p className="font-mono text-base font-bold text-primary">
              Mã đơn hàng: {paymentData?.order_number}
            </p>
          </div>

          <p className="text-xs sm:text-sm text-on-surface-variant max-w-lg mx-auto leading-relaxed">
            Hệ thống đã nhận được thông tin giao dịch từ SePay nhưng có sự chênh lệch nhỏ hoặc cần
            kiểm tra thêm thông tin đối soát. Quý đạo hữu hoàn toàn an tâm, ban quản trị sẽ chủ động
            kiểm tra và hỗ trợ kịp thời.
          </p>

          <div className="flex flex-wrap items-center justify-center gap-3 pt-4 border-t border-outline-variant/30">
            <Link
              href={`/don-hang/${publicId}`}
              className="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-primary text-on-primary font-medium text-sm shadow-sm hover:bg-primary/90 transition-all cursor-pointer"
            >
              <span className="material-symbols-outlined text-base">receipt_long</span>
              <span>Xem Trạng Thái Đơn Hàng</span>
            </Link>
            <Link
              href="/"
              className="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-surface-container text-on-surface font-medium text-sm hover:bg-surface-container-high transition-all cursor-pointer"
            >
              <span className="material-symbols-outlined text-base">home</span>
              <span>Về Trang Chủ</span>
            </Link>
          </div>
        </div>
      )}

      {/* 4. Trạng thái: THẤT BẠI HOẶC ĐÃ HỦY */}
      {(status === "failed" || status === "cancelled") && (
        <div className="p-8 sm:p-10 rounded-3xl border border-outline-variant/50 bg-surface-container-low text-center space-y-6 shadow-sm">
          <div className="w-20 h-20 mx-auto rounded-full bg-surface-container-high text-on-surface-variant flex items-center justify-center shadow-md">
            <span className="material-symbols-outlined text-4xl">
              {status === "cancelled" ? "cancel" : "error"}
            </span>
          </div>

          <div className="space-y-2">
            <span className="text-xs uppercase tracking-widest font-bold text-on-surface-variant">
              {status === "cancelled" ? "GIAO DỊCH ĐÃ HỦY" : "THANH TOÁN CHƯA HOÀN TẤT"}
            </span>
            <h1 className="font-serif text-2xl sm:text-3xl font-bold text-on-surface">
              {status === "cancelled"
                ? "Phiên Thanh Toán Đã Hủy"
                : "Thanh Toán Chưa Được Hoàn Tất"}
            </h1>
            <p className="font-mono text-base font-bold text-primary">
              Mã đơn hàng: {paymentData?.order_number}
            </p>
          </div>

          <p className="text-xs sm:text-sm text-on-surface-variant max-w-lg mx-auto leading-relaxed">
            Phiên thanh toán trực tuyến chưa hoàn tất hoặc đã bị hủy bỏ bởi người dùng. Quý đạo hữu
            có thể thử thanh toán lại qua SePay hoặc chuyển sang hình thức thanh toán khi nhận hàng
            (COD).
          </p>

          <div className="flex flex-wrap items-center justify-center gap-3 pt-4 border-t border-outline-variant/30">
            {paymentData?.can_retry_payment && (
              <button
                type="button"
                onClick={handleRetrySepay}
                disabled={actionLoading}
                className="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-primary text-on-primary font-medium text-sm shadow-sm hover:bg-primary/90 transition-all cursor-pointer disabled:opacity-50"
              >
                <span className="material-symbols-outlined text-base">qr_code_2</span>
                <span>Thanh Toán Lại Với VietQR (SePay)</span>
              </button>
            )}

            {paymentData?.can_switch_to_cod && (
              <button
                type="button"
                onClick={handleSwitchToCod}
                disabled={actionLoading}
                className="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-surface-container text-on-surface font-medium text-sm hover:bg-surface-container-high transition-all cursor-pointer disabled:opacity-50"
              >
                <span className="material-symbols-outlined text-base">local_shipping</span>
                <span>Chuyển Sang Thanh Toán COD</span>
              </button>
            )}

            <Link
              href="/tai-khoan/don-hang"
              className="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-surface-container text-on-surface font-medium text-sm hover:bg-surface-container-high transition-all cursor-pointer"
            >
              <span className="material-symbols-outlined text-base">format_list_bulleted</span>
              <span>Đơn Hàng Của Tôi</span>
            </Link>
          </div>
        </div>
      )}
    </div>
  );
}
