"use client";

import React, { useEffect, useState } from "react";
import Link from "next/link";
import { OrderDetail } from "@/types/shop";
import { getOrderConfirmation, switchOrderToCod } from "@/lib/api/shop";
import { useAuth } from "@/context/AuthContext";
import { ApiError } from "@/lib/api/client";
import { FireworksCelebration } from "@/components/common/FireworksCelebration";

export function OrderConfirmationClient({ publicId }: { publicId: string }) {
  const { isAuthenticated, isLoading: authLoading } = useAuth();
  const [order, setOrder] = useState<OrderDetail | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);

  // VNPAY Actions State (nếu đơn hàng chọn VNPAY nhưng chưa thanh toán)
  const [paymentActionLoading, setPaymentActionLoading] = useState(false);
  const [paymentActionError, setPaymentActionError] = useState<string | null>(null);
  const [actionSuccessMsg, setActionSuccessMsg] = useState<string | null>(null);

  useEffect(() => {
    let isMounted = true;

    if (authLoading) return;

    if (!isAuthenticated) {
      Promise.resolve().then(() => {
        if (isMounted) {
          setIsLoading(false);
          setErrorMsg("Quý đạo hữu cần đăng nhập để xem thông tin đơn hàng này.");
        }
      });
      return;
    }

    getOrderConfirmation(publicId)
      .then((data) => {
        if (!isMounted) return;
        if (data) {
          setOrder(data);
        } else {
          setErrorMsg("Không tìm thấy đơn hàng hoặc đơn hàng không thuộc tài khoản của quý đạo hữu.");
        }
      })
      .catch((err) => {
        if (!isMounted) return;
        console.error("Failed to load order confirmation:", err);
        setErrorMsg("Không thể tải thông tin đơn hàng lúc này. Xin quý đạo hữu vui lòng thử lại sau.");
      })
      .finally(() => {
        if (isMounted) {
          setIsLoading(false);
        }
      });

    return () => {
      isMounted = false;
    };
  }, [publicId, isAuthenticated, authLoading]);

  const handleSwitchToCod = async () => {
    if (!order) return;
    setPaymentActionLoading(true);
    setPaymentActionError(null);
    try {
      const res = await switchOrderToCod(order.public_id);
      setOrder(res.order);
      setActionSuccessMsg("Đã chuyển sang hình thức thanh toán COD thành công.");
    } catch (err) {
      console.error("Switch to COD failed:", err);
      if (err instanceof ApiError) {
        setPaymentActionError(err.message || "Không thể chuyển sang COD.");
      } else {
        setPaymentActionError("Lỗi kết nối. Vui lòng thử lại sau.");
      }
    } finally {
      setPaymentActionLoading(false);
    }
  };

  // Loading skeleton
  if (isLoading || authLoading) {
    return (
      <div className="max-w-2xl mx-auto py-16 px-4 space-y-6 animate-pulse">
        <div className="h-20 bg-surface-container-high rounded-3xl" />
        <div className="h-48 bg-surface-container-low rounded-3xl" />
      </div>
    );
  }

  // Error / Not found state
  if (errorMsg || !order) {
    return (
      <div className="max-w-xl mx-auto py-16 px-4 text-center space-y-6">
        <div className="w-16 h-16 mx-auto rounded-full bg-surface-container-high flex items-center justify-center text-outline">
          <span className="material-symbols-outlined text-3xl">receipt_long</span>
        </div>
        <div className="space-y-2">
          <h1 className="font-serif text-2xl font-bold text-on-surface">
            Không Tìm Thấy Đơn Hàng
          </h1>
          <p className="text-sm text-on-surface-variant max-w-md mx-auto leading-relaxed">
            {errorMsg || "Đơn hàng này không tồn tại hoặc quý đạo hữu chưa được phân quyền truy cập."}
          </p>
        </div>
        <div className="flex flex-wrap items-center justify-center gap-3 pt-2">
          <Link
            href="/tai-khoan/don-hang"
            className="inline-flex items-center gap-2 px-6 py-2.5 rounded-2xl bg-surface-container text-on-surface font-medium text-sm hover:bg-surface-container-high transition-all"
          >
            <span className="material-symbols-outlined text-base">format_list_bulleted</span>
            <span>Đơn Hàng Của Tôi</span>
          </Link>
          <Link
            href="/cua-hang"
            className="inline-flex items-center gap-2 px-6 py-2.5 rounded-2xl bg-primary text-on-primary font-medium text-sm shadow-sm hover:bg-primary/90 transition-all"
          >
            <span className="material-symbols-outlined text-base">storefront</span>
            <span>Về Cửa Hàng Vật Phẩm</span>
          </Link>
        </div>
      </div>
    );
  }

  const isCancelled = order.order_status === "cancelled";

  return (
    <div className="max-w-2xl mx-auto py-8 sm:py-14 px-4 space-y-6">
      {/* Hiệu ứng bắn pháo hoa chúc mừng khi đặt hàng thành công */}
      {!isCancelled && <FireworksCelebration durationMs={6500} />}

      {/* Thông báo cập nhật trạng thái nếu có */}
      {actionSuccessMsg && (
        <div className="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-300 dark:border-emerald-800 text-sm text-emerald-900 dark:text-emerald-200 flex items-center gap-3">
          <span className="material-symbols-outlined text-emerald-600 text-xl shrink-0">
            check_circle
          </span>
          <span className="flex-1">{actionSuccessMsg}</span>
          <button
            type="button"
            onClick={() => setActionSuccessMsg(null)}
            className="text-xs text-emerald-700 hover:text-emerald-900 cursor-pointer"
          >
            Đóng
          </button>
        </div>
      )}

      {/* Ô duy nhất trên cùng: Chúc mừng đặt hàng thành công */}
      <div
        className={`p-8 sm:p-10 rounded-3xl border text-center space-y-6 shadow-sm relative overflow-hidden ${
          isCancelled
            ? "bg-rose-50/50 dark:bg-rose-950/20 border-rose-200 dark:border-rose-800"
            : "bg-surface-container-low border-primary/20"
        }`}
      >
        {/* Vầng sáng trang trí */}
        {!isCancelled && (
          <div className="absolute -top-24 left-1/2 -translate-x-1/2 w-96 h-96 bg-primary/10 rounded-full blur-3xl pointer-events-none" />
        )}

        <div
          className={`w-20 h-20 mx-auto rounded-full flex items-center justify-center shadow-md relative z-10 ${
            isCancelled
              ? "bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300"
              : "bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 ring-8 ring-emerald-50 dark:ring-emerald-950/20"
          }`}
        >
          <span className="material-symbols-outlined text-4xl">
            {isCancelled ? "cancel" : "check_circle"}
          </span>
        </div>

        <div className="space-y-2 relative z-10">
          <span
            className={`text-xs uppercase tracking-widest font-bold ${
              isCancelled
                ? "text-rose-800 dark:text-rose-400"
                : "text-emerald-800 dark:text-emerald-400"
            }`}
          >
            {isCancelled ? "ĐƠN HÀNG ĐÃ HỦY" : "CHÚC MỪNG ĐẶT HÀNG THÀNH CÔNG"}
          </span>
          <h1 className="font-serif text-2xl sm:text-3xl lg:text-4xl font-bold text-on-surface">
            {isCancelled
              ? "Đơn Hàng Đã Được Hủy"
              : "Hoan Hỷ! Đơn Hàng Đã Được Tiếp Nhận"}
          </h1>
          <p className="font-mono text-base sm:text-lg font-bold text-primary pt-1">
            Mã đơn hàng: {order.order_number}
          </p>
        </div>

        <p className="text-xs sm:text-sm text-on-surface-variant max-w-lg mx-auto leading-relaxed relative z-10">
          {isCancelled
            ? `Đơn hàng này đã được hủy vào lúc ${
                order.cancelled_at
                  ? new Date(order.cancelled_at).toLocaleDateString("vi-VN", {
                      day: "2-digit",
                      month: "2-digit",
                      year: "numeric",
                      hour: "2-digit",
                      minute: "2-digit",
                    })
                  : ""
              }${order.cancellation_reason ? ` (Lý do: ${order.cancellation_reason})` : ""}. Số lượng vật phẩm đã được hoàn trả về kho.`
            : "Nam Mô Bổn Sư Thích Ca Mâu Ni Phật. Ban quản trị OmPraMani xin tri ân sự trợ duyên của quý đạo hữu. Vật phẩm tu học sẽ được kiểm tra và đóng gói trang nghiêm trước khi bàn giao đơn vị vận chuyển."}
        </p>

        {/* Khung xử lý phụ trợ nếu là đơn VNPAY chưa thanh toán */}
        {order.payment_method === "vnpay" && order.payment_status !== "paid" && !isCancelled && (
          <div className="p-5 rounded-2xl bg-amber-50/70 dark:bg-amber-950/20 border border-amber-300 dark:border-amber-800 text-left space-y-3 relative z-10">
            <div className="flex items-start gap-3">
              <span className="material-symbols-outlined text-amber-600 text-xl shrink-0 mt-0.5">
                pending_actions
              </span>
              <div className="space-y-1 flex-1">
                <h3 className="font-semibold text-xs sm:text-sm text-on-surface">
                  Chưa ghi nhận hoàn tất thanh toán qua VNPAY
                </h3>
                <p className="text-xs text-on-surface-variant leading-relaxed">
                  Đơn hàng của quý đạo hữu đã được khởi tạo. Nếu giao dịch bị gián đoạn, quý đạo hữu có thể tiếp tục thanh toán hoặc chuyển sang hình thức thanh toán tiền mặt khi nhận hàng (COD).
                </p>
              </div>
            </div>

            {paymentActionError && (
              <p className="text-xs text-rose-600 font-medium pl-8">{paymentActionError}</p>
            )}

            <div className="flex flex-wrap items-center gap-2.5 pt-1 pl-8">
              {(order.can_switch_to_cod ?? true) && (
                <button
                  type="button"
                  onClick={handleSwitchToCod}
                  disabled={paymentActionLoading}
                  className="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-surface border border-outline-variant/50 text-on-surface font-medium text-xs hover:bg-surface-container transition-all disabled:opacity-50 cursor-pointer"
                >
                  <span className="material-symbols-outlined text-sm">local_shipping</span>
                  <span>Chuyển sang thanh toán COD</span>
                </button>
              )}
            </div>
          </div>
        )}

        {/* Các nút điều hướng thao tác */}
        <div className="flex flex-wrap items-center justify-center gap-3 pt-4 border-t border-outline-variant/30 relative z-10">
          <Link
            href="/tai-khoan/don-hang"
            className="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-surface-container text-on-surface font-medium text-sm hover:bg-surface-container-high transition-all cursor-pointer"
          >
            <span className="material-symbols-outlined text-base">format_list_bulleted</span>
            <span>Đơn Hàng Của Tôi</span>
          </Link>
          <Link
            href="/cua-hang"
            className="inline-flex items-center gap-2 px-6 py-2.5 rounded-2xl bg-primary text-on-primary font-medium text-sm shadow-sm hover:bg-primary/90 transition-all cursor-pointer"
          >
            <span className="material-symbols-outlined text-base">storefront</span>
            <span>Tiếp Tục Thỉnh Vật Phẩm</span>
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
    </div>
  );
}
