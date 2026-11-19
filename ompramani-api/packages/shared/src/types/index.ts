import type { UserRole, PermissionCode } from '../constants/index.js';

export interface Brand {
  id: string;
  name: string;
  code: string;
  slug: string;
  logoUrl?: string | null;
  description?: string | null;
  isActive: boolean;
  productCount?: number;
  createdAt: string;
  updatedAt: string;
}

export interface Category {
  id: string;
  name: string;
  slug: string;
  parentId?: string | null;
  description?: string | null;
  isActive: boolean;
  createdAt: string;
  updatedAt: string;
}

export interface Product {
  id: string;
  brandId: string;
  categoryId?: string | null;
  sku: string;
  name: string;
  description?: string | null;
  unit: string;
  warrantyMonths: number;
  specs: Record<string, any>;
  isActive: boolean;
  createdAt: string;
  updatedAt: string;
  brand?: Brand;
  category?: Category | null;
}

export interface Role {
  id: string;
  name: string;
  code: UserRole;
  description?: string | null;
}

export interface Permission {
  id: string;
  name: string;
  code: PermissionCode;
  module: string;
  description?: string | null;
}

export interface UserProfile {
  id: string;
  email: string;
  fullName: string;
  phone?: string | null;
  avatarUrl?: string | null;
  roleId: string;
  role: Role;
  permissions: PermissionCode[];
  isActive: boolean;
  createdAt: string;
  updatedAt: string;
}

export interface PaginationMeta {
  page: number;
  limit: number;
  total: number;
  totalPages: number;
}

export interface ApiResponse<T> {
  success: true;
  data: T;
  meta?: PaginationMeta;
}

export interface ApiErrorDetail {
  field?: string;
  message: string;
}

export interface ApiErrorResponse {
  success: false;
  error: {
    code: string;
    message: string;
    details?: ApiErrorDetail[];
  };
}
