# 🚀 Quick Setup Guide

## Prerequisites Checklist
- [ ] PHP 8.2 or higher installed
- [ ] Composer installed
- [ ] MySQL 8.0 or higher installed and running
- [ ] Git installed

## Step-by-Step Installation

### 1️⃣ Clone & Install Dependencies (5 minutes)
```bash
# Clone repository
git clone <your-repository-url>
cd ecommerce-order-management

# Install PHP dependencies
composer install
```

### 2️⃣ Environment Configuration (3 minutes)
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Generate JWT secret
php artisan jwt:secret
```

### 3️⃣ Database Setup (2 minutes)
```bash
# Create database
mysql -u root -p
CREATE DATABASE ecommerce_orders;
EXIT;

# Update .env file with database credentials
DB_DATABASE=ecommerce_orders
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 4️⃣ Run Migrations & Seeders (2 minutes)
```bash
# Run migrations
php artisan migrate

# Seed data
php artisan db:seed
```

**Sample Users Created:**
- Admin: `admin@ecommerce.com` / `password`
- Vendor: ` vendor@electronics.com` / `password`
- Customer: `john.doe@example.com` / `password`

### 5️⃣ Start Application (1 minute)
```bash
# Terminal 1: Start web server
php artisan serve

# Terminal 2: Start queue worker
php artisan queue:work
```

Your API is now running at: **http://localhost:8000/api**

---

## ✅ Verification Steps

### Test 1: Health Check
```bash
curl http://localhost:8000/api/health
```
**Expected**: `{"status":"ok","timestamp":"..."}`

### Test 2: Register User
```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role": "customer"
  }'
```
**Expected**: `{"message":"User registered successfully", "authorization":{"token":"..."}}`

### Test 3: Login
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password"
  }'
```
**Expected**: `{"message":"Login successful", "authorization":{"token":"..."}}`

### Test 4: Get Products (with token)
```bash
# Replace <TOKEN> with token from login response
curl -X GET http://localhost:8000/api/v1/products \
  -H "Authorization: Bearer <TOKEN>"
```
**Expected**: Paginated list of products

---

## 🧪 Running Tests
```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage
```
---

## 🐛 Troubleshooting

### Issue: "Key not found" error
**Solution**: Run `php artisan key:generate`

### Issue: JWT token errors
**Solution**: Run `php artisan jwt:secret --force`

### Issue: Database connection failed
**Solution**: Check `.env` database credentials and ensure MySQL is running

### Issue: Queue jobs not processing
**Solution**: Ensure queue worker is running: `php artisan queue:work`

### Issue: Permission denied on storage
**Solution**:
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

---


# Complete API Documentation
## E-Commerce Order Management System

**Version:** 1.0  
**Base URL:** `http://localhost:8000/api/v1`  
**Authentication:** Bearer Token (JWT)  
**Author:** Alamingemamin

---

## Table of Contents

