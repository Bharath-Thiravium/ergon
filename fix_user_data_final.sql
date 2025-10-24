-- Final user data fix - works for both Laragon and Hostinger

-- Update user ID 2 with complete data
UPDATE users SET 
    name = 'Admin User',
    email = 'admin@athenas.co.in',
    role = 'admin',
    status = 'active',
    phone = '1234567890',
    department = 'Administration',
    designation = 'System Administrator',
    joining_date = '2024-01-01',
    salary = 50000.00
WHERE id = 2;

-- Verify the update
SELECT id, name, email, phone, department, designation, joining_date, salary FROM users WHERE id = 2;