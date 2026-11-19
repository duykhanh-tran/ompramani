# Kiến Trúc Hệ Thống - Quản Lý Kho & Tra Cứu Giá Thiết Bị IT (Sprint 01)

## 1. Tổng Quan Hệ Thống

Hệ thống là giải pháp web nội bộ phục vụ công ty phân phối thiết bị công nghệ thông tin (IT Hardware Distribution) với danh mục hàng chục nghìn Part Number/SKU từ các thương hiệu hàng đầu: **Cisco, HPE Aruba, Fortinet, Dell, Lenovo...**

### 1.1 Mục Tiêu Nghiệp Vụ
1. **Quản lý danh mục Part Number/SKU chuẩn hóa**: SKU là duy nhất theo từng hãng sản xuất (`brand_id` + `sku`). Một mã có thể có nhiều tên gọi, alias hoặc barcode.
2. **Tách biệt Danh mục SKU và Tồn kho theo Lô (Inventory Lots)**: Cùng một SKU có thể tồn tại ở nhiều lô nhập khác nhau về nhà cung cấp, đơn giá nhập, trạng thái hóa đơn VAT và chứng từ xuất xứ CO/CQ. **Tuyệt đối không dùng màu sắc Excel làm dữ liệu nghiệp vụ**.
3. **Phân quyền chặt chẽ (RBAC)**:
   - **Admin**: Toàn quyền cấu hình hệ thống, quản lý người dùng, hãng, sản phẩm, bảng giá và kho.
   - **Kho (Warehouse)**: Quản lý phiếu nhập/xuất kho, kiểm kê tồn kho theo lô, cập nhật tình trạng hàng hóa và chứng từ CO/CQ.
   - **Sale**: Tra cứu nhanh SKU, thông số, tồn kho khả dụng, chính sách giá bán và lập báo giá/đơn hàng.
4. **Khả năng mở rộng**: Sẵn sàng tích hợp Import Excel dung lượng lớn (hàng chục nghìn dòng), đa bảng giá (bán lẻ, đại lý cấp 1, dự án), quản lý đơn hàng và realtime updates qua Supabase Realtime ở các sprint tiếp theo.

---

## 2. Kiến Trúc Monorepo (pnpm Workspaces)

```
ompramani-api/
├── apps/
│   ├── api/                     # Backend Express.js REST API (TypeScript)
│   │   ├── src/
│   │   │   ├── config/          # Cấu hình biến môi trường, Supabase, DB
│   │   │   ├── controllers/     # HTTP Request/Response Controllers
│   │   │   ├── services/        # Nghiệp vụ lõi (Business Logic Layer)
│   │   │   ├── repositories/    # Truy vấn dữ liệu qua Drizzle ORM (Data Access Layer)
│   │   │   ├── middlewares/     # Auth, RBAC, Error Handling, Logging, Validation
│   │   │   ├── routes/          # Định nghĩa API Routes
│   │   │   ├── db/              # Drizzle Schema, Migrations, Seed Data
│   │   │   └── app.ts           # Khởi tạo Express Application
│   │   ├── tests/               # Unit & Integration Tests (Vitest / Supertest)
│   │   ├── drizzle.config.ts    # Drizzle Kit Configuration
│   │   └── package.json
│   │
│   └── web/                     # Frontend Single Page Application (React + Vite)
│       ├── src/
│       │   ├── assets/          # Icons, logos, static files
│       │   ├── components/      # UI components (shadcn/ui style, Table, Dialog...)
│       │   ├── context/         # AuthContext, Supabase Session Management
│       │   ├── hooks/           # Custom hooks, TanStack Query hooks
│       │   ├── layouts/         # AppLayout (Sidebar, Header, Breadcrumbs)
│       │   ├── pages/           # Login, Dashboard, Brands, Products, Detail
│       │   ├── services/        # Axios/Fetch API client kết nối backend
│       │   └── types/           # Frontend-specific types
│       ├── vite.config.ts
│       ├── tailwind.config.js
│       └── package.json
│
├── packages/
│   └── shared/                  # Thư viện dùng chung (Shared Package)
│       ├── src/
│       │   ├── types/           # TypeScript interfaces (Product, Brand, User, Role...)
│       │   ├── schemas/         # Zod validation schemas dùng chung cả FE & BE
│       │   └── constants/       # Role enums, Permissions, Error codes
│       └── package.json
│
├── docs/                        # Tài liệu kiến trúc, database ERD, API specs
│   ├── architecture.md
│   ├── database.md
│   └── api.md
├── pnpm-workspace.yaml
├── package.json                 # Root package scripts
├── .env.example
└── README.md
```

---

## 3. Kiến Trúc Backend (Layered Architecture)

Luồng xử lý một request tuân thủ nghiêm ngặt mô hình 4 tầng:

```
[ Client Request ]
       │
       ▼
[ Middlewares ] ──────► Auth Middleware (Supabase JWT Verification)
       │        ──────► RBAC Middleware (Kiểm tra Role & Permissions)
       │        ──────► Zod Validation Middleware (Validate Body/Query/Params)
       ▼
[ Controllers ] ──────► Tiếp nhận request, bóc tách params, gọi Service, trả response chuẩn
       │
       ▼
[ Services ]    ──────► Xử lý business rules, kiểm tra xung đột SKU, tính toán
       │
       ▼
[ Repositories ] ─────► Thao tác CSDL thông qua Drizzle ORM (Query Builder / Prepared Statements)
       │
       ▼
[ PostgreSQL / Supabase ]
```

