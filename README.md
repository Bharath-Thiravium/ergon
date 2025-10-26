# 🧭 **ERGON - Employee Tracker & Task Manager**

### *Enterprise-Grade PHP Solution for MSMEs*

---

## 🎯 **Core Objectives**

- Real-time **GPS-based attendance** and time tracking
- **Task lifecycle management** with progress analytics
- **Leave, advance, and expense** workflows with approvals
- **Role-based secure access** for Owner, Admin, and User
- **Audit-ready**, scalable, and API-friendly ERP foundation

---

## 🏗️ **System Architecture**

| Layer              | Technology Stack                                        |
| ------------------ | ------------------------------------------------------- |
| **Frontend**       | HTML5, CSS3, JavaScript (Bootstrap 5, jQuery, Chart.js) |
| **Backend**        | PHP 8.x (MVC Pattern)                                   |
| **Database**       | MySQL 8.x / MariaDB 10.4+                               |
| **API Layer**      | RESTful API (for mobile app integration)                |
| **Authentication** | JWT-based + Role-based ACL                              |
| **Security**       | HTTPS, CSRF Tokens, Sanitization, Audit Logs            |
| **Hosting Target** | Shared or Business Hosting (Hostinger-compatible)       |
| **Deployment**     | GitHub Actions + Auto-deploy to `/public_html/ergon`    |

---

## 👥 **User Roles & Permissions**

| Role      | Description           | Key Access                                    |
| --------- | --------------------- | --------------------------------------------- |
| **Owner** | System controller     | Dashboards, Analytics, User Mgmt, Settings    |
| **Admin** | Operational manager   | Task allocation, Approvals, Attendance review |
| **User**  | Field or office staff | Attendance, Tasks, Requests, Progress updates |

---

## 📦 **Core Modules**

### 1. 🔐 **Authentication & Security**
- JWT + session-based hybrid auth
- Secure password hashing (bcrypt)
- Session timeout + IP-based session validation
- CSRF & XSS protection (token + input sanitation)
- Centralized `AuthMiddleware` for ACL enforcement

### 2. 📍 **GPS Attendance Tracker**
- HTML5 Geolocation API / Mobile GPS capture
- Geo-fence validation (distance from assigned site)
- Time-in / Time-out with map snapshot logging
- Admin override with remarks & digital signature
- Daily summary and attendance anomaly detection

### 3. 📝 **Leave & Advance Requests**
- Dynamic form (type, duration, reason, attachments)
- Dual-level approval (Admin → Owner)
- Real-time request status tracking
- Notification trigger (Email / SMS optional)
- Rejection reasons and revision history

### 4. 💰 **Expense Management**
- Receipt uploads (PDF, JPG, PNG)
- Categorization (Travel, Food, Material, etc.)
- Validation & approval workflows
- Monthly expense summary + export (CSV/PDF)
- Budget limit alerts for Admins

### 5. ⚙️ **Task Allocation & Progress Tracker**
- Admin assigns daily/weekly tasks
- Task types: Checklist, Milestone, Timed, or Ad-hoc
- Users post progress updates with % completion, attachments, blockers
- Task history + change tracking
- Analytics: Productivity index, task load balance, completion trends

---

## 🛡️ **Security Features**

- HTTPS + SSL enforcement
- PDO prepared statements (no raw queries)
- Server-side input filtering (FILTER_SANITIZE_*)
- Rate-limited login attempts (throttling)
- OTP or Email verification for critical actions
- Config file protection (`.env` or constants.php)

---

## 📱 **Mobile Integration (Optional)**

- RESTful API endpoints for mobile clients
- Push notifications for new tasks/approvals
- Offline attendance cache (sync later)
- Token-based session authentication
- Flutter / React Native compatible API format

---

## ⚙️ **Deployment & CI/CD Workflow**

### **GitHub Actions Workflow**
- **Branches**: `main` (stable), `dev` (staging), `hotfix/*`
- Auto-linting & syntax validation on push
- Test environment deploy on `dev` merge
- Production deploy to `/public_html/ergon` on `main` merge
- Backup trigger before deployment

---

## 🧰 **Future Enhancements**

- AI-based productivity scoring (task completion patterns)
- Integration with Google Workspace (Calendar, Sheets)
- WebSocket live task updates
- QR-based check-in system for indoor employees
- Multi-company (tenant-based) architecture

---

### ✅ **Summary**

> **Ergon** is designed as a **modular, secure, and analytics-driven** employee tracker tailored for MSMEs.
> It offers **GPS attendance**, **task tracking**, **leave/expense automation**, and **real-time dashboards** — all built on a **robust PHP MVC foundation** optimized for shared hosting and scalable growth.

## 🚀 **Installation**

1. Clone the repository
2. Configure database settings in `app/config/database.php`
3. Set up web server to point to `public/` directory
4. Import database schema
5. Configure environment settings

## 📁 **Project Structure**

```
ergon_clean/
├── app/
│   ├── config/         # Configuration files
│   ├── controllers/    # MVC Controllers
│   ├── core/          # Core framework files
│   ├── helpers/       # Helper classes
│   ├── middlewares/   # Middleware classes
│   └── models/        # Data models
├── public/            # Web root directory
│   ├── assets/        # CSS, JS, images
│   └── uploads/       # File uploads
├── storage/           # Logs and cache
├── views/             # View templates
└── README.md
```

## 🔧 **Configuration**

- Database: Configure in `app/config/database.php`
- Routes: Define in `app/config/routes.php`
- Constants: Set in `app/config/constants.php`
- Environment: Auto-detected in `app/config/environment.php`