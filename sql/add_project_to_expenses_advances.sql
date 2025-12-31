-- Add project_id to expenses and advances tables

ALTER TABLE expenses ADD COLUMN project_id INT NULL AFTER user_id;
ALTER TABLE expenses ADD FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL;

ALTER TABLE advances ADD COLUMN project_id INT NULL AFTER user_id;
ALTER TABLE advances ADD FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL;