### Nguyên tắc thiết kế:
- **Controllers** không chứa logic nghiệp vụ và không viết raw SQL / ORM query trực tiếp.
- **Services** độc lập với HTTP context (dễ viết Unit Test).
- **Repositories** cô lập tầng dữ liệu; khi thay đổi CSDL hoặc migration, các tầng trên không bị ảnh hưởng.
- **Xử lý lỗi tập trung (Centralized Error Handling)**: Tất cả lỗi từ validation, authorization, database conflict đều đi qua `errorHandler` và trả về cấu trúc lỗi chuẩn JSON.

---

## 4. Cơ Chế Xác Thực & Phân Quyền (Authentication & RBAC)

### 4.1 Supabase Auth & JWT Flow
1. **Frontend**: Người dùng nhập Email/Mật khẩu trên trang đăng nhập. Supabase Client thực hiện xác thực và lưu Access Token (JWT) an toàn trong bộ nhớ/cookie/storage của trình duyệt.
2. **Gọi API**: Mỗi request đến Backend kèm header: `Authorization: Bearer <access_token>`.
3. **Backend Middleware**:
   - `authenticateUser`: Kiểm tra token thông qua Supabase Auth Client (`supabase.auth.getUser(token)`).
   - Lấy thông tin `user_id` -> truy vấn bảng `profiles` và kết nối với bảng `roles` và `permissions`.
   - Gắn `req.user = { id, email, role, permissions }` vào Request Context.
4. **Bảo mật tuyệt đối**:
   - Frontend **chỉ sử dụng** `SUPABASE_ANON_KEY` (khóa công khai an toàn cho trình duyệt).
   - Backend nắm giữ `SUPABASE_SERVICE_ROLE_KEY` (chỉ dùng nội bộ cho các tác vụ quản trị CSDL hoặc đồng bộ hồ sơ). **Tuyệt đối không bao giờ phơi bày Service Role Key ra phía Client**.

### 4.2 Ma Trận Quyền Hạn (RBAC Matrix - Sprint 01 & Kế Thừa)

| Tính Năng / API | Quyền (Permission) | Admin | Kho (Warehouse) | Sale |
| :--- | :--- | :---: | :---: | :---: |
| Đăng nhập, xem hồ sơ cá nhân | `auth:profile` | ✅ | ✅ | ✅ |
| Xem danh sách & chi tiết Hãng | `brands:read` | ✅ | ✅ | ✅ |
| Quản lý Hãng (Tạo, sửa, xóa) | `brands:manage` | ✅ | ❌ | ❌ |
| Tra cứu Sản phẩm / SKU | `products:read` | ✅ | ✅ | ✅ |
| Thêm mới Sản phẩm | `products:create` | ✅ | ❌ | ❌ |
| Cập nhật thông tin Sản phẩm | `products:update` | ✅ | ❌ | ❌ |
| Xóa / Ẩn Sản phẩm | `products:delete` | ✅ | ❌ | ❌ |
| Xem số lượng tồn kho theo lô | `inventory:read` | ✅ | ✅ | ⚠️ (Chỉ khả dụng) |
| Nhập kho, xuất kho, điều chuyển | `inventory:manage` | ✅ | ✅ | ❌ |
| Quản lý bảng giá | `prices:manage` | ✅ | ❌ | ❌ |
| Lập báo giá & đơn hàng | `quotes:create` | ✅ | ❌ | ✅ |

---

## 5. Thiết Kế Row Level Security (RLS)

Để bảo đảm an toàn dữ liệu nhiều lớp (Defense in Depth):
1. **Tầng API Backend**: Thực thi kiểm tra quyền RBAC trước khi vào Controller.
2. **Tầng CSDL (Supabase PostgreSQL RLS)**:
   - Bảng `brands`, `categories`, `products`: Cho phép tất cả người dùng đã đăng nhập (`authenticated`) quyền `SELECT`. Quyền `INSERT/UPDATE/DELETE` chỉ dành cho role `admin`.
   - Bảng `profiles`: Người dùng chỉ có quyền đọc/ghi hồ sơ của chính mình (`auth.uid() = id`), hoặc role `admin` có quyền toàn bộ.
   - Bảng `inventory_lots` (Sprint 02): Role `admin` và `warehouse` có toàn quyền, role `sale` chỉ được xem qua view tổng hợp tồn khả dụng.

---

## 6. Lộ Trình Triển Khai (Sprint Roadmap)

- **Sprint 01 (Hiện tại)**:
  - Khởi tạo monorepo workspace, TypeScript strict, Lint, Format, Build scripts.
  - Thiết kế kiến trúc tổng thể, ERD 18+ bảng, đặc tả REST API.
  - Cài đặt Drizzle ORM, Core Migrations (brands, categories, products, profiles, roles, permissions).
  - Seed dữ liệu mẫu (Cisco, HPE Aruba, Fortinet với các SKU đại diện).
  - Backend API (Health, Brands, Products CRUD, Zod validation, Auth/RBAC middleware).
  - Frontend SPA (Layout, Login, Dashboard, Brands, Products Table TanStack, Create/Edit Product, Error/Loading states).
  - Unit & Integration Tests.
- **Sprint 02**:
  - Quản lý kho theo Lô nhập (`inventory_lots`, `stock_balances`, `stock_movements`).
  - Phân loại CO/CQ, tình trạng hóa đơn VAT, nhà cung cấp.
  - Hệ thống đa bảng giá (`price_lists`, `price_list_items`) theo cấp đại lý/dự án.
  - Công cụ Import Excel danh mục lớn (10.000+ SKU) với cơ chế preview & background job queue.
- **Sprint 03**:
  - Quản lý Khách hàng, Báo giá (`quotes`) và Đơn hàng (`orders`).
  - Giữ chỗ hàng tồn (`stock_reservations`) theo thời hạn báo giá.
  - Supabase Realtime thông báo khi tồn kho biến động.
