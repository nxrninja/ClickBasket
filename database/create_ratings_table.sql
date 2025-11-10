-- Create ratings and reviews table
CREATE TABLE IF NOT EXISTS product_ratings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review_title VARCHAR(200),
    review_text TEXT,
    is_verified_purchase BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_product_rating (user_id, product_id)
);

-- Create index for better performance
CREATE INDEX idx_product_ratings_product_id ON product_ratings(product_id);
CREATE INDEX idx_product_ratings_rating ON product_ratings(rating);
CREATE INDEX idx_product_ratings_created_at ON product_ratings(created_at);

-- Add average rating and rating count columns to products table
ALTER TABLE products 
ADD COLUMN average_rating DECIMAL(3,2) DEFAULT 0.00,
ADD COLUMN rating_count INT DEFAULT 0;

-- Create index for rating columns
CREATE INDEX idx_products_average_rating ON products(average_rating);
CREATE INDEX idx_products_rating_count ON products(rating_count);
