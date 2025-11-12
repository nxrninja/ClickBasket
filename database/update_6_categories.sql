-- ClickBasket Simple 6 Categories Update
-- Updates to: Fashion, Mobile, Beauty, Electronics, Toys, Furniture

USE cpfr_40391125_clickbasket;

-- Backup existing categories (optional)
CREATE TABLE IF NOT EXISTS categories_backup_simple AS SELECT * FROM categories;

-- Clear existing categories
DELETE FROM categories;

-- Reset auto increment
ALTER TABLE categories AUTO_INCREMENT = 1;

-- Insert the 6 requested categories
INSERT INTO categories (name, slug, description, is_active) VALUES
('Fashion', 'fashion', 'Clothing, accessories, and fashion items for all ages', 1),
('Mobile', 'mobile', 'Smartphones, tablets, and mobile accessories', 1),
('Beauty', 'beauty', 'Cosmetics, skincare, and beauty products', 1),
('Electronics', 'electronics', 'Consumer electronics and gadgets', 1),
('Toys', 'toys', 'Toys and games for children and adults', 1),
('Furniture', 'furniture', 'Home and office furniture', 1);

-- Verify the update
SELECT * FROM categories ORDER BY id;
