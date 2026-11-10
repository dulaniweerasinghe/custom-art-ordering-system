-- COD Migration: Add COD support fields to orders table
-- Run this on your existing database

ALTER TABLE orders 
ADD COLUMN cod_deposit DECIMAL(10,2) DEFAULT NULL COMMENT '50% deposit paid for COD orders',
ADD COLUMN cod_remaining DECIMAL(10,2) DEFAULT NULL COMMENT 'Remaining 50% to pay on delivery',
ADD COLUMN cod_deposit_paid BOOLEAN DEFAULT FALSE COMMENT 'Whether COD deposit has been paid',
ADD COLUMN delivery_address TEXT DEFAULT NULL COMMENT 'Delivery address for COD orders';

-- Extend payment_status to support COD states
ALTER TABLE orders 
MODIFY COLUMN payment_status ENUM('pending','paid','failed','refunded','cod_deposit_paid') DEFAULT 'pending';

-- Extend payment_method to support cod
ALTER TABLE orders
MODIFY COLUMN payment_method VARCHAR(50) DEFAULT NULL;
