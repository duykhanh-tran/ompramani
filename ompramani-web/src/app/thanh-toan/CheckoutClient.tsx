"use client";

import React, { useState, useEffect, useRef, useTransition } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import Image from "next/image";
import { useAuth } from "@/context/AuthContext";
import { useCart } from "@/context/CartContext";
import { previewCheckout, createOrder } from "@/lib/api/shop";
import { CheckoutPreviewData, CreateOrderPayload } from "@/types/shop";
import { ApiError } from "@/lib/api/client";

const VIETNAM_PROVINCES = [
  "Hà Nội",
  "TP. Hồ Chí Minh",
  "Đà Nẵng",
  "Hải Phòng",
  "Cần Thơ",
  "Thừa Thiên Huế",
  "An Giang",
  "Bà Rịa - Vũng Tàu",
  "Bắc Giang",
  "Bắc Kạn",
  "Bạc Liêu",
  "Bắc Ninh",
  "Bến Tre",
  "Bình Định",
  "Bình Dương",
  "Bình Phước",
  "Bình Thuận",
  "Cà Mau",
  "Cao Bằng",
  "Đắk Lắk",
  "Đắk Nông",
  "Điện Biên",
  "Đồng Nai",
  "Đồng Tháp",
  "Gia Lai",
  "Hà Giang",
  "Hà Nam",
  "Hà Tĩnh",
  "Hải Dương",
  "Hậu Giang",
  "Hòa Bình",
  "Hưng Yên",
  "Khánh Hòa",
  "Kiên Giang",
  "Kon Tum",
  "Lai Châu",
  "Lâm Đồng",
  "Lạng Sơn",
  "Lào Cai",
  "Long An",
  "Nam Định",
  "Nghệ An",
  "Ninh Bình",
  "Ninh Thuận",
  "Phú Thọ",
  "Phú Yên",
  "Quảng Bình",
  "Quảng Nam",
  "Quảng Ngãi",
  "Quảng Ninh",
  "Quảng Trị",
  "Sóc Trăng",
  "Sơn La",
  "Tây Ninh",
  "Thái Bình",
  "Thái Nguyên",
  "Thanh Hóa",
  "Tiền Giang",
  "Trà Vinh",
  "Tuyên Quang",
  "Vĩnh Long",
  "Vĩnh Phúc",
  "Yên Bái",
];

