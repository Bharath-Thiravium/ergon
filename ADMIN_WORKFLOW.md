# 🔧 Admin Management Workflow

## Overview

The ERGON system now supports a two-tier admin management system:

1. **System Admins** - Created by Owner for system operations
2. **User Admins** - Existing users promoted to admin roles

## Workflow

### 1. Initial Setup (Owner)

1. **Owner** logs in to the system
2. Goes to **System Admins** section
3. Creates a **System Admin** (no personal info needed)
   - Admin Name: e.g., "HR Admin", "Operations Admin"
   - Admin Email: Login email
   - Permissions: Select system permissions
4. System generates temporary password
5. **System Admin** can now login and create users

### 2. User Creation (System Admin)

1. **System Admin** logs in
2. Goes to **User Management**
3. Creates **Users** with personal information
   - Full name, email, phone, department, etc.
   - Users get temporary passwords
4. Users login and reset their passwords

### 3. User Promotion (Owner)

1. **Owner** can promote existing **Users** to **Admin** roles
2. Goes to **User Admins** section
3. Selects a user and assigns admin permissions
4. User becomes an admin while retaining personal profile

## Key Differences

| Type | Created By | Purpose | Personal Info | Can Be Demoted |
|------|------------|---------|---------------|----------------|
| **System Admin** | Owner | System operations | No | Deactivated only |
| **User Admin** | Owner (promotion) | Department management | Yes | Yes (back to user) |

## Benefits

- **Clear Separation**: System admins vs user admins
- **Scalable**: Owner creates system admins, they create users
- **Flexible**: Users can be promoted/demoted as needed
- **Secure**: System admins are purpose-built for operations

## Database Changes

- Added `is_system_admin` flag to `users` table
- Added `is_system_admin` flag to `admin_positions` table
- System admins are marked separately from promoted users

## Navigation

### Owner Dashboard
- **System Admins**: Create/manage system administrators
- **User Admins**: Promote/demote existing users
- **User Management**: View all users

### System Admin Dashboard
- **User Management**: Create and manage users
- **Department Management**: Organize users
- **Task Management**: Assign and track tasks

## Security Notes

- System admins cannot be demoted, only deactivated
- User admins can be demoted back to regular users
- All admin actions are logged and auditable
- Temporary passwords must be changed on first login