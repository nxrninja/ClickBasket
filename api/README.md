# ClickBasket Mobile API Documentation

## Base URL
```
https://yourdomain.com/api/
```

## Authentication

### Token-Based Authentication
All authenticated endpoints require a Bearer token in the Authorization header:
```
Authorization: Bearer <token>
```

Alternatively, you can send the token in the request body or as a query parameter:
```json
{
  "token": "<token>",
  "action": "..."
}
```

## Endpoints

### Authentication (`auth.php`)

#### Login
```http
POST /api/auth.php
Content-Type: application/json

{
  "action": "login",
  "email": "user@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "token": "eyJ1c2VyX2lkIjoxLCJlbWFpbCI6InVzZXJAZXhhbXBsZS5jb20iLCJleHAiOjE2MzQ1Njc4OTB9",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "first_name": "John",
    "last_name": "Doe",
    "phone": "+1234567890"
  }
}
```

#### Register
```http
POST /api/auth.php
Content-Type: application/json

{
  "action": "register",
  "first_name": "John",
  "last_name": "Doe",
  "email": "user@example.com",
  "phone": "+1234567890",
  "password": "password123"
}
```

#### Verify Token
```http
POST /api/auth.php
Content-Type: application/json

{
  "action": "verify",
  "token": "your_token_here"
}
```

### Products (`products.php`)

#### Get Products List
```http
GET /api/products.php?action=list&page=1&limit=12&category_id=1&search=keyword
```

**Response:**
```json
{
  "success": true,
  "products": [
    {
      "id": 1,
      "title": "Product Name",
      "description": "Product description",
      "price": 99.99,
      "category_name": "Category",
      "avg_rating": 4.5,
      "rating_count": 10,
      "screenshots": ["image1.jpg", "image2.jpg"],
      "image_urls": ["https://yourdomain.com/uploads/screenshots/image1.jpg"]
    }
  ],
  "pagination": {
    "current_page": 1,
    "total_pages": 5,
    "total_products": 50,
    "per_page": 12
  }
}
```

#### Get Product Details
```http
GET /api/products.php?action=detail&id=1
```

#### Get Categories
```http
GET /api/products.php?action=categories
```

#### Get Featured Products
```http
GET /api/products.php?action=featured&limit=10
```

### Cart (`cart.php`)

#### Add to Cart
```http
POST /api/cart.php
Authorization: Bearer <token>
Content-Type: application/json

{
  "action": "add",
  "product_id": 1,
  "quantity": 2
}
```

#### Get Cart Items
```http
GET /api/cart.php?action=get
Authorization: Bearer <token>
```

**Response:**
```json
{
  "success": true,
  "items": [
    {
      "id": 1,
      "product_id": 1,
      "title": "Product Name",
      "price": 99.99,
      "quantity": 2,
      "total": 199.98,
      "image": "image1.jpg"
    }
  ],
  "subtotal": 199.98,
  "count": 1
}
```

#### Update Cart Item
```http
POST /api/cart.php
Authorization: Bearer <token>
Content-Type: application/json

{
  "action": "update",
  "product_id": 1,
  "quantity": 3
}
```

#### Remove from Cart
```http
POST /api/cart.php
Authorization: Bearer <token>
Content-Type: application/json

{
  "action": "remove",
  "product_id": 1
}
```

#### Get Cart Count
```http
GET /api/cart.php?action=count
Authorization: Bearer <token>
```

#### Clear Cart
```http
POST /api/cart.php
Authorization: Bearer <token>
Content-Type: application/json

{
  "action": "clear"
}
```

### Orders (`orders.php`)

#### Create Order
```http
POST /api/orders.php
Authorization: Bearer <token>
Content-Type: application/json

{
  "action": "create",
  "billing_name": "John Doe",
  "billing_email": "john@example.com",
  "billing_phone": "+1234567890",
  "billing_address": "123 Main St, City, State",
  "payment_method": "cod"
}
```

#### Get Orders List
```http
GET /api/orders.php?action=list&page=1&limit=10
Authorization: Bearer <token>
```

#### Get Order Details
```http
GET /api/orders.php?action=detail&order_id=ORD123456789
Authorization: Bearer <token>
```

#### Cancel Order
```http
POST /api/orders.php
Authorization: Bearer <token>
Content-Type: application/json

{
  "action": "cancel",
  "order_id": "ORD123456789"
}
```

## Error Responses

All endpoints return errors in this format:
```json
{
  "success": false,
  "message": "Error description"
}
```

Common HTTP status codes:
- `200` - Success
- `400` - Bad Request (validation error)
- `401` - Unauthorized (authentication required)
- `404` - Not Found
- `500` - Internal Server Error

## CORS Support

All API endpoints include CORS headers to support cross-origin requests from mobile apps:
- `Access-Control-Allow-Origin: *`
- `Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS`
- `Access-Control-Allow-Headers: Content-Type, Authorization`

## Mobile App Integration Tips

1. **Store the token securely** in your mobile app (keychain/keystore)
2. **Handle token expiration** - tokens expire after 7 days
3. **Include proper error handling** for network requests
4. **Use HTTPS** in production for security
5. **Implement offline caching** for better user experience

## cPanel Deployment Notes

1. Upload all files to your cPanel public_html directory
2. Update `config/config.php` with your production database credentials
3. Ensure your MySQL database is properly configured
4. Set proper file permissions (644 for files, 755 for directories)
5. Update `SITE_URL` in config.php to your actual domain
