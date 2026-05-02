-- Add selected logo and seal columns to ra_bills table
ALTER TABLE ra_bills 
ADD COLUMN selected_logo VARCHAR(100) NULL,
ADD COLUMN selected_seal VARCHAR(100) NULL;