1. [Authentication](#authentication)
2. [Products API](#products-api)
3. [Orders API](#orders-api)
4. [Error Handling](#error-handling)
5. [Response Formats](#response-formats)
6. [Rate Limiting](#rate-limiting)
7. [Postman Collection](#postman-collection)

---

## Authentication

All protected endpoints require a JWT token in the Authorization header:

```
Authorization: Bearer YOUR_JWT_TOKEN
```

### Register User

```http
POST /auth/register
```

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "role": "customer"
}
```

**Response (201):**
```json
{
  "message": "User registered successfully",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "customer",
    "created_at": "2025-11-16T20:55:31.000000Z"
  },
  "authorization": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "type": "bearer"
  }
}
```

### Login

```http
POST /auth/login
```

**Request Body:**
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response (200):**
```json
{
  "message": "Login successful",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "customer"
  },
  "authorization": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "type": "bearer"
  }
}
```

### Get Current User

```http
GET /auth/me
```

**Response (200):**
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "customer"
  }
}
```

### Refresh Token

```http
POST /auth/refresh
```

**Response (200):**
```json
{
  "authorization": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "type": "bearer"
  }
}
```

### Logout

```http
POST /auth/logout
```

**Response (200):**
```json
{
  "message": "Logout successful"
}
```

---

## Products API

### Get All Products

```http
GET /products
```

**Query Parameters:**
- `search` (string) - Search by name, description, or SKU
- `vendor_id` (integer) - Filter by vendor
- `min_price` (decimal) - Minimum price filter
- `max_price` (decimal) - Maximum price filter
- `per_page` (integer) - Items per page (default: 15)

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "vendor_id": 2,
      "name": "Wireless Headphones",
      "slug": "wireless-headphones",
      "description": "Premium wireless headphones with noise cancellation",
      "price": 299.99,
      "sku": "WH-001",
      "stock_quantity": 100,
      "low_stock_threshold": 10,
      "is_active": true,
      "image": "products/wireless-headphones.jpg",
      "metadata": {
        "brand": "AudioTech",
        "category": "Audio",
        "warranty": "2 years"
      },
      "is_low_stock": false,
      "variants": [
        {
          "id": 1,
          "product_id": 1,
          "sku": "WH-001-BLACK",
          "name": "Wireless Headphones - Black",
          "price": 299.99,
          "stock_quantity": 50,
          "attributes": {
            "color": "Black"
          },
          "is_active": true
        }
      ],
      "created_at": "2025-11-16T20:55:31.000000Z",
      "updated_at": "2025-11-16T20:55:31.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 15,
    "to": 15,
    "total": 67
  }
}
```

### Get Single Product

```http
GET /products/{id}
```

**Response (200):**
```json
{
  "id": 1,
  "vendor_id": 2,
  "name": "Wireless Headphones",
  "slug": "wireless-headphones",
  "description": "Premium wireless headphones with noise cancellation",
  "price": 299.99,
  "sku": "WH-001",
  "stock_quantity": 100,
  "low_stock_threshold": 10,
  "is_active": true,
  "image": "products/wireless-headphones.jpg",
  "metadata": {
    "brand": "AudioTech",
    "category": "Audio",
    "warranty": "2 years"
  },
  "is_low_stock": false,
  "variants": [
    {
      "id": 1,
      "product_id": 1,
      "sku": "WH-001-BLACK",
      "name": "Wireless Headphones - Black",
      "price": 299.99,
      "stock_quantity": 50,
      "attributes": {
        "color": "Black"
      },
      "is_active": true
    },
    {
      "id": 2,
      "product_id": 1,
      "sku": "WH-001-WHITE",
      "name": "Wireless Headphones - White",
      "price": 299.99,
      "stock_quantity": 30,
      "attributes": {
        "color": "White"
      },
      "is_active": true
    }
  ],
  "created_at": "2025-11-16T20:55:31.000000Z",
  "updated_at": "2025-11-16T20:55:31.000000Z"
}
```

### Create Product

```http
POST /products
```

**Request Body:**
```json
{
  "name": "Wireless Headphones",
  "description": "Premium wireless headphones with noise cancellation",
  "price": 299.99,
  "sku": "WH-001",
  "stock_quantity": 100,
  "low_stock_threshold": 10,
  "is_active": true,
  "image": "products/wireless-headphones.jpg",
  "metadata": {
    "brand": "AudioTech",
    "category": "Audio",
    "warranty": "2 years"
  },
  "variants": [
    {
      "sku": "WH-001-BLACK",
      "name": "Wireless Headphones - Black",
      "price": 299.99,
      "stock_quantity": 50,
      "attributes": {
        "color": "Black"
      },
      "is_active": true
    },
    {
      "sku": "WH-001-WHITE",
      "name": "Wireless Headphones - White",
      "price": 299.99,
      "stock_quantity": 30,
      "attributes": {
        "color": "White"
      },
      "is_active": true
    },
    {
      "sku": "WH-001-BLUE",
      "name": "Wireless Headphones - Blue",
      "price": 299.99,
      "stock_quantity": 20,
      "attributes": {
        "color": "Blue"
      },
      "is_active": true
    }
  ]
}
```

**Response (201):**
```json
{
  "message": "Product created successfully",
  "product": {
    "id": 1,
    "vendor_id": 2,
    "name": "Wireless Headphones",
    "slug": "wireless-headphones",
    "description": "Premium wireless headphones with noise cancellation",
    "price": 299.99,
    "sku": "WH-001",
    "stock_quantity": 100,
    "low_stock_threshold": 10,
    "is_active": true,
    "image": "products/wireless-headphones.jpg",
    "metadata": {
      "brand": "AudioTech",
      "category": "Audio",
      "warranty": "2 years"
    },
    "variants": [
      {
        "id": 1,
        "product_id": 1,
        "sku": "WH-001-BLACK",
        "name": "Wireless Headphones - Black",
        "price": 299.99,
        "stock_quantity": 50,
        "attributes": {
          "color": "Black"
        },
        "is_active": true
      }
    ],
    "created_at": "2025-11-16T20:55:31.000000Z",
    "updated_at": "2025-11-16T20:55:31.000000Z"
  }
}
```

### Update Product

```http
PUT /products/{id}
```

**Request Body:**
```json
{
  "name": "Updated Product Name",
  "description": "Updated description",
  "price": 199.99,
  "low_stock_threshold": 15,
  "is_active": true,
  "metadata": {
    "brand": "Updated Brand",
    "category": "Updated Category"
  }
}
```

**Response (200):**
```json
{
  "message": "Product updated successfully",
  "product": {
    "id": 1,
    "name": "Updated Product Name",
    "description": "Updated description",
    "price": 199.99,
    "low_stock_threshold": 15,
    "is_active": true,
    "metadata": {
      "brand": "Updated Brand",
      "category": "Updated Category"
    },
    "variants": [
      {
        "id": 1,
        "product_id": 1,
        "sku": "WH-001-BLACK",
        "name": "Wireless Headphones - Black",
        "price": 299.99,
        "stock_quantity": 50,
        "attributes": {
          "color": "Black"
        },
        "is_active": true
      }
    ],
    "updated_at": "2025-11-16T20:55:31.000000Z"
  }
}
```

### Delete Product

```http
DELETE /products/{id}
```

**Response (200):**
```json
{
  "message": "Product deleted successfully"
}
```

### Import Products from CSV

```http
POST /products/import/csv
```

**Headers:**
- `Content-Type: multipart/form-data`

**Form Data:**
- `file` (file) - CSV file containing products

**CSV Format:**
```csv
name,slug,description,price,sku,stock_quantity,low_stock_threshold,is_active,vendor_id,image,metadata,variants
"iPhone 15","iphone-15","Latest iPhone model",999.00,IP15-001,100,10,true,2,"products/iphone-15.jpg","{""brand"":""Apple"",""category"":""Smartphones""}","[{""sku"":""IP15-128-BLACK"",""name"":""iPhone 15 - 128GB - Black"",""price"":999.00,""stock_quantity"":25,""attributes"":{""storage"":""128GB"",""color"":""Black""},""is_active"":true}]"
```

**Response (200):**
```json
{
  "message": "Import completed",
  "result": {
    "total_processed": 10,
    "successful": 8,
    "failed": 2,
    "errors": [
      {
        "row": 5,
        "error": "SKU already exists"
      }
    ]
  }
}
```

### Product Variant Examples

#### T-Shirt with Size and Color Variants

```bash
curl -X POST http://localhost:8000/api/v1/products \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Premium Cotton T-Shirt",
    "slug": "premium-cotton-tshirt",
    "description": "Comfortable 100% cotton t-shirt",
    "price": 29.99,
    "sku": "TSHIRT-001",
    "stock_quantity": 200,
    "low_stock_threshold": 20,
    "is_active": true,
    "metadata": {
      "material": "100% Cotton",
      "care": "Machine wash cold"
    },
    "variants": [
      {
        "sku": "TSHIRT-S-WHITE",
        "name": "T-Shirt - Small - White",
        "price": 29.99,
        "stock_quantity": 30,
        "attributes": {
          "size": "S",
          "color": "White"
        },
        "is_active": true
      },
      {
        "sku": "TSHIRT-M-WHITE",
        "name": "T-Shirt - Medium - White", 
        "price": 29.99,
        "stock_quantity": 35,
        "attributes": {
          "size": "M",
          "color": "White"
        },
        "is_active": true
      },
      {
        "sku": "TSHIRT-L-BLACK",
        "name": "T-Shirt - Large - Black",
        "price": 29.99,
        "stock_quantity": 25,
        "attributes": {
          "size": "L",
          "color": "Black"
        },
        "is_active": true
      }
    ]
  }'
