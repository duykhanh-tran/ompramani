# Đặc Tả Kỹ Thuật REST API - IT Equipment Warehouse (Sprint 01)

## 1. Chuẩn Quy Ước API (API Standards)

### 1.1 Base URL
- Môi trường Local: `http://localhost:5000/api`
- Tiền tố API: `/api`

### 1.2 Headers Bắt Buộc
- Request JSON: `Content-Type: application/json`
- Request Xác thực: `Authorization: Bearer <supabase_access_token>`

### 1.3 Cấu Trúc Phản Hồi Chuẩn (Standard Response Format)

#### Phản Hồi Thành Công (Success):
```json
{
  "success": true,
  "data": { ... },
  "meta": {
    "page": 1,
    "limit": 20,
    "total": 105,
    "totalPages": 6
  }
}
```
*(Trường `meta` chỉ xuất hiện trong các API có phân trang).*

#### Phản Hồi Lỗi (Error):
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR | UNAUTHORIZED | FORBIDDEN | NOT_FOUND | CONFLICT | INTERNAL_SERVER_ERROR",
    "message": "Thông điệp mô tả lỗi thân thiện",
    "details": [
      {
        "field": "sku",
        "message": "SKU này đã tồn tại trong thương hiệu đã chọn"
      }
    ]
  }
}
```

---

## 2. Danh Sách Endpoint Sprint 01

### 2.1 Hệ Thống & Kiểm Tra Tình Trạng (Health Check)
#### `GET /api/health`
- **Mô tả**: Kiểm tra trạng thái hoạt động của Backend API server và kết nối Database.
- **Quyền hạn**: Public (Không yêu cầu đăng nhập).
- **Phản hồi 200 OK**:
```json
{
  "success": true,
  "data": {
    "status": "ok",
    "timestamp": "2026-10-28T07:45:00.000Z",
    "version": "1.0.0",
    "environment": "development"
  }
}
```

---

### 2.2 Xác Thực & Thông Tin Người Dùng (Authentication)

#### `GET /api/auth/me`
- **Mô tả**: Lấy thông tin hồ sơ người dùng hiện tại, vai trò (role) và danh sách quyền (permissions).
- **Quyền hạn**: `authenticated` (Cần Bearer Token).
- **Phản hồi 200 OK**:
```json
{
  "success": true,
  "data": {
    "id": "e6a49b67-0c7f-4f81-80bb-685b3bc82101",
    "email": "admin@ompramani.local",
    "fullName": "Quản Trị Viên Hệ Thống",
    "role": {
      "id": "3b7b3b3a-5431-41b2-a42e-18e3ec82a101",
      "code": "admin",
      "name": "Quản trị viên"
    },
    "permissions": [
      "products:read",
      "products:create",
      "products:update",
      "products:delete",
      "brands:read",
      "brands:manage",
      "inventory:read",
      "inventory:manage"
    ]
  }
}
```

---

### 2.3 Quản Lý Thương Hiệu (Brands)

#### `GET /api/brands`
- **Mô tả**: Lấy danh sách tất cả thương hiệu thiết bị đang hoạt động (Cisco, HPE Aruba, Fortinet, Dell, Lenovo...).
- **Quyền hạn**: `authenticated` (Admin, Kho, Sale đều có quyền xem).
- **Query Parameters**:
  - `includeCounts` (boolean, optional, default: `true`): Kèm số lượng sản phẩm liên kết.
- **Phản hồi 200 OK**:
```json
{
  "success": true,
  "data": [
    {
      "id": "c1a1a1a1-1111-4111-8111-111111111111",
      "name": "Cisco Systems",
      "code": "CISCO",
      "slug": "cisco",
      "logoUrl": "https://example.com/cisco.png",
      "description": "Thiết bị mạng doanh nghiệp, switch, router hàng đầu thế giới",
      "isActive": true,
      "productCount": 2,
      "createdAt": "2026-10-28T00:00:00Z"
    }
  ]
}
```

---

### 2.4 Quản Lý Sản Phẩm & SKU (Products Master Data)

#### `GET /api/products`
- **Mô tả**: Tìm kiếm, lọc và phân trang danh mục SKU sản phẩm trên toàn hệ thống.
- **Quyền hạn**: `authenticated` (Admin, Kho, Sale).
- **Query Parameters**:
  - `search` (string, optional): Từ khóa tìm kiếm theo Part Number (SKU) hoặc tên sản phẩm.
  - `brandId` (uuid, optional): Lọc theo ID thương hiệu.
  - `categoryId` (uuid, optional): Lọc theo ID danh mục.
  - `page` (integer, optional, default: 1): Số trang (bắt đầu từ 1).
  - `limit` (integer, optional, default: 10, max: 100): Số bản ghi trên mỗi trang.
  - `sortBy` (string, optional, default: `createdAt`): `sku` | `name` | `createdAt`.
  - `sortOrder` (string, optional, default: `desc`): `asc` | `desc`.
- **Phản hồi 200 OK**:
```json
{
  "success": true,
  "data": [
    {
      "id": "p1a1a1a1-1111-4111-8111-111111111111",
      "sku": "C9200-48T-E",
      "name": "Cisco Catalyst 9200 48-port Data Switch, Network Essentials",
      "brandId": "c1a1a1a1-1111-4111-8111-111111111111",
      "brand": {
        "id": "c1a1a1a1-1111-4111-8111-111111111111",
        "name": "Cisco Systems",
        "code": "CISCO"
      },
      "categoryId": "cat1-1111-4111-8111-111111111111",
      "category": {
        "id": "cat1-1111-4111-8111-111111111111",
        "name": "Switch"
      },
      "description": "Switch Cisco Catalyst 9200 series 48 cổng 1G RJ45 data...",
      "unit": "Cái",
      "warrantyMonths": 12,
      "specs": {
        "ports": "48x 10/100/1000",
        "uplinks": "Modular uplink options",
        "stacking": "160 Gbps Stacking"
      },
      "isActive": true,
      "createdAt": "2026-10-28T00:00:00Z",
      "updatedAt": "2026-10-28T00:00:00Z"
    }
  ],
  "meta": {
    "page": 1,
    "limit": 10,
    "total": 5,
    "totalPages": 1
  }
}
```

#### `GET /api/products/:id`
- **Mô tả**: Xem thông tin chi tiết một sản phẩm theo ID (chuẩn bị sẵn các slot để ghép nối dữ liệu tồn kho theo lô và bảng giá trong Sprint 02).
- **Quyền hạn**: `authenticated` (Admin, Kho, Sale).
- **Phản hồi 200 OK**:
```json
{
  "success": true,
  "data": {
    "id": "p1a1a1a1-1111-4111-8111-111111111111",
    "sku": "C9200-48T-E",
    "name": "Cisco Catalyst 9200 48-port Data Switch, Network Essentials",
    "brandId": "c1a1a1a1-1111-4111-8111-111111111111",
    "brand": {
      "id": "c1a1a1a1-1111-4111-8111-111111111111",
      "name": "Cisco Systems",
      "code": "CISCO",
      "slug": "cisco"
    },
    "category": {
      "id": "cat1-1111-4111-8111-111111111111",
      "name": "Switch",
      "slug": "switch"
    },
    "unit": "Cái",
    "warrantyMonths": 12,
    "specs": {
      "ports": "48x 10/100/1000",
      "uplinks": "Modular uplink options"
    },
    "description": "Switch Cisco Catalyst 9200...",
    "isActive": true,
    "createdAt": "2026-10-28T00:00:00Z",
    "updatedAt": "2026-10-28T00:00:00Z"
  }
}
```

#### `POST /api/products`
- **Mô tả**: Tạo mới Part Number / SKU vào danh mục hệ thống.
- **Quyền hạn**: Bắt buộc vai trò `Admin` (hoặc quyền `products:create`).
- **Request Body**:
```json
{
  "brandId": "c1a1a1a1-1111-4111-8111-111111111111",
  "categoryId": "cat1-1111-4111-8111-111111111111",
  "sku": "C9200L-24T-4G-E",
  "name": "Cisco Catalyst 9200L 24-port Data Switch, 4x1G uplinks",
  "unit": "Cái",
  "warrantyMonths": 12,
  "description": "Dòng Catalyst 9200L cố định 4 cổng quang SFP...",
  "specs": {
    "ports": "24x 10/100/1000",
    "uplinks": "4x 1G SFP"
  },
  "isActive": true
}
```
- **Phản hồi 201 Created**: Trả về dữ liệu sản phẩm vừa tạo.
- **Phản hồi 409 Conflict**: Khi SKU đã tồn tại trong cùng thương hiệu (`brandId`).
```json
{
  "success": false,
  "error": {
    "code": "CONFLICT",
    "message": "Part Number (SKU) đã tồn tại trong thương hiệu này",
    "details": [
      { "field": "sku", "message": "SKU 'C9200-48T-E' đã được đăng ký cho hãng Cisco Systems" }
    ]
  }
}
```

#### `PATCH /api/products/:id`
- **Mô tả**: Cập nhật thông tin SKU (tên, mô tả, thông số kỹ thuật, quy cách bảo hành, trạng thái).
- **Quyền hạn**: Bắt buộc vai trò `Admin` (hoặc quyền `products:update`).
- **Request Body**: (Bất kỳ trường nào trong schema tạo sản phẩm).
- **Phản hồi 200 OK**: Dữ liệu sau khi cập nhật.

---

### 2.5 Danh Mục Sản Phẩm (Categories)
#### `GET /api/categories`
- **Mô tả**: Lấy danh sách cây danh mục thiết bị (Switch, Router, Firewall, Server, Module/Phụ kiện).
- **Quyền hạn**: `authenticated`.
- **Phản hồi 200 OK**: Danh sách phân loại thiết bị.
