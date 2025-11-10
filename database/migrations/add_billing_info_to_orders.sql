-- Migration: Add billing information fields to orders table
-- Date: 2025-11-10
-- Description: Add billing fields to support Cash on Delivery and other payment methods

ALTER TABLE orders ADD COLUMN billing_name VARCHAR(100) AFTER order_status;
ALTER TABLE orders ADD COLUMN billing_email VARCHAR(100) AFTER billing_name;
ALTER TABLE orders ADD COLUMN billing_phone VARCHAR(20) AFTER billing_email;
ALTER TABLE orders ADD COLUMN billing_address TEXT AFTER billing_phone;
ALTER TABLE orders ADD COLUMN billing_city VARCHAR(50) AFTER billing_address;
ALTER TABLE orders ADD COLUMN billing_state VARCHAR(50) AFTER billing_city;
ALTER TABLE orders ADD COLUMN billing_zip VARCHAR(20) AFTER billing_state;
ALTER TABLE orders ADD COLUMN billing_country VARCHAR(50) AFTER billing_zip;

-- Add index for better performance on order lookups
CREATE INDEX idx_orders_payment_method ON orders(payment_method);
CREATE INDEX idx_orders_order_status ON orders(order_status);
CREATE INDEX idx_orders_payment_status ON orders(payment_status);

-- Add missing total_price column to order_items if not exists
ALTER TABLE order_items ADD COLUMN total_price DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER quantity;
