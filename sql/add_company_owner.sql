-- Add company_owner user type
INSERT INTO users (username, email, password, role, full_name, status, created_at) 
VALUES ('company_owner', 'owner@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'company_owner', 'Company Owner', 'active', NOW())
ON DUPLICATE KEY UPDATE role = 'company_owner';

-- Update existing owner role to company_owner if needed
UPDATE users SET role = 'company_owner' WHERE role = 'owner' AND username LIKE '%owner%';