```

#### Laptop with Storage and Memory Variants

```bash
curl -X POST http://localhost:8000/api/v1/products \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Gaming Laptop Pro",
    "slug": "gaming-laptop-pro",
    "description": "High-performance gaming laptop",
    "price": 1499.99,
    "sku": "GLP-001",
    "stock_quantity": 50,
    "low_stock_threshold": 10,
    "is_active": true,
    "metadata": {
      "brand": "GameTech",
      "warranty": "3 years",
      "gpu": "RTX 4070"
    },
    "variants": [
      {
        "sku": "GLP-001-16GB-512GB",
        "name": "Gaming Laptop - 16GB RAM - 512GB SSD",
        "price": 1499.99,
        "stock_quantity": 15,
        "attributes": {
          "memory": "16GB",
          "storage": "512GB SSD"
        },
        "is_active": true
      },
      {
        "sku": "GLP-001-32GB-1TB",
        "name": "Gaming Laptop - 32GB RAM - 1TB SSD",
        "price": 1899.99,
        "stock_quantity": 20,
        "attributes": {
          "memory": "32GB", 
          "storage": "1TB SSD"
        },
        "is_active": true
      }
    ]
  }'
```

#### Phone with Storage and Color Variants

```bash
curl -X POST http://localhost:8000/api/v1/products \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "SmartPhone X1",
    "slug": "smartphone-x1",
    "description": "Latest flagship smartphone",
    "price": 899.99,
    "sku": "SPX1-001",
    "stock_quantity": 100,
    "low_stock_threshold": 15,
    "is_active": true,
    "metadata": {
      "brand": "TechCorp",
      "warranty": "1 year",
      "display": "6.7 inch OLED"
    },
    "variants": [
      {
        "sku": "SPX1-128GB-BLACK",
        "name": "SmartPhone X1 - 128GB - Black",
        "price": 899.99,
        "stock_quantity": 25,
        "attributes": {
          "storage": "128GB",
          "color": "Black"
        },
        "is_active": true
      },
      {
        "sku": "SPX1-256GB-BLACK",
        "name": "SmartPhone X1 - 256GB - Black",
        "price": 1099.99,
        "stock_quantity": 30,
        "attributes": {
          "storage": "256GB",
          "color": "Black"
        },
        "is_active": true
      },
      {
        "sku": "SPX1-256GB-WHITE",
        "name": "SmartPhone X1 - 256GB - White",
        "price": 1099.99,
        "stock_quantity": 25,
        "attributes": {
          "storage": "256GB",
          "color": "White"
        },
        "is_active": true
      }
    ]
  }'
