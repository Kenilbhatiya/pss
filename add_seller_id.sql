-- Add seller_id column to products table if it doesn't exist
ALTER TABLE products ADD COLUMN IF NOT EXISTS seller_id INT DEFAULT NULL;

-- Update the existing product with seller ID 3 (which is the logged-in seller's ID)
UPDATE products SET seller_id = 3 WHERE id = 6;

-- Add a foreign key constraint (optional - do this after testing)
-- ALTER TABLE products ADD CONSTRAINT fk_seller_id FOREIGN KEY (seller_id) REFERENCES sellers(id); 