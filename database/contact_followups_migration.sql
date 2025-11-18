-- Complete Follow-ups Module Rebuild
-- Phase 1: Backup legacy data
-- Phase 2: Drop and recreate tables
-- Phase 3: Migrate data to new structure

-- Create backup tables with expected structure
CREATE TABLE IF NOT EXISTS followups_backup (
    id INT,
    user_id INT,
    task_id INT,
    contact_person VARCHAR(255),
    contact_phone VARCHAR(20),
    company_name VARCHAR(255),
    title VARCHAR(255),
    description TEXT,
    follow_up_date DATE,
    status VARCHAR(20),
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP
);

CREATE TABLE IF NOT EXISTS followup_history_backup (
    id INT,
    followup_id INT,
    action VARCHAR(50),
    old_value TEXT,
    new_value TEXT,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP
);

-- Drop legacy tables
DROP TABLE IF EXISTS followup_history;
DROP TABLE IF EXISTS followups;
DROP TABLE IF EXISTS contacts;

-- Create contacts table
CREATE TABLE contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(255),
    company VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_company (company)
);

-- Create new followups table (standalone only)
CREATE TABLE followups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    contact_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    follow_up_date DATE NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_contact_id (contact_id),
    INDEX idx_follow_date (follow_up_date),
    INDEX idx_status (status)
);

-- Create new followup_history table
CREATE TABLE followup_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    followup_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    old_value TEXT,
    new_value TEXT,
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_followup_id (followup_id)
);

-- Add required columns to tasks table
SET @task_type_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tasks'
    AND COLUMN_NAME = 'type'
);

SET @task_sql = IF(@task_type_exists = 0,
    'ALTER TABLE tasks ADD COLUMN type VARCHAR(50) DEFAULT "task"',
    'SELECT "type column already exists in tasks" as message'
);

PREPARE stmt FROM @task_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add missing columns if they don't exist
SET @assigned_by_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tasks'
    AND COLUMN_NAME = 'assigned_by'
);

SET @assigned_by_sql = IF(@assigned_by_exists = 0,
    'ALTER TABLE tasks ADD COLUMN assigned_by INT',
    'SELECT "assigned_by column already exists" as message'
);

PREPARE stmt FROM @assigned_by_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @deadline_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tasks'
    AND COLUMN_NAME = 'deadline'
);

SET @deadline_sql = IF(@deadline_exists = 0,
    'ALTER TABLE tasks ADD COLUMN deadline DATE',
    'SELECT "deadline column already exists" as message'
);