```

---

## Orders API

### Get All Orders

```http
GET /orders
```

**Query Parameters:**
- `per_page` (integer) - Items per page (default: 15)

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "order_number": "ORD-123ABC",
      "customer_id": 5,
      "status": "pending",
      "subtotal": 599.98,
      "tax": 48.00,
      "shipping": 15.00,
      "total": 662.98,
      "shipping_address": "123 Main St, New York, NY 10001",
      "billing_address": "123 Main St, New York, NY 10001",
      "notes": "Please leave at front door",
      "confirmed_at": null,
      "shipped_at": null,
      "delivered_at": null,
      "cancelled_at": null,
      "customer": {
        "id": 5,
        "name": "John Doe",
        "email": "john@example.com"
      },
      "items": [
        {
          "id": 1,
          "order_id": 1,
          "product_id": 1,
          "product_variant_id": 2,
          "product_name": "Wireless Headphones",
          "product_sku": "WH-001-BLACK",
          "price": 299.99,
          "quantity": 2,
          "subtotal": 599.98,
          "variant_details": {
            "color": "Black"
          }
        }
      ],
      "created_at": "2025-11-16T20:55:31.000000Z",
      "updated_at": "2025-11-16T20:55:31.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 3,
    "per_page": 15,
    "to": 15,
    "total": 42
  }
}
```

### Get Single Order

```http
GET /orders/{id}
```

