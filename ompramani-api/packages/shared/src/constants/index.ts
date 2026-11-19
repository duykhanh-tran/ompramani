export const USER_ROLES = {
  ADMIN: 'admin',
  WAREHOUSE: 'warehouse',
  SALE: 'sale',
} as const;

export type UserRole = (typeof USER_ROLES)[keyof typeof USER_ROLES];

export const ROLE_LABELS: Record<UserRole, string> = {
  admin: 'Quản trị viên (Admin)',
  warehouse: 'Quản lý kho (Kho)',
  sale: 'Kinh doanh (Sale)',
};

export const PERMISSIONS = {
  // Products
  PRODUCTS_READ: 'products:read',
  PRODUCTS_CREATE: 'products:create',
  PRODUCTS_UPDATE: 'products:update',
  PRODUCTS_DELETE: 'products:delete',
  
  // Brands
  BRANDS_READ: 'brands:read',
  BRANDS_MANAGE: 'brands:manage',

  // Categories
  CATEGORIES_READ: 'categories:read',
  CATEGORIES_MANAGE: 'categories:manage',

  // Inventory (Sprint 02)
  INVENTORY_READ: 'inventory:read',
  INVENTORY_MANAGE: 'inventory:manage',

  // Price lists (Sprint 02)
  PRICES_READ: 'prices:read',
  PRICES_MANAGE: 'prices:manage',

  // Quotes & Orders (Sprint 03)
  QUOTES_READ: 'quotes:read',
  QUOTES_CREATE: 'quotes:create',
  ORDERS_READ: 'orders:read',
  ORDERS_MANAGE: 'orders:manage',
} as const;

export type PermissionCode = (typeof PERMISSIONS)[keyof typeof PERMISSIONS];

export const INVOICE_STATUS = {
  FULL_VAT: 'full_vat',           // Có hóa đơn VAT 10%
  DIRECT_VAT: 'direct_vat',       // Hóa đơn trực tiếp
  PENDING_VAT: 'pending_vat',     // Chờ xuất hóa đơn
  NO_VAT: 'no_vat',               // Không có hóa đơn
} as const;

export type InvoiceStatus = (typeof INVOICE_STATUS)[keyof typeof INVOICE_STATUS];

export const CO_CQ_STATUS = {
  CERTIFIED: 'certified',         // Đầy đủ CO/CQ hãng
  COMMERCIAL: 'commercial',       // CO thương mại / bản sao
  PENDING: 'pending',             // Đang chờ cấp từ hãng
  NONE: 'none',                   // Không có CO/CQ
} as const;

export type CoCqStatus = (typeof CO_CQ_STATUS)[keyof typeof CO_CQ_STATUS];