PREPARE stmt FROM @deadline_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update status enum to include more values
ALTER TABLE tasks MODIFY COLUMN status ENUM('pending', 'assigned', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending';

-- Create indexes
SET @index_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tasks' AND INDEX_NAME = 'idx_task_type');
SET @index_sql = IF(@index_exists = 0, 'CREATE INDEX idx_task_type ON tasks (type)', 'SELECT "idx_task_type already exists" as message');
PREPARE stmt FROM @index_sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @index_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tasks' AND INDEX_NAME = 'idx_task_deadline');
SET @index_sql = IF(@index_exists = 0, 'CREATE INDEX idx_task_deadline ON tasks (deadline)', 'SELECT "idx_task_deadline already exists" as message');
PREPARE stmt FROM @index_sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @index_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tasks' AND INDEX_NAME = 'idx_task_assigned_to');
SET @index_sql = IF(@index_exists = 0, 'CREATE INDEX idx_task_assigned_to ON tasks (assigned_to)', 'SELECT "idx_task_assigned_to already exists" as message');
PREPARE stmt FROM @index_sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Migrate legacy data (only if backup has data)
INSERT INTO contacts (name, phone, company)
SELECT DISTINCT 
    COALESCE(NULLIF(contact_person, ''), 'Unknown Contact'),
    NULLIF(contact_phone, ''),
    NULLIF(company_name, '')
FROM followups_backup 
WHERE (contact_person IS NOT NULL OR contact_phone IS NOT NULL)
    AND EXISTS (SELECT 1 FROM followups_backup LIMIT 1);

-- Migrate standalone followups (only if backup has data)
INSERT INTO followups (user_id, contact_id, title, description, follow_up_date, status, completed_at, created_at)
SELECT 
    fb.user_id,
    c.id,
    fb.title,
    fb.description,
    fb.follow_up_date,
    fb.status,
    fb.completed_at,
    fb.created_at
FROM followups_backup fb
JOIN contacts c ON (fb.contact_person = c.name OR fb.contact_phone = c.phone)
WHERE fb.task_id IS NULL
    AND EXISTS (SELECT 1 FROM followups_backup LIMIT 1);

-- Convert task-linked followups to follow-up tasks (only if backup has data)
UPDATE tasks t
JOIN followups_backup fb ON t.id = fb.task_id
SET t.type = 'followup'
WHERE fb.task_id IS NOT NULL
    AND EXISTS (SELECT 1 FROM followups_backup LIMIT 1);

-- Insert comprehensive sample contacts
INSERT IGNORE INTO contacts (name, phone, email, company) VALUES
('John Smith', '(555) 123-4567', 'john@abccorp.com', 'ABC Corp'),
('Sarah Johnson', '(555) 234-5678', 'sarah@xyzltd.com', 'XYZ Ltd'),
('Mike Davis', '(555) 987-6543', 'mike@techsol.com', 'Tech Solutions'),
('Lisa Brown', '(555) 345-6789', 'lisa@techsolutions.com', 'Tech Solutions'),
('Robert Wilson', '(555) 456-7890', 'robert@globalind.com', 'Global Industries'),
('Emma Thompson', '(555) 567-8901', 'emma@innovate.com', 'Innovate Inc'),
('David Chen', '(555) 678-9012', 'david@startup.io', 'StartupIO'),
('Maria Garcia', '(555) 789-0123', 'maria@consulting.net', 'Garcia Consulting');

-- Insert multiple standalone follow-ups for each contact
INSERT INTO followups (user_id, contact_id, title, description, follow_up_date, status, completed_at, created_at) VALUES
-- John Smith follow-ups
(1, 1, 'Initial proposal discussion', 'Discuss project requirements and timeline', DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'completed', DATE_SUB(NOW(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY)),
(1, 1, 'Follow up on proposal feedback', 'Get feedback on submitted proposal', DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'completed', DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),
(1, 1, 'Contract negotiation call', 'Discuss contract terms and pricing', CURDATE(), 'pending', NULL, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1, 1, 'Final approval meeting', 'Get final sign-off from stakeholders', DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'pending', NULL, NOW()),

-- Sarah Johnson follow-ups
(1, 2, 'Quarterly review meeting', 'Review Q3 performance and discuss Q4 goals', DATE_SUB(CURDATE(), INTERVAL 7 DAY), 'completed', DATE_SUB(NOW(), INTERVAL 6 DAY), DATE_SUB(NOW(), INTERVAL 8 DAY)),
(1, 2, 'Contract renewal discussion', 'Discuss terms for contract renewal', DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'postponed', NULL, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(1, 2, 'Budget planning session', 'Plan budget for next quarter', DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'pending', NULL, NOW()),

-- Mike Davis follow-ups
(1, 3, 'Project kickoff call', 'Initiate new project and set expectations', DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'completed', DATE_SUB(NOW(), INTERVAL 9 DAY), DATE_SUB(NOW(), INTERVAL 11 DAY)),
(1, 3, 'Weekly status update', 'Get project progress update', DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'completed', DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 4 DAY)),
(1, 3, 'Technical review meeting', 'Review technical implementation', CURDATE(), 'in_progress', NULL, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1, 3, 'Delivery timeline discussion', 'Finalize delivery schedule', DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'pending', NULL, NOW()),

-- Lisa Brown follow-ups
(1, 4, 'Requirements gathering', 'Collect detailed project requirements', DATE_SUB(CURDATE(), INTERVAL 6 DAY), 'completed', DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 7 DAY)),
(1, 4, 'Implementation timeline review', 'Review and adjust implementation timeline', DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'pending', NULL, NOW()),

-- Robert Wilson follow-ups
(1, 5, 'Budget approval request', 'Request budget approval for project', DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'pending', NULL, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(1, 5, 'Stakeholder alignment meeting', 'Align all stakeholders on project scope', DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'pending', NULL, NOW());

-- Insert multiple task-linked follow-ups for comprehensive testing
INSERT INTO tasks (title, description, assigned_by, assigned_to, type, priority, deadline, status, created_at) VALUES
-- John Smith tasks
('Follow up: ABC Corp Proposal', 'Call John Smith from ABC Corp regarding the proposal discussion. Phone: (555) 123-4567', 1, 1, 'followup', 'high', CURDATE(), 'assigned', DATE_SUB(NOW(), INTERVAL 2 DAY)),
('Follow up: ABC Corp Contract', 'Follow up on contract signing with John Smith', 1, 1, 'followup', 'high', DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'assigned', NOW()),
('Follow up: ABC Corp Implementation', 'Discuss implementation timeline with John Smith', 1, 1, 'followup', 'medium', DATE_ADD(CURDATE(), INTERVAL 5 DAY), 'assigned', NOW()),

-- Sarah Johnson tasks
('Follow up: XYZ Contract Renewal', 'Follow up with Sarah Johnson about the contract renewal. Company: XYZ Ltd', 1, 1, 'followup', 'medium', DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'assigned', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('Follow up: XYZ Budget Review', 'Review budget allocation with Sarah Johnson', 1, 1, 'followup', 'low', DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'assigned', NOW()),

-- Mike Davis tasks
('Follow up: Tech Solutions Project', 'Check on project status with Mike Davis. Phone: (555) 987-6543', 1, 1, 'followup', 'medium', DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'in_progress', DATE_SUB(NOW(), INTERVAL 3 DAY)),
('Follow up: Tech Solutions Delivery', 'Confirm delivery schedule with Mike Davis', 1, 1, 'followup', 'high', DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'assigned', NOW()),

-- Lisa Brown tasks
('Follow up: Tech Solutions Timeline', 'Follow up with Lisa Brown from Tech Solutions regarding implementation timeline', 1, 1, 'followup', 'low', DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'assigned', DATE_SUB(NOW(), INTERVAL 2 DAY)),
('Follow up: Tech Solutions Requirements', 'Clarify additional requirements with Lisa Brown', 1, 1, 'followup', 'medium', DATE_ADD(CURDATE(), INTERVAL 6 DAY), 'assigned', NOW()),

-- Robert Wilson tasks
('Follow up: Global Industries Budget', 'Call Robert Wilson about the budget approval. Company: Global Industries', 1, 1, 'followup', 'high', DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'assigned', DATE_SUB(NOW(), INTERVAL 3 DAY)),
('Follow up: Global Industries Expansion', 'Discuss expansion plans with Robert Wilson', 1, 1, 'followup', 'medium', DATE_ADD(CURDATE(), INTERVAL 4 DAY), 'assigned', NOW()),

-- Emma Thompson tasks
('Follow up: Innovate Inc Partnership', 'Explore partnership opportunities with Emma Thompson', 1, 1, 'followup', 'medium', DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'assigned', NOW()),
('Follow up: Innovate Inc Demo', 'Schedule product demo for Emma Thompson', 1, 1, 'followup', 'low', DATE_ADD(CURDATE(), INTERVAL 8 DAY), 'assigned', NOW()),

-- David Chen tasks
('Follow up: StartupIO Integration', 'Discuss API integration with David Chen', 1, 1, 'followup', 'high', CURDATE(), 'assigned', NOW()),

-- Maria Garcia tasks
('Follow up: Garcia Consulting Services', 'Explore consulting services with Maria Garcia', 1, 1, 'followup', 'medium', DATE_ADD(CURDATE(), INTERVAL 5 DAY), 'assigned', NOW());

-- Insert comprehensive follow-up history for completed items
INSERT INTO followup_history (followup_id, action, old_value, new_value, notes, created_by, created_at) VALUES
-- History for John Smith's completed follow-ups
(1, 'created', NULL, 'Initial proposal discussion created', 'Follow-up created for proposal discussion', 1, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(1, 'completed', 'pending', 'completed', 'Successfully discussed project requirements and timeline. Client is interested.', 1, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(2, 'created', NULL, 'Follow up on proposal feedback created', 'Follow-up created to get proposal feedback', 1, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(2, 'completed', 'pending', 'completed', 'Received positive feedback. Minor revisions requested.', 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, 'created', NULL, 'Contract negotiation call created', 'Follow-up created for contract discussion', 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),

-- History for Sarah Johnson's follow-ups
(5, 'created', NULL, 'Quarterly review meeting created', 'Follow-up created for Q3 review', 1, DATE_SUB(NOW(), INTERVAL 8 DAY)),
(5, 'completed', 'pending', 'completed', 'Successful quarterly review. Exceeded targets by 15%.', 1, DATE_SUB(NOW(), INTERVAL 6 DAY)),
(6, 'created', NULL, 'Contract renewal discussion created', 'Follow-up created for contract renewal', 1, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(6, 'rescheduled', DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'Rescheduled due to client availability', 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),

-- History for Mike Davis's follow-ups
(8, 'created', NULL, 'Project kickoff call created', 'Follow-up created for project initiation', 1, DATE_SUB(NOW(), INTERVAL 11 DAY)),
(8, 'completed', 'pending', 'completed', 'Project kicked off successfully. Team assigned and timeline set.', 1, DATE_SUB(NOW(), INTERVAL 9 DAY)),
(9, 'created', NULL, 'Weekly status update created', 'Follow-up created for progress check', 1, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(9, 'completed', 'pending', 'completed', 'Project on track. 30% completion achieved.', 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(10, 'created', NULL, 'Technical review meeting created', 'Follow-up created for technical review', 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),

-- History for Lisa Brown's follow-ups
(12, 'created', NULL, 'Requirements gathering created', 'Follow-up created for requirements collection', 1, DATE_SUB(NOW(), INTERVAL 7 DAY)),
(12, 'completed', 'pending', 'completed', 'All requirements gathered and documented. Ready for development.', 1, DATE_SUB(NOW(), INTERVAL 5 DAY));

SELECT 'Follow-ups module rebuild completed with comprehensive sample data!' as status;

-- Display summary of created data
SELECT 
    'Data Summary' as info,
    (SELECT COUNT(*) FROM contacts) as total_contacts,
    (SELECT COUNT(*) FROM followups) as standalone_followups,
    (SELECT COUNT(*) FROM tasks WHERE type = 'followup') as task_followups,
    (SELECT COUNT(*) FROM followup_history) as history_records;