**Response (200):**
```json
{
  "id": 1,
  "order_number": "ORD-123ABC",
  "customer_id": 5,
  "status": "pending",
  "subtotal": 599.98,
  "tax": 48.00,
  "shipping": 15.00,
  "total": 662.98,
  "shipping_address": "123 Main St, New York, NY 10001",
  "billing_address": "123 Main St, New York, NY 10001",
  "notes": "Please leave at front door",
  "confirmed_at": null,
  "shipped_at": null,
  "delivered_at": null,
  "cancelled_at": null,
  "customer": {
    "id": 5,
    "name": "John Doe",
    "email": "john@example.com"
  },
  "items": [
    {
      "id": 1,
      "order_id": 1,
      "product_id": 1,
      "product_variant_id": 2,
      "product_name": "Wireless Headphones",
      "product_sku": "WH-001-BLACK",
      "price": 299.99,
      "quantity": 2,
      "subtotal": 599.98,
      "variant_details": {
        "color": "Black"
      },
      "product": {
        "id": 1,
        "name": "Wireless Headphones",
        "image": "products/wireless-headphones.jpg"
      },
      "variant": {
        "id": 2,
        "sku": "WH-001-BLACK",
        "name": "Wireless Headphones - Black",
        "attributes": {
          "color": "Black"
        }
      }
    }
  ],
  "created_at": "2025-11-16T20:55:31.000000Z",
  "updated_at": "2025-11-16T20:55:31.000000Z"
}
```

### Create Order

```http
POST /orders
```

**Request Body:**
```json
{
  "items": [
    {
      "product_id": 1,
      "variant_id": 2,
      "quantity": 2
    },
    {
      "product_id": 3,
      "variant_id": null,
      "quantity": 1
    }
  ],
  "shipping_address": "123 Main St, New York, NY 10001",
  "billing_address": "123 Main St, New York, NY 10001",
  "shipping": 15.00,
  "notes": "Please leave at front door"
}
```

**Response (201):**
```json
{
  "message": "Order created successfully",
  "order": {
    "id": 1,
    "order_number": "ORD-123ABC",
    "customer_id": 5,
    "status": "pending",
    "subtotal": 599.98,
    "tax": 48.00,
    "shipping": 15.00,
    "total": 662.98,
    "shipping_address": "123 Main St, New York, NY 10001",
    "billing_address": "123 Main St, New York, NY 10001",
    "notes": "Please leave at front door",
    "confirmed_at": null,
    "shipped_at": null,
    "delivered_at": null,
    "cancelled_at": null,
    "items": [
      {
        "id": 1,
        "order_id": 1,
        "product_id": 1,
        "product_variant_id": 2,
        "product_name": "Wireless Headphones",
        "product_sku": "WH-001-BLACK",
        "price": 299.99,
        "quantity": 2,
        "subtotal": 599.98,
        "variant_details": {
          "color": "Black"
        }
      }
    ],
    "created_at": "2025-11-16T20:55:31.000000Z",
    "updated_at": "2025-11-16T20:55:31.000000Z"
  }
}
```

### Confirm Order (Admin Only)

```http
PATCH /orders/{id}/confirm
```

**Response (200):**
```json
{
  "message": "Order confirmed successfully",
  "order": {
    "id": 1,
    "order_number": "ORD-123ABC",
    "status": "processing",
    "confirmed_at": "2025-11-16T20:55:31.000000Z",
    "items": [
      {
        "id": 1,
        "product_name": "Wireless Headphones",
        "quantity": 2,
        "subtotal": 599.98
      }
    ]
  }
}
```

### Update Order Status (Admin Only)

```http
PATCH /orders/{id}/status
```

**Request Body:**
```json
{
  "status": "shipped"
}
```

**Status Options:**
- `pending` - Order created, awaiting confirmation
- `processing` - Order confirmed, being prepared
- `shipped` - Order shipped, in transit
- `delivered` - Order delivered to customer
- `cancelled` - Order cancelled

**Response (200):**
```json
{
  "message": "Order status updated successfully",
  "order": {
    "id": 1,
    "order_number": "ORD-123ABC",
    "status": "shipped",
    "shipped_at": "2025-11-16T20:55:31.000000Z",
    "updated_at": "2025-11-16T20:55:31.000000Z"
  }
}
```

### Cancel Order

```http
DELETE /orders/{id}
```

**Response (200):**
```json
{
  "message": "Order cancelled successfully",
  "order": {
    "id": 1,
    "order_number": "ORD-123ABC",
    "status": "cancelled",
    "cancelled_at": "2025-11-16T20:55:31.000000Z"
  }
}
```

### Order Creation Examples

#### Simple Order with Variants

```bash
curl -X POST http://localhost:8000/api/v1/orders \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "items": [
      {
        "product_id": 1,
        "variant_id": 2,
        "quantity": 2
      }
    ],
    "shipping_address": "123 Main St, New York, NY 10001",
    "billing_address": "123 Main St, New York, NY 10001",
    "shipping": 15.00,
    "notes": "Please leave at front door"
  }'
```

