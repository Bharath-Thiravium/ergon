-- ERGON Enhanced Database Schema
-- Employee Tracker & Task Manager with Mobile & Push Notifications

-- Add new columns to existing tables (ignore errors if columns exist)
ALTER TABLE leaves ADD COLUMN remarks TEXT;
ALTER TABLE leaves ADD COLUMN attachment VARCHAR(255);
ALTER TABLE expenses ADD COLUMN date DATE;

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(150) NOT NULL,
  message TEXT NOT NULL,
  link VARCHAR(255),
  is_read TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- FCM device tokens for push notifications
CREATE TABLE IF NOT EXISTS user_devices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  fcm_token VARCHAR(255) NOT NULL,
  device_type ENUM('android', 'ios', 'web') DEFAULT 'android',
  device_info TEXT,
  last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_user_token (user_id, fcm_token),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Enhanced attendance with geofencing and anti-fraud
ALTER TABLE attendance ADD COLUMN client_uuid VARCHAR(36);
ALTER TABLE attendance ADD COLUMN distance_meters INT DEFAULT 0;
ALTER TABLE attendance ADD COLUMN is_valid TINYINT(1) DEFAULT 1;
ALTER TABLE attendance ADD COLUMN photo_path VARCHAR(255);
ALTER TABLE attendance ADD COLUMN ip_address VARCHAR(45);

-- Attendance conflicts for manual review
CREATE TABLE IF NOT EXISTS attendance_conflicts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  attendance_id INT NOT NULL,
  conflict_type ENUM('duplicate', 'location_mismatch', 'time_anomaly') NOT NULL,
  details TEXT,
  resolved TINYINT(1) DEFAULT 0,
  resolved_by INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (attendance_id) REFERENCES attendance(id),
  FOREIGN KEY (resolved_by) REFERENCES users(id)
);

-- Task dependencies and SLA
ALTER TABLE tasks ADD COLUMN depends_on_task_id INT;
ALTER TABLE tasks ADD COLUMN sla_hours INT DEFAULT 24;
ALTER TABLE tasks ADD COLUMN progress INT DEFAULT 0;
ALTER TABLE tasks ADD COLUMN parent_task_id INT;
ALTER TABLE tasks ADD COLUMN task_type ENUM('task', 'subtask', 'checklist') DEFAULT 'task';

-- Task updates/progress history
CREATE TABLE IF NOT EXISTS task_updates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  task_id INT NOT NULL,
  user_id INT NOT NULL,
  progress INT NOT NULL,
  comment TEXT,
  attachments TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Offline sync queue
CREATE TABLE IF NOT EXISTS sync_queue (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  action_type ENUM('attendance', 'task_update', 'leave', 'expense') NOT NULL,
  data JSON NOT NULL,
  client_uuid VARCHAR(36) NOT NULL,
  synced TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  synced_at TIMESTAMP NULL,
  UNIQUE KEY unique_client_action (client_uuid, action_type),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Geofence locations
CREATE TABLE IF NOT EXISTS geofence_locations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  latitude DECIMAL(10, 8) NOT NULL,
  longitude DECIMAL(11, 8) NOT NULL,
  radius_meters INT DEFAULT 100,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default geofence location
INSERT IGNORE INTO geofence_locations (name, latitude, longitude, radius_meters) 
VALUES ('Main Office', 0.0, 0.0, 200);

-- Add indexes for performance
CREATE INDEX idx_notifications_user_read ON notifications(user_id, is_read);
CREATE INDEX idx_attendance_user_date ON attendance(user_id, created_at);
CREATE INDEX idx_tasks_assigned_status ON tasks(assigned_to, status);
CREATE INDEX idx_sync_queue_user_synced ON sync_queue(user_id, synced);