export function CheckoutClient() {
  const router = useRouter();
  const [, startTransition] = useTransition();
  const { user, isAuthenticated, isLoading: authLoading } = useAuth();
  const { items, isHydrated, clearCart } = useCart();

  // Form State
  const [customerName, setCustomerName] = useState("");
  const [customerPhone, setCustomerPhone] = useState("");
  const [customerEmail, setCustomerEmail] = useState("");
  const [shippingProvince, setShippingProvince] = useState("Hà Nội");
  const [shippingAddress, setShippingAddress] = useState("");
  const [notes, setNotes] = useState("");
  const [paymentMethod, setPaymentMethod] = useState<"cod" | "sepay">("cod");

  // Status & Validation State
  const [previewData, setPreviewData] = useState<CheckoutPreviewData | null>(null);
  const [isPreviewLoading, setIsPreviewLoading] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [generalError, setGeneralError] = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});

  // Idempotency key per checkout attempt
  const idempotencyKeyRef = useRef<string | null>(null);

  useEffect(() => {
    if (!idempotencyKeyRef.current) {
      idempotencyKeyRef.current = `idem_${Date.now()}_${Math.random().toString(36).substring(2, 10)}`;
    }
  }, []);

  // Redirect unauthenticated users to login
  useEffect(() => {
    if (!authLoading && !isAuthenticated) {
      router.replace("/dang-nhap?redirect=/thanh-toan");
    }
  }, [authLoading, isAuthenticated, router]);

  // Autofill from user profile once authenticated
  useEffect(() => {
    if (user) {
      Promise.resolve().then(() => {
        setCustomerName((prev) => prev || user.name || "");
        setCustomerEmail((prev) => prev || user.email || "");
      });
    }
  }, [user]);

  // Fetch checkout preview with server pricing and stock validation
  const previewReqIdRef = useRef<number>(0);
  useEffect(() => {
    if (!isHydrated || items.length === 0 || !isAuthenticated) {
      return;
    }

    const reqId = ++previewReqIdRef.current;
    Promise.resolve().then(() => {
      if (reqId === previewReqIdRef.current) {
        setIsPreviewLoading(true);
        setGeneralError(null);
      }
    });

    previewCheckout(items)
      .then((data) => {
        if (reqId === previewReqIdRef.current) {
          setPreviewData(data);
          setIsPreviewLoading(false);

          // If SePay is not configured or unavailable, ensure payment method falls back to COD
          const sepayOpt = data.available_payment_methods?.find((m) => m.code === "sepay");
          if (sepayOpt && sepayOpt.is_available === false) {
            setPaymentMethod("cod");
          }
        }
      })
      .catch((err) => {
        if (reqId === previewReqIdRef.current) {
          console.error("Preview checkout failed:", err);
          setGeneralError(
            "Không thể tính toán chi phí đơn hàng vào lúc này. Xin quý đạo hữu vui lòng thử lại sau."
          );
          setIsPreviewLoading(false);
        }
      });
  }, [items, isHydrated, isAuthenticated]);

  // Local Form Validation
  const validateForm = (): boolean => {
    const errors: Record<string, string> = {};

    if (!customerName.trim()) {
      errors.customer_name = "Vui lòng nhập họ tên người nhận.";
    }

    const phoneTrimmed = customerPhone.trim();
    if (!phoneTrimmed) {
      errors.customer_phone = "Vui lòng nhập số điện thoại nhận hàng.";
    } else if (!/^[0-9+\s\-()]{9,15}$/.test(phoneTrimmed)) {
      errors.customer_phone = "Số điện thoại không hợp lệ (từ 9 đến 15 ký tự số).";
    }

    if (customerEmail && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(customerEmail.trim())) {
      errors.customer_email = "Địa chỉ email không đúng định dạng.";
    }

    if (!shippingProvince.trim()) {
      errors.shipping_province = "Vui lòng chọn tỉnh/thành phố nhận hàng.";
    }

    if (!shippingAddress.trim()) {
      errors.shipping_address = "Vui lòng nhập địa chỉ cụ thể (số nhà, đường, phường/xã).";
    }

    setFieldErrors(errors);
    return Object.keys(errors).length === 0;
  };

  // Submit Order
  const handlePlaceOrder = async (e: React.FormEvent) => {
    e.preventDefault();
    setGeneralError(null);

    if (!validateForm()) {
      return;
    }

    if (!previewData?.is_valid) {
      setGeneralError(
        "Một số vật phẩm trong đơn hàng hiện không đủ số lượng hoặc tạm ngừng phát hành. Vui lòng kiểm tra lại giỏ hàng."
      );
      return;
    }

    setIsSubmitting(true);

    if (!idempotencyKeyRef.current) {
      idempotencyKeyRef.current = `idem_${Date.now()}_${Math.random().toString(36).substring(2, 10)}`;
    }

    const payload: CreateOrderPayload = {
      customer_name: customerName.trim(),
      customer_phone: customerPhone.trim(),
      customer_email: customerEmail.trim() || undefined,
      shipping_province: shippingProvince.trim(),
      shipping_address: shippingAddress.trim(),
      notes: notes.trim() || undefined,
      payment_method: paymentMethod,
      idempotency_key: idempotencyKeyRef.current,
      items: items.map((it) => ({
        product_id: it.product_id,
        quantity: it.quantity,
      })),
    };

    try {
      const res = await createOrder(payload);

      // Successfully created: clear cart
      clearCart();

      // If SePay payment method, submit form POST to SePay Sandbox endpoint
      if (paymentMethod === "sepay" && res.payment_data) {
        const form = document.createElement("form");
        form.method = "POST";
        form.action = res.payment_data.checkout_url;
        form.style.display = "none";

        Object.entries(res.payment_data.fields).forEach(([key, val]) => {
          const input = document.createElement("input");
          input.type = "hidden";
          input.name = key;
          input.value = String(val);
          form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
        return;
      }

      startTransition(() => {
        router.push(`/don-hang/${res.data.public_id}`);
      });
    } catch (err) {
      console.error("Order creation failed:", err);
      setIsSubmitting(false);

      if (err instanceof ApiError) {
        if (err.errors) {
          const apiErrors: Record<string, string> = {};
          Object.entries(err.errors).forEach(([field, messages]) => {
            if (Array.isArray(messages) && messages.length > 0) {
              apiErrors[field] = messages[0];
            }
          });
          setFieldErrors((prev) => ({ ...prev, ...apiErrors }));
        }
        setGeneralError(err.message || "Không thể tạo đơn hàng. Xin vui lòng thử lại.");
      } else {
        setGeneralError("Đã xảy ra lỗi kết nối. Xin quý đạo hữu vui lòng thử lại sau ít phút.");
      }
    }
  };

  // 1. Loading screen while authenticating or hydrating cart
  if (authLoading || !isHydrated) {
    return (
      <div className="max-w-4xl mx-auto py-16 px-4 space-y-6">
        <div className="h-8 bg-surface-container-high rounded-xl w-64 animate-pulse" />
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
          <div className="lg:col-span-7 h-96 bg-surface-container-low rounded-3xl animate-pulse" />
          <div className="lg:col-span-5 h-72 bg-surface-container-low rounded-3xl animate-pulse" />
        </div>
      </div>
    );
  }

  // 2. Guest fallback before redirect
  if (!isAuthenticated) {
    return (
      <div className="max-w-xl mx-auto py-20 px-4 text-center space-y-4">
        <span className="material-symbols-outlined text-4xl text-primary animate-spin">
          progress_activity
        </span>
        <h2 className="font-serif text-xl font-bold text-on-surface">
          Đang chuyển hướng đến trang đăng nhập...
        </h2>
        <p className="text-sm text-on-surface-variant">
          Quý đạo hữu cần đăng nhập để quản lý đơn hàng và nhận thông báo giao vật phẩm tu học.
        </p>
      </div>
    );
  }

  // 3. Empty cart state
  if (items.length === 0) {
    return (
      <div className="max-w-xl mx-auto py-16 px-4 text-center space-y-6">
        <div className="w-20 h-20 mx-auto rounded-full bg-surface-container-high flex items-center justify-center text-primary/70">
          <span className="material-symbols-outlined text-4xl">shopping_cart</span>
        </div>
        <div className="space-y-2">
          <h1 className="font-serif text-2xl font-bold text-on-surface">
            Giỏ Hàng Đang Trống
          </h1>
          <p className="text-sm text-on-surface-variant max-w-md mx-auto leading-relaxed">
            Quý đạo hữu chưa chọn vật phẩm tu học nào. Hãy ghé thăm Cửa Hàng để thỉnh pháp khí, kinh sách hoặc tràng hạt trợ duyên.
          </p>
        </div>
        <div>
          <Link
            href="/cua-hang"
            className="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-primary text-on-primary font-medium text-sm shadow-sm hover:bg-primary/90 transition-all"
          >
            <span className="material-symbols-outlined text-base">storefront</span>
            <span>Ghé Thăm Cửa Hàng Vật Phẩm</span>
          </Link>
        </div>
      </div>
    );
  }

  const subtotal = previewData?.subtotal_vnd ?? 0;
  const freeShippingThreshold = 500000;
  const remainingForFreeShipping = Math.max(0, freeShippingThreshold - subtotal);

  return (
    <div className="space-y-8">
      {/* Page Title & Breadcrumb */}
      <div className="space-y-2">
        <div className="flex items-center gap-2 text-xs text-on-surface-variant">
          <Link href="/cua-hang" className="hover:text-primary transition-colors">
            Cửa Hàng
          </Link>
          <span>/</span>
          <Link href="/gio-hang" className="hover:text-primary transition-colors">
            Giỏ Hàng
          </Link>
          <span>/</span>
          <span className="text-on-surface font-medium">Đặt Hàng & Thanh Toán</span>
        </div>
        <h1 className="font-serif text-2xl sm:text-3xl font-bold text-on-surface">
          Đặt Hàng & Thanh Toán
        </h1>
        <p className="text-xs sm:text-sm text-on-surface-variant">
          Kiểm tra thông tin giao nhận và xác nhận thỉnh vật phẩm tu học trong chánh niệm.
        </p>
      </div>

      {/* Global Error Notice */}
      {generalError && (
        <div className="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/30 border border-rose-300 dark:border-rose-800 text-sm text-rose-800 dark:text-rose-200 flex items-start gap-3">
          <span className="material-symbols-outlined text-rose-600 text-xl shrink-0">
            error
          </span>
          <div className="flex-1">
            <p className="font-semibold">Lưu ý khi tạo đơn hàng</p>
            <p className="text-xs sm:text-sm mt-0.5 leading-relaxed">{generalError}</p>
          </div>
        </div>
      )}

      {/* Main Checkout Grid */}
      <form onSubmit={handlePlaceOrder} className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        {/* Left Column: Shipping Form & Payment Selection */}
        <div className="lg:col-span-7 space-y-6">
          {/* Section 1: Thông tin người nhận */}
          <div className="p-6 rounded-3xl bg-surface-container-low/70 border border-outline-variant/40 space-y-5 shadow-xs">
            <div className="flex items-center gap-2 border-b border-outline-variant/30 pb-3">
              <span className="material-symbols-outlined text-primary text-xl">
                local_shipping
              </span>
              <h2 className="font-serif text-lg font-bold text-on-surface">
                1. Thông Tin Giao Nhận Vật Phẩm
              </h2>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              {/* Họ tên */}
              <div className="sm:col-span-2 space-y-1.5">
                <label className="block text-xs font-semibold text-on-surface">
                  Họ và tên người nhận <span className="text-rose-600">*</span>
                </label>
                <input
                  type="text"
                  value={customerName}
                  onChange={(e) => {
                    setCustomerName(e.target.value);
                    if (fieldErrors.customer_name) {
                      setFieldErrors((prev) => ({ ...prev, customer_name: "" }));
                    }
                  }}
                  placeholder="Ví dụ: Nguyễn Văn An (Pháp danh: Diệu Thiện)"
                  className={`w-full px-4 py-2.5 rounded-xl border bg-surface text-sm text-on-surface focus:outline-hidden focus:ring-2 focus:ring-primary/40 transition-all ${
                    fieldErrors.customer_name
                      ? "border-rose-500 focus:border-rose-500"
                      : "border-outline-variant/60 focus:border-primary"
                  }`}
                  disabled={isSubmitting}
                />
                {fieldErrors.customer_name && (
                  <p className="text-xs text-rose-600">{fieldErrors.customer_name}</p>
                )}
              </div>

              {/* Số điện thoại */}
              <div className="space-y-1.5">
                <label className="block text-xs font-semibold text-on-surface">
                  Số điện thoại <span className="text-rose-600">*</span>
                </label>
                <input
                  type="tel"
                  value={customerPhone}
                  onChange={(e) => {
                    setCustomerPhone(e.target.value);
                    if (fieldErrors.customer_phone) {
                      setFieldErrors((prev) => ({ ...prev, customer_phone: "" }));
                    }
                  }}
                  placeholder="Ví dụ: 0912345678"
                  className={`w-full px-4 py-2.5 rounded-xl border bg-surface text-sm text-on-surface focus:outline-hidden focus:ring-2 focus:ring-primary/40 transition-all ${
                    fieldErrors.customer_phone
                      ? "border-rose-500 focus:border-rose-500"
                      : "border-outline-variant/60 focus:border-primary"
                  }`}
                  disabled={isSubmitting}
                />
                {fieldErrors.customer_phone && (
                  <p className="text-xs text-rose-600">{fieldErrors.customer_phone}</p>
                )}
              </div>

              {/* Email */}
              <div className="space-y-1.5">
                <label className="block text-xs font-semibold text-on-surface">
                  Địa chỉ email (nhận thông báo)
                </label>
                <input
                  type="email"
                  value={customerEmail}
                  onChange={(e) => {
                    setCustomerEmail(e.target.value);
                    if (fieldErrors.customer_email) {
                      setFieldErrors((prev) => ({ ...prev, customer_email: "" }));
                    }
                  }}
                  placeholder="name@example.com"
                  className={`w-full px-4 py-2.5 rounded-xl border bg-surface text-sm text-on-surface focus:outline-hidden focus:ring-2 focus:ring-primary/40 transition-all ${
                    fieldErrors.customer_email
                      ? "border-rose-500 focus:border-rose-500"
                      : "border-outline-variant/60 focus:border-primary"
                  }`}
                  disabled={isSubmitting}
                />
                {fieldErrors.customer_email && (
                  <p className="text-xs text-rose-600">{fieldErrors.customer_email}</p>
                )}
              </div>

              {/* Tỉnh / Thành phố */}
              <div className="sm:col-span-2 space-y-1.5">
                <label className="block text-xs font-semibold text-on-surface">
                  Tỉnh / Thành phố <span className="text-rose-600">*</span>
                </label>
                <select
                  value={shippingProvince}
                  onChange={(e) => {
                    setShippingProvince(e.target.value);
                    if (fieldErrors.shipping_province) {
                      setFieldErrors((prev) => ({ ...prev, shipping_province: "" }));
                    }
                  }}
                  className={`w-full px-4 py-2.5 rounded-xl border bg-surface text-sm text-on-surface focus:outline-hidden focus:ring-2 focus:ring-primary/40 transition-all ${
                    fieldErrors.shipping_province
                      ? "border-rose-500 focus:border-rose-500"
                      : "border-outline-variant/60 focus:border-primary"
                  }`}
                  disabled={isSubmitting}
                >
                  {VIETNAM_PROVINCES.map((prov) => (
                    <option key={prov} value={prov}>
                      {prov}
                    </option>
                  ))}
                </select>
                {fieldErrors.shipping_province && (
                  <p className="text-xs text-rose-600">{fieldErrors.shipping_province}</p>
                )}
              </div>

              {/* Địa chỉ chi tiết */}
              <div className="sm:col-span-2 space-y-1.5">
                <label className="block text-xs font-semibold text-on-surface">
                  Địa chỉ chi tiết (Số nhà, ngõ/đường, phường/xã, quận/huyện){" "}
                  <span className="text-rose-600">*</span>
                </label>
                <textarea
                  rows={2}
                  value={shippingAddress}
                  onChange={(e) => {
                    setShippingAddress(e.target.value);
                    if (fieldErrors.shipping_address) {
                      setFieldErrors((prev) => ({ ...prev, shipping_address: "" }));
                    }
                  }}
                  placeholder="Ví dụ: Số 24 ngõ 12 Đội Cấn, Phường Kim Mã, Quận Ba Đình"
                  className={`w-full px-4 py-2.5 rounded-xl border bg-surface text-sm text-on-surface focus:outline-hidden focus:ring-2 focus:ring-primary/40 transition-all resize-none ${
                    fieldErrors.shipping_address
                      ? "border-rose-500 focus:border-rose-500"
                      : "border-outline-variant/60 focus:border-primary"
                  }`}
                  disabled={isSubmitting}
                />
                {fieldErrors.shipping_address && (
                  <p className="text-xs text-rose-600">{fieldErrors.shipping_address}</p>
                )}
              </div>

              {/* Ghi chú */}
              <div className="sm:col-span-2 space-y-1.5">
                <label className="block text-xs font-semibold text-on-surface">
                  Ghi chú giao hàng (Tùy chọn)
                </label>
                <input
                  type="text"
                  value={notes}
                  onChange={(e) => setNotes(e.target.value)}
                  placeholder="Ví dụ: Xin giao vào giờ hành chính, gọi trước khi giao 15 phút..."
                  className="w-full px-4 py-2.5 rounded-xl border border-outline-variant/60 bg-surface text-sm text-on-surface focus:outline-hidden focus:ring-2 focus:ring-primary/40 transition-all"
                  disabled={isSubmitting}
                />
              </div>
            </div>
          </div>

          {/* Section 2: Phương thức thanh toán */}
          <div className="p-6 rounded-3xl bg-surface-container-low/70 border border-outline-variant/40 space-y-4 shadow-xs">
            <div className="flex items-center gap-2 border-b border-outline-variant/30 pb-3">
              <span className="material-symbols-outlined text-primary text-xl">
                payments
              </span>
              <h2 className="font-serif text-lg font-bold text-on-surface">
                2. Phương Thức Thanh Toán
              </h2>
            </div>

            <div className="space-y-3">
              {/* COD Option */}
              <label
                htmlFor="payment-cod"
                className={`flex items-start gap-3.5 p-4 rounded-2xl border-2 cursor-pointer transition-all ${
                  paymentMethod === "cod"
                    ? "border-primary bg-primary/5"
                    : "border-outline-variant/30 hover:border-outline-variant/60 bg-surface"
                }`}
              >
                <input
                  type="radio"
                  id="payment-cod"
                  name="payment_method"
                  value="cod"
                  checked={paymentMethod === "cod"}
                  onChange={() => setPaymentMethod("cod")}
                  className="mt-1 text-primary focus:ring-primary cursor-pointer"
                />
                <div className="space-y-1 flex-1">
                  <div className="flex items-center justify-between">
                    <span className="font-semibold text-sm text-on-surface">
                      Thanh toán khi nhận hàng (COD)
                    </span>
                    <span className="text-xs px-2 py-0.5 rounded-full bg-primary/10 text-primary font-medium">
                      Phổ biến
                    </span>
                  </div>
                  <p className="text-xs text-on-surface-variant leading-relaxed">
                    Quý đạo hữu chỉ thanh toán tiền mặt cho nhân viên giao hàng sau khi đã nhận và kiểm tra vật phẩm tu học. An toàn, tiện lợi và không phát sinh thêm chi phí trung gian.
                  </p>
                </div>
              </label>

              {/* SePay VietQR Option */}
              {(() => {
                const sepayOpt = previewData?.available_payment_methods?.find((m) => m.code === "sepay");
                const isSepayActive = sepayOpt ? sepayOpt.is_available !== false : true;

                return (
                  <label
                    htmlFor="payment-sepay"
                    className={`flex items-start gap-3.5 p-4 rounded-2xl border-2 transition-all ${
                      !isSepayActive
                        ? "opacity-60 bg-surface-container/50 border-outline-variant/20 cursor-not-allowed"
                        : paymentMethod === "sepay"
                        ? "border-primary bg-primary/5 cursor-pointer"
                        : "border-outline-variant/30 hover:border-outline-variant/60 bg-surface cursor-pointer"
                    }`}
                  >
                    <input
                      type="radio"
                      id="payment-sepay"
                      name="payment_method"
                      value="sepay"
                      disabled={!isSepayActive}
                      checked={paymentMethod === "sepay"}
                      onChange={() => {
                        if (isSepayActive) {
                          setPaymentMethod("sepay");
                        }
                      }}
                      className="mt-1 text-primary focus:ring-primary cursor-pointer disabled:cursor-not-allowed"
                    />
                    <div className="space-y-1 flex-1">
                      <div className="flex items-center justify-between">
                        <span className="font-semibold text-sm text-on-surface">
                          Chuyển khoản VietQR (Cổng SePay)
                        </span>
                        {isSepayActive ? (
                          <span className="text-xs px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300 font-medium">
                            Tự động 24/7
                          </span>
                        ) : (
                          <span className="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300 font-medium">
                            Tạm chưa khả dụng
                          </span>
                        )}
                      </div>
                      <p className="text-xs text-on-surface-variant leading-relaxed">
                        {isSepayActive
                          ? "Quét mã QR chuyển khoản nhanh qua mọi ứng dụng ngân hàng và ví điện tử. Hệ thống tự động xác nhận giao dịch tức thì, bảo mật và chính xác 100%."
                          : (sepayOpt?.description || "Cổng thanh toán SePay đang trong quá trình bảo trì/cấu hình. Quý đạo hữu vui lòng chọn phương thức COD.")}
                      </p>
                    </div>
                  </label>
                );
              })()}
            </div>
          </div>
        </div>

        {/* Right Column: Order Summary & Confirm Button (Sticky) */}
        <div className="lg:col-span-5 space-y-5 sticky top-24">
          <div className="p-6 rounded-3xl bg-surface-container-low/70 border border-outline-variant/40 space-y-5 shadow-xs">
            <div className="flex items-center justify-between border-b border-outline-variant/30 pb-3">
              <h2 className="font-serif text-lg font-bold text-on-surface">
                Tóm Tắt Đơn Hàng
              </h2>
              <span className="text-xs font-mono font-medium text-on-surface-variant">
                {previewData?.total_quantity || items.reduce((s, it) => s + it.quantity, 0)} món
              </span>
            </div>

            {/* Items Mini List */}
            <div className="space-y-3 max-h-64 overflow-y-auto pr-1">
              {previewData?.items?.map((item) => (
                <div
                  key={item.product_id}
                  className="flex items-center gap-3 py-2 border-b border-outline-variant/20 last:border-b-0"
                >
                  <div className="relative w-12 h-12 rounded-xl bg-surface-container overflow-hidden shrink-0 border border-outline-variant/40">
                    {item.cover_image ? (
                      <Image
                        src={item.cover_image}
                        alt={item.name}
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
                  <div className="flex-1 min-w-0">
                    <p className="text-xs font-semibold text-on-surface truncate">
                      {item.name}
                    </p>
                    <p className="text-[11px] text-on-surface-variant">
                      {item.formatted_unit_price} x {item.quantity}
                    </p>
                  </div>
                  <div className="text-right shrink-0">
                    <p className="font-mono text-xs font-semibold text-on-surface">
                      {item.formatted_line_subtotal}
                    </p>
                  </div>
                </div>
              ))}
            </div>

            {/* Free Shipping Banner */}
            <div className="p-3 rounded-2xl bg-amber-50/80 dark:bg-amber-950/30 border border-amber-200/60 dark:border-amber-800/40 text-xs text-amber-900 dark:text-amber-200 leading-relaxed">
              {remainingForFreeShipping > 0 ? (
                <span>
                  🎁 Thỉnh thêm{" "}
                  <strong>{remainingForFreeShipping.toLocaleString("vi-VN")} ₫</strong> để
                  được <strong>Miễn phí vận chuyển</strong> (đơn từ 500.000 ₫).
                </span>
              ) : (
                <span className="font-semibold text-emerald-800 dark:text-emerald-300">
                  🎉 Chúc mừng! Đơn hàng của quý đạo hữu được Miễn phí giao hàng toàn quốc.
                </span>
              )}
            </div>

            {/* Price Calculations */}
            <div className="space-y-2.5 text-xs sm:text-sm pt-2 border-t border-outline-variant/30">
              <div className="flex justify-between text-on-surface-variant">
                <span>Tiền hàng:</span>
                <span className="font-mono font-medium text-on-surface">
                  {previewData?.formatted_subtotal || "..."}
                </span>
              </div>
              <div className="flex justify-between text-on-surface-variant">
                <span>Cước vận chuyển:</span>
                <span className="font-mono font-medium text-on-surface">
                  {previewData?.shipping_fee_vnd === 0
                    ? "Miễn phí"
                    : previewData?.formatted_shipping_fee || "30.000 ₫"}
                </span>
              </div>
              <div className="flex justify-between items-baseline pt-2 border-t border-outline-variant/30">
                <span className="font-semibold text-base text-on-surface">Tổng thanh toán:</span>
                <span className="font-serif font-bold text-2xl text-amber-700 dark:text-amber-400">
                  {previewData?.formatted_total || "..."}
                </span>
              </div>
            </div>

            {/* Submit Button */}
            <div className="pt-2">
              <button
                type="submit"
                disabled={isSubmitting || isPreviewLoading || !previewData?.is_valid}
                className="w-full py-4 px-6 rounded-2xl font-label-lg font-semibold bg-primary hover:bg-primary/90 text-on-primary shadow-sm hover:shadow-md transition-all flex items-center justify-center gap-2 text-center text-sm cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {isSubmitting ? (
                  <>
                    <span className="material-symbols-outlined text-[18px] animate-spin">
                      progress_activity
                    </span>
                    <span>Đang Khởi Tạo Đơn Hàng...</span>
                  </>
                ) : (
                  <>
                    <span className="material-symbols-outlined text-[18px]">
                      {paymentMethod === "sepay" ? "qr_code_2" : "verified"}
                    </span>
                    <span>
                      {paymentMethod === "sepay"
                        ? "Tiến Hành Thanh Toán SePay (VietQR)"
                        : "Xác Nhận Đặt Hàng (COD)"}
                    </span>
                  </>
                )}
              </button>
            </div>

            <div className="text-[11px] text-center text-on-surface-variant/80 leading-relaxed">
              Bằng việc xác nhận đặt hàng, quý đạo hữu đồng ý thỉnh các vật phẩm tu học theo chính sách bảo hành và bảo trợ của OmPraMani.
            </div>
          </div>
        </div>
      </form>
    </div>
  );
}