#### Order with Multiple Products and Variants

```bash
curl -X POST http://localhost:8000/api/v1/orders \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "items": [
      {
        "product_id": 1,
        "variant_id": 2,
        "quantity": 1
      },
      {
        "product_id": 3,
        "variant_id": 5,
        "quantity": 2
      },
      {
        "product_id": 5,
        "variant_id": null,
        "quantity": 1
      }
    ],
    "shipping_address": "456 Oak Ave, Los Angeles, CA 90210",
    "billing_address": "456 Oak Ave, Los Angeles, CA 90210",
    "shipping": 25.00,
    "notes": "Ring doorbell twice"
  }'
```

---

## Error Handling

### HTTP Status Codes

- `200` - Success
- `201` - Created successfully
- `400` - Bad Request
- `401` - Unauthorized (Invalid or missing token)
- `403` - Forbidden (Insufficient permissions)
- `404` - Not Found
- `422` - Validation Error
- `500` - Internal Server Error

### Error Response Format

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field_name": ["Error message"],
    "variants.0.sku": ["The variants.0.sku field is required."],
    "items.0.quantity": ["The items.0.quantity must be at least 1."]
  }
}
```

### Common Error Examples

#### Validation Error (422)
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "name": ["The name field is required."],
    "price": ["The price must be a number."],
    "variants.0.sku": ["The variants.0.sku field is required."],
    "variants.0.attributes": ["The variants.0.attributes field is required."]
  }
}
```

#### Unauthorized Error (401)
```json
{
  "error": "Unauthorized"
}
```

#### Not Found Error (404)
```json
{
  "message": "No query results for model [App\\Models\\Product]."
}
```

---

## Response Formats

### Success Response
All successful responses follow this general format:

```json
{
  "message": "Operation completed successfully",
  "data": { ... } // or "product"/"order"/"user"
}
```

### Paginated Response
List endpoints return paginated data:

```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 15,
    "to": 15,
    "total": 67
  },
  "links": {
    "first": "http://localhost:8000/api/v1/products?page=1",
    "last": "http://localhost:8000/api/v1/products?page=5",
    "prev": null,
    "next": "http://localhost:8000/api/v1/products?page=2"
  }
}
```

### Data Types

- **Decimal numbers** are returned with 2 decimal places
- **Dates** are in ISO 8601 format: `2025-11-16T20:55:31.000000Z`
- **Boolean values** are `true` or `false`
- **Arrays** are used for lists and variant attributes
- **Null values** are explicitly `null`

---

## Rate Limiting

Currently, no rate limiting is implemented, but it's recommended to:

- Implement exponential backoff for retry logic
- Cache frequently accessed data
- Use bulk operations when available
- Monitor API usage patterns

---

## Data Models

### Product Model
```json
{
  "id": "integer",
  "vendor_id": "integer",
  "name": "string",
  "slug": "string",
  "description": "string|null",
  "price": "decimal(8,2)",
  "sku": "string",
  "stock_quantity": "integer",
  "low_stock_threshold": "integer",
  "is_active": "boolean",
  "image": "string|null",
  "metadata": "object",
  "is_low_stock": "boolean",
  "variants": "array",
  "created_at": "datetime",
  "updated_at": "datetime"
}
```

### ProductVariant Model
```json
{
  "id": "integer",
  "product_id": "integer",
  "sku": "string",
  "name": "string",
  "price": "decimal(8,2)",
  "stock_quantity": "integer",
  "attributes": "object",
  "is_active": "boolean",
  "created_at": "datetime",
  "updated_at": "datetime"
}
```

### Order Model
```json
{
  "id": "integer",
  "order_number": "string",
  "customer_id": "integer",
  "status": "string",
  "subtotal": "decimal(8,2)",
  "tax": "decimal(8,2)",
  "shipping": "decimal(8,2)",
  "total": "decimal(8,2)",
  "shipping_address": "string",
  "billing_address": "string|null",
  "notes": "string|null",
  "confirmed_at": "datetime|null",
  "shipped_at": "datetime|null",
  "delivered_at": "datetime|null",
  "cancelled_at": "datetime|null",
  "customer": "object",
  "items": "array",
  "created_at": "datetime",
  "updated_at": "datetime"
}
```

