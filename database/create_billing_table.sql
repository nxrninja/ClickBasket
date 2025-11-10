-- Create order_billing table to store billing information
CREATE TABLE IF NOT EXISTS order_billing (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    billing_name VARCHAR(100) NOT NULL,
    billing_email VARCHAR(100) NOT NULL,
    billing_phone VARCHAR(20) NOT NULL,
    billing_address TEXT,
    billing_city VARCHAR(50),
    billing_state VARCHAR(50),
    billing_zip VARCHAR(20),
    billing_country VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- Create index for better performance
CREATE INDEX idx_order_billing_order_id ON order_billing(order_id);
