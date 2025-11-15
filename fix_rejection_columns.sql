-- Add rejection_reason column to advances table (the one that's likely missing it)
ALTER TABLE `advances` ADD COLUMN `rejection_reason` TEXT NULL;