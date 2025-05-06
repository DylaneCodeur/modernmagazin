-- Create database
CREATE DATABASE IF NOT EXISTS e_commerce_db;
USE e_commerce_db;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    is_admin TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    image_url VARCHAR(255),
    stock INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert initial admin user (password: admin123)
INSERT INTO users (username, email, password, is_admin) 
VALUES ('admin', 'admin@example.com', '$2y$10$6YGW3RhQiJClvs6ifQ3tNOO7PTX7xFiAZguRwHMyfRnzUyQb3r9hm', 1);

-- Insert test user (password: user123)
INSERT INTO users (username, email, password, is_admin) 
VALUES ('user', 'user@example.com', '$2y$10$QlE8SrJH0MRdpKVJnmD3JO7nKQZOYSGvnkhbD7LTr.R6hTavdxEqq', 0);

-- Insert sample products
INSERT INTO products (user_id, name, description, price, image_url, stock) VALUES 
(1, 'Smartphone Pro Max', 'Latest smartphone with advanced features and long battery life', 899.99, 'https://picsum.photos/id/1/500/500', 15),
(1, 'Ultra HD Smart TV', '65-inch 4K television with smart capabilities', 1299.99, 'https://picsum.photos/id/2/500/500', 8),
(1, 'Wireless Headphones', 'Noise-cancelling wireless headphones with 30-hour battery life', 199.99, 'https://picsum.photos/id/3/500/500', 25),
(1, 'Gaming Laptop', 'High-performance gaming laptop with RGB keyboard', 1599.99, 'https://picsum.photos/id/4/500/500', 12),
(1, 'Fitness Smartwatch', 'Waterproof fitness tracker with heart rate monitoring', 149.99, 'https://picsum.photos/id/5/500/500', 30),
(1, 'Digital Camera', 'Professional-grade digital camera with 4K video capability', 799.99, 'https://picsum.photos/id/6/500/500', 10),
(1, 'Bluetooth Speaker', 'Portable waterproof bluetooth speaker with 24-hour playtime', 89.99, 'https://picsum.photos/id/7/500/500', 20),
(1, 'Coffee Maker', 'Programmable coffee maker with built-in grinder', 129.99, 'https://picsum.photos/id/8/500/500', 15),
(1, 'Robot Vacuum', 'Smart robot vacuum with mapping technology', 349.99, 'https://picsum.photos/id/9/500/500', 18),
(1, 'Tablet Pro', '10-inch tablet with high-resolution display and stylus support', 499.99, 'https://picsum.photos/id/10/500/500', 22);