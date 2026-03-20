# PostgreSQL Connection Setup Guide

## Current Status
- ✅ PostgreSQL 17 is installed at: `C:\Program Files\PostgreSQL\17\`
- ❌ PostgreSQL service is STOPPED
- ✅ Configuration updated to use localhost (127.0.0.1)

## Solution Options

### Option 1: Start PostgreSQL Service (RECOMMENDED)
**Run Command Prompt as Administrator** and execute:
```cmd
net start postgresql-x64-17
```

### Option 2: Start via Services Manager
1. Press `Win + R`, type `services.msc`
2. Find "postgresql-x64-17" service
3. Right-click → Start

### Option 3: Start via PostgreSQL Service Manager
1. Go to: `C:\Program Files\PostgreSQL\17\bin\`
2. Run `pg_ctl.exe` as administrator

## Verification Steps

### 1. Check if PostgreSQL is Running
```cmd
netstat -an | findstr :5432
```
Should show: `TCP    127.0.0.1:5432         0.0.0.0:0              LISTENING`

### 2. Test Connection
```cmd
cd "C:\Program Files\PostgreSQL\17\bin"
psql -h 127.0.0.1 -p 5432 -U postgres -d modernsap
```

### 3. Run Our Test Script
```cmd
php test_local_postgres.php
```

## Database Setup (if needed)

If database 'modernsap' doesn't exist:
```sql
-- Connect as postgres user first
psql -h 127.0.0.1 -p 5432 -U postgres

-- Create database
CREATE DATABASE modernsap;

-- Grant permissions
GRANT ALL PRIVILEGES ON DATABASE modernsap TO postgres;
```

## Next Steps After PostgreSQL Starts
1. ✅ Verify connection works
2. ✅ Check database schema/tables
3. ✅ Test data sync functionality
4. ✅ Update sync service to use correct config

## Current Configuration (.env)
```
SAP_PG_HOST=127.0.0.1
SAP_PG_PORT=5432
SAP_PG_DB=modernsap
SAP_PG_USER=postgres
SAP_PG_PASS=mango
```

## Troubleshooting
- **Connection refused**: PostgreSQL service not running
- **Authentication failed**: Wrong username/password
- **Database not found**: Database 'modernsap' doesn't exist
- **Permission denied**: User doesn't have access to database