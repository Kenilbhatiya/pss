-- Create database (if not already created)
CREATE DATABASE IF NOT EXISTS plant_nursery;
USE plant_nursery;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20),
    user_type ENUM('buyer', 'seller', 'admin') DEFAULT 'buyer',
    status TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create user_addresses table for multiple addresses
CREATE TABLE IF NOT EXISTS user_addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255),
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    zip_code VARCHAR(20) NOT NULL,
    country VARCHAR(100) DEFAULT 'India',
    is_default BOOLEAN DEFAULT 0,
    address_type ENUM('home', 'work', 'other') DEFAULT 'home',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    image_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    sale_price DECIMAL(10, 2),
    stock_quantity INT NOT NULL DEFAULT 0,
    reorder_level INT NOT NULL DEFAULT 10,
    image_path VARCHAR(255),
    category_id INT,
    featured BOOLEAN DEFAULT 0,
    status ENUM('in_stock', 'out_of_stock', 'coming_soon') DEFAULT 'in_stock',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Create cart table
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Create orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    username VARCHAR(100),
    product_name VARCHAR(255),
    total_amount DECIMAL(10, 2) NOT NULL,
    shipping_address TEXT NOT NULL,
    billing_address TEXT,
    payment_method VARCHAR(50),
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    delivery_date DATE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create order_items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Create testimonials table
CREATE TABLE IF NOT EXISTS testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    image_path VARCHAR(255),
    comment TEXT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Newsletter subscribers table
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) NOT NULL UNIQUE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Settings table
CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample data for categories
INSERT INTO categories (name, description, image_path) VALUES
('Indoor Plants', 'Plants that thrive indoors with minimal sunlight', 'images/indoor plant.jpg'),
('Outdoor Plants', 'Plants perfect for your garden or patio', 'images/outdoor plant.jpg'),
('Succulents', 'Low-maintenance plants that store water in their leaves', 'images/succulents.jpg'),
('Flowering Plants', 'Plants that produce beautiful flowers', 'images/flowering plant.jpg');

-- Insert sample data for products
INSERT INTO products (name, description, price, stock_quantity, image_path, category_id, featured) VALUES
('Peace Lily', 'The Peace Lily is a popular indoor plant known for its beautiful white flowers and air-purifying qualities.', 29.99, 50, 'images/peace lily.jpg', 1, 1),
('Snake Plant', 'The Snake Plant is one of the most tolerant indoor plants available, making it perfect for beginners.', 24.99, 75, 'image/snake.jpg', 1, 1),
('Aloe Vera', 'Aloe Vera is a succulent plant species that has been used for centuries for its healing properties.', 19.99, 100, 'images/aloe vera.jpg', 3, 1),
('Spider Plant', 'The Spider Plant is a popular houseplant known for its arching leaves and tiny plantlets.', 22.99, 60, 'images/spider.jpg', 1, 0),
('Monstera Deliciosa', 'The Monstera Deliciosa, or Swiss Cheese Plant, is known for its large, perforated leaves.', 49.99, 30, 'images/monstera.jpg', 1, 1);

-- Insert sample testimonials
INSERT INTO testimonials (name, image_path, comment, rating) VALUES
('Sarah Johnson', 'images/testimonials/person1.jpg', 'I received my Peace Lily in perfect condition. It\'s been thriving in my apartment and has already produced two beautiful flowers!', 5),
('Mike Thompson', 'images/testimonials/person2.jpg', 'The customer service was outstanding. When one of my plants arrived damaged, they immediately shipped a replacement.', 5);

-- Insert default settings
INSERT INTO settings (name, value) VALUES 
('site_name', 'Plant Nursery'),
('site_email', 'info@plantnursery.com'),
('currency', 'USD'),
('shipping_cost', '10.00'),
('tax_rate', '7.00'),
('min_order_amount', '20.00'),
('maintenance_mode', '0');

-- Insert seller user (password: admin123)
INSERT INTO users (username, email, password, first_name, last_name, user_type) VALUES
('seller', 'seller@plantnursery.com', '$2y$10$8zT2sbw3Nk6L0QZ1ZZfX9.Fs4JHxZSrfHyQaD.7uxUUwJNfgwQUt2', 'Seller', 'User', 'seller'); 