"use client";

import React, { useState, useEffect, useRef } from "react";
import Link from "next/link";
import Image from "next/image";
import { useRouter } from "next/navigation";
import { useAuth } from "@/context/AuthContext";
import { getMyOrders } from "@/lib/api/shop";
import { OrderSummary, OrderPaginationMeta } from "@/types/shop";

const STATUS_TABS = [
  { key: "all", label: "Tất cả" },
  { key: "pending", label: "Chờ xác nhận" },
  { key: "confirmed", label: "Đã xác nhận" },
  { key: "processing", label: "Đang chuẩn bị" },
  { key: "completed", label: "Hoàn tất" },
  { key: "cancelled", label: "Đã hủy" },
];

function formatSafeImageUrl(url?: string | null): string | null {
  if (!url) return null;
  const trimmed = url.trim();
  if (!trimmed) return null;
  if (trimmed.startsWith("http://") || trimmed.startsWith("https://") || trimmed.startsWith("/")) {
    return trimmed;
  }
  return `/storage/${trimmed}`;
}

export function OrderListClient() {
  const router = useRouter();
  const { isAuthenticated, isLoading: authLoading } = useAuth();

  const [activeTab, setActiveTab] = useState("all");
  const [page, setPage] = useState(1);
  const [orders, setOrders] = useState<OrderSummary[]>([]);
  const [meta, setMeta] = useState<OrderPaginationMeta | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);

  const fetchReqIdRef = useRef(0);

  // Redirect guest to login
  useEffect(() => {
    if (!authLoading && !isAuthenticated) {
      router.replace("/dang-nhap?redirect=/tai-khoan/don-hang");
    }
  }, [authLoading, isAuthenticated, router]);

  // Load orders
  useEffect(() => {
    if (authLoading || !isAuthenticated) return;

    const reqId = ++fetchReqIdRef.current;
    Promise.resolve().then(() => {
      if (reqId === fetchReqIdRef.current) {
        setIsLoading(true);
        setErrorMsg(null);
      }
    });

    getMyOrders(page, 10, activeTab)
      .then((res) => {
        if (reqId === fetchReqIdRef.current) {
          setOrders(res.data);
          setMeta(res.meta);
          setIsLoading(false);
        }
      })
      .catch((err) => {
        if (reqId === fetchReqIdRef.current) {
          console.error("Failed to load orders:", err);
          setErrorMsg("Không thể tải danh sách đơn hàng lúc này. Xin quý đạo hữu thử lại sau.");
          setIsLoading(false);
        }
      });
  }, [page, activeTab, isAuthenticated, authLoading]);

  const handleTabChange = (tabKey: string) => {
    setActiveTab(tabKey);
    setPage(1);
  };

  const getStatusBadgeClass = (status: string) => {
    switch (status) {
      case "pending":
        return "bg-amber-100 text-amber-900 border-amber-300 dark:bg-amber-950/40 dark:text-amber-200 dark:border-amber-800";
      case "confirmed":
        return "bg-sky-100 text-sky-900 border-sky-300 dark:bg-sky-950/40 dark:text-sky-200 dark:border-sky-800";
      case "processing":
        return "bg-indigo-100 text-indigo-900 border-indigo-300 dark:bg-indigo-950/40 dark:text-indigo-200 dark:border-indigo-800";
      case "completed":
        return "bg-emerald-100 text-emerald-900 border-emerald-300 dark:bg-emerald-950/40 dark:text-emerald-200 dark:border-emerald-800";
      case "cancelled":
        return "bg-rose-100 text-rose-900 border-rose-300 dark:bg-rose-950/40 dark:text-rose-200 dark:border-rose-800";
      default:
        return "bg-stone-100 text-stone-800 border-stone-300 dark:bg-stone-800 dark:text-stone-200";
    }
  };

  const getPaymentBadgeClass = (status: string) => {
    switch (status) {
      case "paid":
        return "bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-300";
      case "pending":
        return "bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/30 dark:text-amber-300";
      case "unpaid":
      default:
        return "bg-stone-50 text-stone-700 border-stone-200 dark:bg-stone-900 dark:text-stone-300";
    }
  };

  if (authLoading) {
    return (
      <div className="max-w-4xl mx-auto py-16 space-y-6 animate-pulse">
        <div className="h-8 bg-surface-container-high rounded-xl w-60" />
        <div className="h-12 bg-surface-container-low rounded-2xl" />
        <div className="space-y-4">
          <div className="h-32 bg-surface-container-low rounded-3xl" />
          <div className="h-32 bg-surface-container-low rounded-3xl" />
        </div>
      </div>
    );
  }

  if (!isAuthenticated) {
    return (
      <div className="max-w-md mx-auto py-20 text-center space-y-4">
        <span className="material-symbols-outlined text-4xl text-primary animate-spin">
          progress_activity
        </span>
        <p className="text-sm text-on-surface-variant">
          Đang chuyển hướng tới trang đăng nhập...
        </p>
      </div>
    );
  }

  return (
    <div className="max-w-4xl mx-auto space-y-8">
      {/* Header & Breadcrumb */}
      <div className="space-y-2">
        <div className="flex items-center gap-2 text-xs text-on-surface-variant">
          <Link href="/tai-khoan" className="hover:text-primary transition-colors">
            Tài Khoản
          </Link>
          <span>/</span>
          <span className="text-on-surface font-medium">Đơn Hàng Của Tôi</span>
        </div>
        <h1 className="font-serif text-2xl sm:text-3xl font-bold text-on-surface">
          Đơn Hàng Của Tôi
        </h1>
        <p className="text-xs sm:text-sm text-on-surface-variant">
          Theo dõi hành trình chuẩn bị và giao nhận các vật phẩm tu học của quý đạo hữu.
        </p>
      </div>

      {/* Filter Tabs */}
      <div className="flex items-center gap-1.5 overflow-x-auto pb-2 border-b border-outline-variant/30 scrollbar-none">
        {STATUS_TABS.map((tab) => (
          <button
            key={tab.key}
            type="button"
            onClick={() => handleTabChange(tab.key)}
            className={`px-4 py-2 rounded-2xl text-xs sm:text-sm font-medium whitespace-nowrap transition-all cursor-pointer ${
              activeTab === tab.key
                ? "bg-primary text-on-primary font-semibold shadow-xs"
                : "bg-surface-container-low text-on-surface-variant hover:bg-surface-container hover:text-on-surface"
            }`}
          >
            {tab.label}
          </button>
        ))}
      </div>

      {/* Error state */}
      {errorMsg && (
        <div className="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/30 border border-rose-300 dark:border-rose-800 text-sm text-rose-800 dark:text-rose-200 flex items-center gap-3">
          <span className="material-symbols-outlined text-rose-600 text-xl">error</span>
          <span>{errorMsg}</span>
        </div>
      )}

      {/* Loading Skeleton */}
      {isLoading ? (
        <div className="space-y-4 animate-pulse">
          <div className="h-36 bg-surface-container-low rounded-3xl" />
          <div className="h-36 bg-surface-container-low rounded-3xl" />
          <div className="h-36 bg-surface-container-low rounded-3xl" />
        </div>
      ) : orders.length === 0 ? (
        /* Empty State */
        <div className="p-12 rounded-3xl bg-surface-container-low/60 border border-outline-variant/40 text-center space-y-5">
          <div className="w-16 h-16 mx-auto rounded-full bg-surface-container-high flex items-center justify-center text-outline">
            <span className="material-symbols-outlined text-3xl">shopping_bag</span>
          </div>
          <div className="space-y-1">
            <h3 className="font-serif text-lg font-bold text-on-surface">
              {activeTab === "all"
                ? "Quý đạo hữu chưa có đơn hàng nào"
                : `Không có đơn hàng nào ở trạng thái "${STATUS_TABS.find((t) => t.key === activeTab)?.label}"`}
            </h3>
            <p className="text-xs sm:text-sm text-on-surface-variant max-w-md mx-auto leading-relaxed">
              Hãy ghé thăm Cửa Hàng để thỉnh pháp khí, kinh sách hoặc chuỗi tràng hạt trợ duyên cho hành trình tu tập.
            </p>
          </div>
          <div>
            <Link
              href="/cua-hang"
              className="inline-flex items-center gap-2 px-6 py-2.5 rounded-2xl bg-primary text-on-primary font-medium text-sm shadow-sm hover:bg-primary/90 transition-all cursor-pointer"
            >
              <span className="material-symbols-outlined text-base">storefront</span>
              <span>Khám Phá Cửa Hàng Vật Phẩm</span>
            </Link>
          </div>
        </div>
      ) : (
        /* Orders List */
        <div className="space-y-4">
          {orders.map((order) => (
            <div
              key={order.public_id}
              className="p-5 sm:p-6 rounded-3xl bg-surface-container-low/70 border border-outline-variant/40 hover:border-primary/40 transition-all space-y-4 shadow-xs"
            >
              {/* Order Card Header */}
              <div className="flex flex-wrap items-center justify-between gap-3 border-b border-outline-variant/20 pb-3">
                <div className="space-y-0.5">
                  <div className="flex items-center gap-2">
                    <span className="font-mono text-sm sm:text-base font-bold text-on-surface">
                      {order.order_number}
                    </span>
                    <span className="text-xs text-on-surface-variant">
                      ({order.item_count} vật phẩm)
                    </span>
                  </div>
                  <p className="text-[11px] text-on-surface-variant">
                    Thời gian đặt: {new Date(order.placed_at).toLocaleDateString("vi-VN", {
                      day: "2-digit",
                      month: "2-digit",
                      year: "numeric",
                      hour: "2-digit",
                      minute: "2-digit",
                    })}
                  </p>
                </div>

                <div className="flex flex-wrap items-center gap-2">
                  <span
                    className={`px-3 py-1 rounded-full text-xs font-semibold border ${getStatusBadgeClass(
                      order.order_status
                    )}`}
                  >
                    {order.order_status_label}
                  </span>
                  <span
                    className={`px-2.5 py-0.5 rounded-full text-[11px] font-medium border ${getPaymentBadgeClass(
                      order.payment_status
                    )}`}
                  >
                    {order.payment_status_label}
                  </span>
                </div>
              </div>

              {/* Order Card Body */}
              <div className="flex items-center justify-between gap-4">
                <div className="flex items-center gap-3 min-w-0">
                  <div className="relative w-12 h-12 rounded-xl bg-surface-container overflow-hidden shrink-0 border border-outline-variant/30">
                    {formatSafeImageUrl(order.first_item_thumbnail) ? (
                      <Image
                        src={formatSafeImageUrl(order.first_item_thumbnail)!}
                        alt={order.first_item_name || "Vật phẩm"}
                        fill
                        className="object-cover"
                        sizes="48px"
                      />
                    ) : (
                      <div className="w-full h-full flex items-center justify-center text-on-surface-variant/40">
                        <span className="material-symbols-outlined text-lg">spa</span>
                      </div>
                    )}
                  </div>
                  <div className="min-w-0">
                    <p className="text-xs sm:text-sm font-semibold text-on-surface truncate">
                      {order.first_item_name || "Vật phẩm Phật giáo"}
                    </p>
                    {order.item_count > 1 && (
                      <p className="text-[11px] text-on-surface-variant">
                        và {order.item_count - 1} vật phẩm khác
                      </p>
                    )}
                    <p className="text-[11px] text-on-surface-variant/80 mt-0.5">
                      Hình thức: {order.payment_method_label}
                    </p>
                  </div>
                </div>

                <div className="text-right shrink-0">
                  <span className="text-[11px] text-on-surface-variant block">Tổng tiền:</span>
                  <span className="font-serif font-bold text-base sm:text-lg text-amber-700 dark:text-amber-400">
                    {order.formatted_total}
                  </span>
                </div>
              </div>

              {/* Order Card Footer */}
              <div className="pt-2 border-t border-outline-variant/20 flex items-center justify-between">
                <div>
                  {order.can_cancel && (
                    <span className="text-[11px] text-amber-800 dark:text-amber-300 font-medium">
                      • Có thể hủy đơn trực tiếp
                    </span>
                  )}
                </div>
                <Link
                  href={`/don-hang/${order.public_id}`}
                  className="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface text-xs font-semibold transition-colors cursor-pointer"
                >
                  <span>Xem Chi Tiết</span>
                  <span className="material-symbols-outlined text-sm">arrow_forward</span>
                </Link>
              </div>
            </div>
          ))}

          {/* Pagination */}
          {meta && meta.last_page > 1 && (
            <div className="flex items-center justify-center gap-3 pt-6">
              <button
                type="button"
                disabled={page <= 1}
                onClick={() => setPage((p) => Math.max(1, p - 1))}
                className="px-4 py-2 rounded-xl text-xs font-medium bg-surface-container hover:bg-surface-container-high disabled:opacity-40 disabled:cursor-not-allowed transition-all"
              >
                Trang trước
              </button>
              <span className="text-xs font-mono text-on-surface-variant">
                Trang {meta.current_page} / {meta.last_page}
              </span>
              <button
                type="button"
                disabled={page >= meta.last_page}
                onClick={() => setPage((p) => p + 1)}
                className="px-4 py-2 rounded-xl text-xs font-medium bg-surface-container hover:bg-surface-container-high disabled:opacity-40 disabled:cursor-not-allowed transition-all"
              >
                Trang sau
              </button>
            </div>
          )}
        </div>
      )}
    </div>
  );
}