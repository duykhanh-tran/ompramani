import { z } from 'zod';

export const loginSchema = z.object({
  email: z.string().email('Email không đúng định dạng'),
  password: z.string().min(6, 'Mật khẩu phải có ít nhất 6 ký tự'),
});

export type LoginInput = z.infer<typeof loginSchema>;

export const createProductSchema = z.object({
  brandId: z.string().uuid('Mã thương hiệu không hợp lệ'),
  categoryId: z.string().uuid('Mã danh mục không hợp lệ').nullable().optional(),
  sku: z
    .string()
    .trim()
    .min(2, 'Part Number / SKU phải có ít nhất 2 ký tự')
    .max(100, 'SKU không được vượt quá 100 ký tự')
    .regex(/^[A-Za-z0-9_.\-#/]+$/, 'SKU chỉ được chứa chữ cái, số và các ký tự đặc biệt _ . - # /'),
  name: z
    .string()
    .trim()
    .min(3, 'Tên thiết bị phải có ít nhất 3 ký tự')
    .max(255, 'Tên thiết bị tối đa 255 ký tự'),
  description: z.string().trim().max(5000).nullable().optional(),
  unit: z.string().trim().min(1, 'Đơn vị tính không được để trống').default('Cái'),
  warrantyMonths: z.coerce.number().int().min(0, 'Thời gian bảo hành không âm').default(12),
  specs: z.record(z.any()).default({}),
  isActive: z.boolean().default(true),
});

export type CreateProductInput = z.infer<typeof createProductSchema>;

export const updateProductSchema = createProductSchema.partial().extend({
  brandId: z.string().uuid('Mã thương hiệu không hợp lệ').optional(),
});

export type UpdateProductInput = z.infer<typeof updateProductSchema>;

export const queryProductSchema = z.object({
  search: z.string().trim().optional(),
  brandId: z.string().uuid().optional(),
  categoryId: z.string().uuid().optional(),
  page: z.coerce.number().int().min(1).default(1),
  limit: z.coerce.number().int().min(1).max(100).default(10),
  sortBy: z.enum(['sku', 'name', 'createdAt']).default('createdAt'),
  sortOrder: z.enum(['asc', 'desc']).default('desc'),
});

export type QueryProductInput = z.infer<typeof queryProductSchema>;

export const createBrandSchema = z.object({
  name: z.string().trim().min(2, 'Tên thương hiệu phải có ít nhất 2 ký tự').max(100),
  code: z.string().trim().min(2).max(50).toUpperCase(),
  slug: z.string().trim().min(2).max(100),
  logoUrl: z.string().url('Đường dẫn logo không hợp lệ').nullable().optional(),
  description: z.string().trim().nullable().optional(),
  isActive: z.boolean().default(true),
});

export type CreateBrandInput = z.infer<typeof createBrandSchema>;
