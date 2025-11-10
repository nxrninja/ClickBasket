-- Create test orders for ClickBasket
-- Replace USER_ID with your actual user ID (usually 1 for the first user)

-- Test Order 1 - COD Order
INSERT INTO orders (user_id, order_number, total_amount, discount_amount, tax_amount, final_amount, payment_method, payment_status, order_status, created_at) 
VALUES (1, 'CB20251110001', 299.99, 0.00, 0.00, 299.99, 'cod', 'pending', 'pending', NOW());

-- Get the order ID (this will be the last inserted ID)
SET @order_id_1 = LAST_INSERT_ID();

-- Test Order Items for Order 1
INSERT INTO order_items (order_id, product_id, product_title, product_price, quantity) 
VALUES 
(@order_id_1, 1, 'Premium Web Template', 149.99, 1),
(@order_id_1, 2, 'Mobile App UI Kit', 149.99, 1);

-- Test Order 2 - Completed Order
INSERT INTO orders (user_id, order_number, total_amount, discount_amount, tax_amount, final_amount, payment_method, payment_status, order_status, created_at) 
VALUES (1, 'CB20251110002', 199.99, 20.00, 0.00, 179.99, 'card', 'completed', 'completed', DATE_SUB(NOW(), INTERVAL 2 DAY));

SET @order_id_2 = LAST_INSERT_ID();

-- Test Order Items for Order 2
INSERT INTO order_items (order_id, product_id, product_title, product_price, quantity) 
VALUES 
(@order_id_2, 3, 'E-commerce Dashboard', 199.99, 1);

-- Test Order 3 - Processing Order
INSERT INTO orders (user_id, order_number, total_amount, discount_amount, tax_amount, final_amount, payment_method, payment_status, order_status, created_at) 
VALUES (1, 'CB20251110003', 99.99, 0.00, 0.00, 99.99, 'paypal', 'completed', 'processing', DATE_SUB(NOW(), INTERVAL 1 DAY));

SET @order_id_3 = LAST_INSERT_ID();

-- Test Order Items for Order 3
INSERT INTO order_items (order_id, product_id, product_title, product_price, quantity) 
VALUES 
(@order_id_3, 4, 'Landing Page Template', 99.99, 1);

-- Create billing information for COD order (if order_billing table exists)
INSERT IGNORE INTO order_billing (order_id, billing_name, billing_email, billing_phone, billing_address, billing_city, billing_state, billing_zip, billing_country) 
VALUES 
(@order_id_1, 'John Doe', 'john@example.com', '+1234567890', '123 Main Street, Apt 4B', 'New York', 'NY', '10001', 'United States');

-- Verify the data was inserted
SELECT 'Orders created:' as message, COUNT(*) as count FROM orders WHERE user_id = 1;
SELECT 'Order items created:' as message, COUNT(*) as count FROM order_items WHERE order_id IN (SELECT id FROM orders WHERE user_id = 1);
