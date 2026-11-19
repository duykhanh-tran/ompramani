import { apiClient, ApiError } from "./client";
import {
  CartItemStorage,
  CartPreviewData,
  CartPreviewResponse,
  CheckoutPreviewData,
  CheckoutPreviewResponse,
  CreateOrderPayload,
  CreateOrderResponse,
  OrderDetail,
  OrderDetailResponse,
  OrderListResponse,
  PaymentStatusData,
  PaymentStatusResponse,
  ProductCategory,
  ProductCategoryListResponse,
  ProductDetail,
  ProductDetailResponse,
  ProductFilters,
  ProductListResponse,
  SepayPaymentData,
} from "@/types/shop";

export async function getProductCategories(): Promise<ProductCategory[]> {
  try {
    const res = await apiClient<ProductCategoryListResponse>(
      "/api/v1/shop/categories"
    );
    return res.data || [];
  } catch (err) {
    console.error("Failed to fetch shop categories:", err);
    return [];
  }
}

export async function getProducts(
  filters?: ProductFilters
): Promise<ProductListResponse> {
  const query = new URLSearchParams();

  if (filters) {
    if (filters.page && filters.page > 1) {
      query.set("page", filters.page.toString());
    }
    if (filters.per_page) {
      query.set("per_page", filters.per_page.toString());
    }
    if (filters.q && filters.q.trim()) {
      query.set("q", filters.q.trim());
    }
    if (filters.category && filters.category !== "all") {
      query.set("category", filters.category);
    }
    if (filters.sort && filters.sort !== "latest") {
      query.set("sort", filters.sort);
    }
  }

  const queryString = query.toString();
  const endpoint = `/api/v1/shop/products${queryString ? `?${queryString}` : ""}`;

  return apiClient<ProductListResponse>(endpoint);
}

export async function getProductBySlug(slug: string): Promise<ProductDetail | null> {
  try {
    const res = await apiClient<ProductDetailResponse>(
      `/api/v1/shop/products/${encodeURIComponent(slug)}`
    );
    return res.data || null;
  } catch (err) {
    if (err instanceof ApiError && err.status === 404) {
      return null;
    }
    throw err;
  }
}

export async function previewCart(
  items: CartItemStorage[]
): Promise<CartPreviewData> {
  const res = await apiClient<CartPreviewResponse>(
    "/api/v1/shop/cart/preview",
    {
      method: "POST",
      body: JSON.stringify({ items }),
    }
  );

  return res.data;
}

export async function previewCheckout(
  items: CartItemStorage[]
): Promise<CheckoutPreviewData> {
  const res = await apiClient<CheckoutPreviewResponse>(
    "/api/v1/shop/checkout/preview",
    {
      method: "POST",
      body: JSON.stringify({ items }),
    }
  );

  return res.data;
}

export async function createOrder(
  payload: CreateOrderPayload
): Promise<CreateOrderResponse> {
  const res = await apiClient<CreateOrderResponse>(
    "/api/v1/shop/orders",
    {
      method: "POST",
      body: JSON.stringify(payload),
    }
  );

  return res;
}

export async function getOrderConfirmation(
  publicId: string
): Promise<OrderDetail | null> {
  try {
    const res = await apiClient<OrderDetailResponse>(
      `/api/v1/shop/me/orders/${encodeURIComponent(publicId)}`
    );
    return res.data || null;
  } catch (err) {
    if (err instanceof ApiError && err.status === 404) {
      return null;
    }
    throw err;
  }
}

export async function getMyOrders(
  page: number = 1,
  perPage: number = 10,
  status?: string
): Promise<OrderListResponse> {
  const query = new URLSearchParams({
    page: String(page),
    per_page: String(perPage),
  });

  if (status && status !== "all") {
    query.set("status", status);
  }

  const res = await apiClient<OrderListResponse>(
    `/api/v1/shop/me/orders?${query.toString()}`
  );
  return res;
}

export async function cancelMyOrder(
  publicId: string,
  reason?: string
): Promise<OrderDetail> {
  const res = await apiClient<OrderDetailResponse>(
    `/api/v1/shop/me/orders/${encodeURIComponent(publicId)}/cancel`,
    {
      method: "POST",
      body: JSON.stringify({ reason }),
    }
  );
  return res.data;
}

export async function switchOrderToCod(
  publicId: string
): Promise<{ message: string; order: OrderDetail }> {
  return apiClient<{ message: string; order: OrderDetail }>(
    `/api/v1/shop/orders/${encodeURIComponent(publicId)}/switch-to-cod`,
    {
      method: "POST",
    }
  );
}

export async function getPaymentStatus(
  publicId: string
): Promise<PaymentStatusData> {
  const res = await apiClient<PaymentStatusResponse>(
    `/api/v1/shop/orders/${encodeURIComponent(publicId)}/payment-status`
  );
  return res.data;
}

export async function retrySepayPayment(
  publicId: string
): Promise<{ message: string; payment_data: SepayPaymentData }> {
  return apiClient<{ message: string; payment_data: SepayPaymentData }>(
    `/api/v1/shop/orders/${encodeURIComponent(publicId)}/sepay-retry`,
    {
      method: "POST",
    }
  );
}
