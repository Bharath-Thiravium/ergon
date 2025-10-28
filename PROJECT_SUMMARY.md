# ergon - Project Summary

## Overview
**ergon** is an enterprise-grade PHP-based Employee Tracker & Task Manager designed for MSMEs (Micro, Small & Medium Enterprises). It provides real-time GPS attendance tracking, task management, and workflow automation with role-based access control.

## Key Features
- **GPS Attendance**: Real-time location-based check-in/out with geo-fence validation
- **Task Management**: Complete lifecycle tracking with progress analytics
- **Workflow Automation**: Leave, advance, and expense request approvals
- **Role-Based Access**: Owner, Admin, and User permissions
- **Security**: JWT authentication, CSRF protection, audit logging

## Technology Stack
- **Backend**: PHP 8.x (MVC Architecture)
- **Frontend**: HTML5, CSS3, JavaScript (Bootstrap 5, jQuery, Chart.js)
- **Database**: MySQL 8.x / MariaDB 10.4+
- **API**: RESTful endpoints for mobile integration
- **Deployment**: GitHub Actions CI/CD to shared hosting

## Architecture
```
ergon_clean/
├── app/
│   ├── config/         # Database, routes, constants
│   ├── controllers/    # Business logic
│   ├── core/          # Framework core
│   ├── models/        # Data layer
│   └── middlewares/   # Authentication & security
├── public/            # Web root & assets
├── views/             # UI templates
└── storage/           # Logs & cache
```

## User Roles
- **Owner**: Full system access, analytics, user management
- **Admin**: Task allocation, approvals, attendance oversight
- **User**: Attendance tracking, task updates, request submissions

## Core Modules
1. **Authentication & Security** - JWT + session hybrid, CSRF protection
2. **GPS Attendance** - Location tracking with geo-fence validation
3. **Leave & Advance** - Request workflows with dual approvals
4. **Expense Management** - Receipt uploads with approval chains
5. **Task Tracker** - Assignment, progress updates, analytics

## Deployment Target
- Shared/Business hosting (Hostinger-compatible)
- Auto-deployment to `/public_html/ergon`
- HTTPS enforcement with SSL

## Status
Production-ready MVC foundation with modular architecture for scalable growth and mobile app integration.