### OrderItem Model
```json
{
  "id": "integer",
  "order_id": "integer",
  "product_id": "integer",
  "product_variant_id": "integer|null",
  "product_name": "string",
  "product_sku": "string",
  "price": "decimal(8,2)",
  "quantity": "integer",
  "subtotal": "decimal(8,2)",
  "variant_details": "object|null",
  "product": "object",
  "variant": "object|null"
}
```

---

## Complete Workflow Examples

### Complete E-Commerce Workflow

```bash
#!/bin/bash

# 1. Login and get token
TOKEN=$(curl -s -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "vendor@electronics.com", "password": "password"}' | \
  jq -r '.authorization.token')

echo "Token: $TOKEN"

# 2. Create a product with variants
PRODUCT_ID=$(curl -s -X POST http://localhost:8000/api/v1/products \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Gaming Laptop",
    "slug": "gaming-laptop",
    "description": "High-performance gaming laptop",
    "price": 1499.99,
    "sku": "GL-001",
    "stock_quantity": 50,
    "low_stock_threshold": 10,
    "is_active": true,
    "metadata": {
      "brand": "GameTech",
      "category": "Laptops",
      "gpu": "RTX 4070"
    },
    "variants": [
      {
        "sku": "GL-001-16GB-512GB",
        "name": "Gaming Laptop - 16GB RAM - 512GB SSD",
        "price": 1499.99,
        "stock_quantity": 20,
        "attributes": {
          "memory": "16GB",
          "storage": "512GB SSD"
        },
        "is_active": true
      },
      {
        "sku": "GL-001-32GB-1TB",
        "name": "Gaming Laptop - 32GB RAM - 1TB SSD",
        "price": 1899.99,
        "stock_quantity": 15,
        "attributes": {
          "memory": "32GB",
          "storage": "1TB SSD"
        },
        "is_active": true
      }
    ]
  }' | jq -r '.product.id')

echo "Product ID: $PRODUCT_ID"

# 3. Get the created product
curl -X GET http://localhost:8000/api/v1/products/$PRODUCT_ID \
  -H "Authorization: Bearer $TOKEN"

# 4. Create an order for this product
ORDER_ID=$(curl -s -X POST http://localhost:8000/api/v1/orders \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "items": [
      {
        "product_id": '$PRODUCT_ID',
        "variant_id": 1,
        "quantity": 1
      }
    ],
    "shipping_address": "123 Main St, New York, NY 10001",
    "shipping": 25.00,
    "notes": "Please handle with care"
  }' | jq -r '.order.id')

echo "Order ID: $ORDER_ID"

# 5. Admin confirms the order
curl -X PATCH http://localhost:8000/api/v1/orders/$ORDER_ID/confirm \
  -H "Authorization: Bearer $TOKEN"

# 6. Admin updates order status
curl -X PATCH http://localhost:8000/api/v1/orders/$ORDER_ID/status \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status": "shipped"}'

# 7. Get order details
curl -X GET http://localhost:8000/api/v1/orders/$ORDER_ID \
  -H "Authorization: Bearer $TOKEN"

echo "Complete workflow executed successfully!"
```

---

## Tips and Best Practices

### Authentication
1. **Store tokens securely** - Use secure storage for JWT tokens
2. **Handle token expiration** - Implement automatic token refresh
3. **Use HTTPS** - Always use HTTPS in production

### Products
1. **Validate variant attributes** - Ensure all required attributes are provided
2. **Use unique SKUs** - Each product and variant should have a unique SKU
3. **Set appropriate stock levels** - Monitor stock quantities and set thresholds
4. **Use transactions** - For bulk operations, use database transactions

### Orders
1. **Validate inventory** - Check stock availability before creating orders
2. **Handle order status transitions** - Follow proper workflow (pending → processing → shipped → delivered)
3. **Log inventory changes** - Track all stock movements
4. **Handle errors gracefully** - Check response codes and handle errors

### General
1. **Implement retry logic** - For network failures
2. **Use pagination** - For large datasets
3. **Cache frequently accessed data** - Improve performance
4. **Monitor API usage** - Track patterns and optimize

---

## Support

For additional support or questions:
- Check the existing documentation files
- Review the Postman collection for examples
- Examine the existing test files for usage patterns

---

**Document Version:** 1.0  
**Last Updated:** November 16, 2025  
**Author:** Alamingemamin