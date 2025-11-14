-- Remove evening_updates table and related data
-- This script removes all evening update functionality from the database

-- Drop the evening_updates table if it exists
DROP TABLE IF EXISTS evening_updates;

-- Remove any evening update related columns from other tables if they exist
-- (Currently there are no such columns, but this is for future safety)

-- Clean up any evening update related data from other tables
-- (Currently not needed, but included for completeness)

-- Note: This script is safe to run multiple times