export type InventoryMode = 'not_tracked' | 'tracked';

export interface ProductCategory {
  id: number;
  name: string;
  slug: string;
  description?: string | null;
  sort_order: number;
  products_count?: number;
}

export interface ProductCategoryListResponse {
  data: ProductCategory[];
}

export interface ProductSummary {
  public_id: string;
  name: string;
  slug: string;
  category?: {
    id: number;
    name: string;
    slug: string;
  };
  short_description?: string | null;
  price_vnd?: number | null;
  formatted_price?: string | null;
  cover_image?: string | null;
  inventory_mode: InventoryMode;
  is_available: boolean;
  published_at?: string | null;
}

export interface ProductDetail extends ProductSummary {
  description?: string | null;
  sku?: string | null;
  stock_quantity?: number | null;
  gallery_images?: string[];
}

export type ProductSortOption = 'latest' | 'price_asc' | 'price_desc';

export interface ProductFilters {
  category?: string;
  q?: string;
  sort?: ProductSortOption;
  page?: number;
  per_page?: number;
}

export interface ProductPaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from?: number | null;
  to?: number | null;
}

export interface ProductPaginationLinks {
  first?: string;
  last?: string;
  prev?: string | null;
  next?: string | null;
}

export interface ProductListResponse {
  data: ProductSummary[];
  meta: ProductPaginationMeta;
  links: ProductPaginationLinks;
}

export interface ProductDetailResponse {
  data: ProductDetail;
}

// Shopping Cart Types (Task 44)
export interface CartItemStorage {
  product_id: string;
  quantity: number;
}

export interface CartStoragePayload {
  version: number;
  items: CartItemStorage[];
}

export interface CartPreviewItem {
  product_id: string;
  slug: string | null;
  name: string;
  cover_image: string | null;
  unit_price_vnd: number;
  formatted_unit_price: string;
  quantity: number;
  line_subtotal_vnd: number;
  formatted_line_subtotal: string;
  is_available: boolean;
  error: string | null;
  error_message: string | null;
  inventory_mode: InventoryMode;
  available_stock: number | null;
}

export interface CartPreviewData {
  items: CartPreviewItem[];
  subtotal_vnd: number;
  formatted_subtotal: string;
  total_quantity: number;
  is_valid: boolean;
}

export interface CartPreviewResponse {
  data: CartPreviewData;
}

// Checkout & Order Types (Task 45)
export interface PaymentMethodOption {
  code: string;
  name: string;
  description?: string;
  is_available?: boolean;
}

export interface CheckoutPreviewData {
  items: CartPreviewItem[];
  subtotal_vnd: number;
  formatted_subtotal: string;
  shipping_fee_vnd: number;
  formatted_shipping_fee: string;
  total_vnd: number;
  formatted_total: string;
  total_quantity: number;
  is_valid: boolean;
  available_payment_methods: PaymentMethodOption[];
}

export interface CheckoutPreviewResponse {
  data: CheckoutPreviewData;
}

export type PaymentMethodType = 'cod' | 'sepay' | 'vnpay';

export interface SepayPaymentData {
  checkout_url: string;
  fields: Record<string, string | number>;
  invoice_number: string;
}

export interface CreateOrderPayload {
  customer_name: string;
  customer_phone: string;
  customer_email?: string;
  shipping_address: string;
  shipping_province: string;
  notes?: string;
  payment_method: PaymentMethodType;
  idempotency_key?: string;
  items: CartItemStorage[];
}

export interface OrderPaymentSummary {
  transaction_ref?: string | null;
  bank_code?: string | null;
  vnp_txn_ref?: string | null;
  attempt_number: number;
  amount_vnd: number;
  formatted_amount: string;
  payment_status: string;
  payment_status_label: string;
  vnp_bank_code?: string | null;
  vnp_transaction_no?: string | null;
  paid_at?: string | null;
  created_at: string;
}

export interface OrderItemDetail {
  id: number;
  product_public_id: string;
  product_name: string;
  product_sku?: string | null;
  product_image?: string | null;
  unit_price_vnd: number;
  formatted_unit_price: string;
  quantity: number;
  line_total_vnd: number;
  formatted_line_total: string;
}

export interface StatusTimelineStep {
  key: string;
  label: string;
  timestamp: string | null;
  is_current: boolean;
  is_passed: boolean;
  reason?: string | null;
}

export interface OrderDetail {
  public_id: string;
  order_number: string;
  customer_name: string;
  customer_phone: string;
  customer_email?: string | null;
  shipping_address: string;
  shipping_province: string;
  shipping_fee_vnd: number;
  formatted_shipping_fee: string;
  subtotal_vnd: number;
  formatted_subtotal: string;
  total_vnd: number;
  formatted_total: string;
  currency: string;
  order_status: string;
  order_status_label: string;
  payment_method: string;
  payment_method_label: string;
  payment_status: string;
  payment_status_label: string;
  notes?: string | null;
  placed_at: string;
  cancelled_at?: string | null;
  cancellation_reason?: string | null;
  completed_at?: string | null;
  paid_at?: string | null;
  can_cancel: boolean;
  can_retry_payment?: boolean;
  can_switch_to_cod?: boolean;
  latest_payment?: OrderPaymentSummary | null;
  status_timeline: StatusTimelineStep[];
  items: OrderItemDetail[];
}

export interface CreateOrderResponse {
  data: OrderDetail;
  message?: string;
  payment_data?: SepayPaymentData | null;
  payment_url?: string | null;
}

export interface PaymentStatusData {
  public_id: string;
  order_number: string;
  order_status: string;
  order_status_label: string;
  payment_method: string;
  payment_method_label: string;
  payment_status: 'unpaid' | 'pending' | 'paid' | 'failed' | 'cancelled' | 'expired' | 'needs_reconciliation';
  payment_status_label: string;
  total_vnd: number;
  formatted_total: string;
  paid_at?: string | null;
  can_switch_to_cod: boolean;
  can_retry_payment: boolean;
  latest_payment?: {
    attempt_number: number;
    invoice_number: string;
    status: string;
    status_label: string;
    created_at?: string | null;
    paid_at?: string | null;
  } | null;
}

export interface PaymentStatusResponse {
  data: PaymentStatusData;
}

export interface OrderDetailResponse {
  data: OrderDetail;
}

// Order Management & History (Task 46)
export interface OrderSummary {
  public_id: string;
  order_number: string;
  placed_at: string;
  order_status: string;
  order_status_label: string;
  payment_method: string;
  payment_method_label: string;
  payment_status: string;
  payment_status_label: string;
  total_vnd: number;
  formatted_total: string;
  item_count: number;
  first_item_name?: string | null;
  first_item_thumbnail?: string | null;
  can_cancel: boolean;
}

export interface OrderPaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface OrderListResponse {
  data: OrderSummary[];
  meta: OrderPaginationMeta;
}
