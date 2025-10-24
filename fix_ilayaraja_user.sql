-- Fix Ilayaraja user data and permissions

-- Check current user data
SELECT id, name, email, role, status, department FROM users WHERE email = 'ilayaraja@athenas.co.in';

-- Update Ilayaraja user with proper role and department
UPDATE users SET 
    name = 'Ilayaraja',
    role = 'user',
    status = 'active',
    department = 'IT',
    designation = 'Developer',
    phone = '9876543210',
    joining_date = '2024-01-01'
WHERE email = 'ilayaraja@athenas.co.in';

-- Verify the update
SELECT id, name, email, role, status, department, designation FROM users WHERE email = 'ilayaraja@athenas.co.in';