-- Fix followup_history table schema
-- Change action_type column to action to match application code

ALTER TABLE `followup_history` CHANGE `action_type` `action` varchar(50) NOT NULL;