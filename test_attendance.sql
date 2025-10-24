-- Check if attendance data is being saved
SELECT * FROM attendance WHERE user_id = 13 ORDER BY created_at DESC LIMIT 10;