-- Legacy Follow-ups Backup Script
-- Run this before deleting legacy tables

-- Backup existing followups table
CREATE TABLE followups_backup_legacy AS SELECT * FROM followups;

-- Backup existing followup_history table  
CREATE TABLE followup_history_backup_legacy AS SELECT * FROM followup_history;

-- Export data summary
SELECT 
    'Legacy Backup Complete' as status,
    (SELECT COUNT(*) FROM followups_backup_legacy) as followups_count,
    (SELECT COUNT(*) FROM followup_history_backup_legacy) as history_count;