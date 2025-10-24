-- Add missing columns to users table for both local and Hostinger

ALTER TABLE users ADD COLUMN date_of_birth DATE NULL;
ALTER TABLE users ADD COLUMN gender ENUM('male', 'female', 'other') NULL;
ALTER TABLE users ADD COLUMN address TEXT NULL;
ALTER TABLE users ADD COLUMN emergency_contact VARCHAR(20) NULL;
ALTER TABLE users ADD COLUMN designation VARCHAR(100) NULL;
ALTER TABLE users ADD COLUMN joining_date DATE NULL;
ALTER TABLE users ADD COLUMN salary DECIMAL(10,2) NULL;