# Admin Invite + Management System
## iReserve - UPSI Facility Booking System

---

## 📖 Overview

A comprehensive **Admin Invite and Management** feature for the iReserve facility booking system at UPSI. This system allows faculty-based admin organization, secure invite-based registration, and robust admin lifecycle management.

### Key Features

✅ **Faculty-Based Organization** - Each admin belongs to one faculty  
✅ **Invite-Based Registration** - Secure token-based admin onboarding  
✅ **Soft Delete** - Admins deactivated, not deleted  
✅ **Rate Limiting** - Max 5 invites per admin per hour  
✅ **Last-Admin Protection** - Cannot deactivate last active admin in faculty  
✅ **Comprehensive Audit Logging** - All actions tracked  
✅ **CSRF Protection** - All forms protected against CSRF attacks  
✅ **Password Strength Validation** - Enforces strong passwords  
✅ **Prepared Statements** - All queries use prepared statements (SQL injection safe)  
✅ **Session-Based Auth** - Secure session management  

---

## 📁 Files Created

### 1. Database Schema
- **`admin_management_schema.sql`** - Complete database schema with:
  - `faculties` table
  - `users` table modifications
  - `admin_invites` table
  - `audit_logs` table
  - Sample seed data (6 faculties, 12 test admins)

### 2. PHP Backend Files

#### Helper Functions
- **`includes/admin_helpers.php`** - Core utility functions:
  - `current_admin()` - Get current admin session data
  - `count_active_admins()` - Count active admins per faculty
  - `is_same_faculty()` - Verify faculty membership
  - `audit_log()` - Log administrative actions
  - `generate_csrf_token()` - Generate CSRF tokens
  - `validate_csrf_token()` - Validate CSRF tokens
  - `check_invite_rate_limit()` - Rate limit checking
  - `validate_email()` - Email format validation
  - `validate_password_strength()` - Password strength validation
  - Additional helper functions

#### Admin Pages
- **`admin/admin_create_invite.php`** - Create admin invitations
  - Email validation
  - Rate limiting enforcement
  - Registration link generation
  - Email notification integration
  
- **`admin/admin_register.php`** - Admin registration form
  - Token validation
  - One-time use enforcement
  - Password strength requirements
  - Account creation
  
- **`admin/admin_list.php`** - Admin management dashboard
  - Faculty-scoped admin list
  - Statistics cards
  - Pending invites display
  - Action buttons
  
- **`admin/admin_deactivate.php`** - Admin deactivation handler
  - Self-deactivation prevention
  - Last-admin protection
  - Audit logging

### 3. Frontend Files

#### CSS
- **`assets/css/admin_management.css`** - Professional styling:
  - Responsive grid layout
  - Modern card designs
  - Gradient statistics cards
  - Table styling
  - Form styling
  - Alert/notification styles
  - Mobile-responsive design

#### JavaScript
- **`assets/js/admin_management.js`** - Client-side functionality:
  - Email validation
  - Password strength checking
  - Form validation
  - Copy-to-clipboard
  - Real-time password matching
  - Notification system
  - Auto-trim inputs

### 4. Documentation
- **`ADMIN_MANAGEMENT_TESTING.md`** - Comprehensive testing guide:
  - Setup instructions
  - Test workflows
  - Test accounts
  - Security testing
  - Troubleshooting
  - Expected results

---

## 🗄️ Database Schema

### Tables

#### `faculties`
```sql
- faculty_id (INT, PK, AUTO_INCREMENT)
- name (VARCHAR 255)
- created_at (TIMESTAMP)
```

#### `users` (Modified)
```sql
- id (INT, PK, AUTO_INCREMENT)
- name (VARCHAR 255)
- email (VARCHAR 255, UNIQUE)
- password (VARCHAR 255)
- role (ENUM: 'student', 'admin')
- faculty_id (INT, FK → faculties)
- is_active (TINYINT 1, DEFAULT 1)
- last_login (TIMESTAMP NULL)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

#### `admin_invites`
```sql
- token (CHAR 32, PK)
- email (VARCHAR 255)
- faculty_id (INT, FK → faculties)
- created_by (INT, FK → users)
- created_at (TIMESTAMP)
- used (TINYINT 1, DEFAULT 0)
- used_at (TIMESTAMP NULL)
- used_by (INT NULL, FK → users)
```

#### `audit_logs`
```sql
- id (INT, PK, AUTO_INCREMENT)
- actor_id (INT NULL, FK → users)
- action (VARCHAR 100)
- target (VARCHAR 255 NULL)
- details (TEXT NULL)
- ip (VARCHAR 45 NULL)
- created_at (TIMESTAMP)
```

---

## 🚀 Installation

### Step 1: Upload Files

Upload all files to your iReserve installation directory:

```
ireserve/
├── admin_management_schema.sql
├── ADMIN_MANAGEMENT_TESTING.md
├── includes/
│   └── admin_helpers.php
├── admin/
│   ├── admin_create_invite.php
│   ├── admin_register.php
│   ├── admin_list.php
│   └── admin_deactivate.php
└── assets/
    ├── css/
    │   └── admin_management.css
    └── js/
        └── admin_management.js
```

### Step 2: Run Database Schema

Execute the SQL schema:

```bash
mysql -u root -p ireserve_db < admin_management_schema.sql
```

Or via phpMyAdmin:
1. Open phpMyAdmin
2. Select `ireserve_db` database
3. Go to "Import" tab
4. Upload `admin_management_schema.sql`
5. Click "Go"

### Step 3: Verify Installation

Check that tables were created:

```sql
SHOW TABLES;
```

Should include:
- faculties
- users (modified)
- admin_invites
- audit_logs

### Step 4: Test Login

Login with test account:
- **Email:** `ahmad.hassan@upsi.edu.my`
- **Password:** `Admin@123`

---

## 📝 Usage Guide

### Creating Admin Invites

1. Login as admin
2. Navigate to **Admin Management** (or go to `/admin/admin_list.php`)
3. Click **"Create Invite"** button
4. Enter email address
5. Click **"Send Invitation"**
6. Share the registration link with the new admin

### Registering as Admin

1. Receive invitation email with registration link
2. Click link or open in browser
3. Fill in:
   - Full name
   - Password (must meet requirements)
   - Confirm password
4. Click **"Complete Registration"**
5. Login with new credentials

### Managing Admins

#### View Admins
- Go to **Admin List** page
- See all admins in your faculty
- View statistics (active, pending invites, total)

#### Deactivate Admin
- Click **"Deactivate"** button next to admin
- Confirm action
- Admin will be marked inactive

**Important Restrictions:**
- ❌ Cannot deactivate yourself
- ❌ Cannot deactivate last active admin in faculty

---

## 🔒 Security Features

### Authentication & Authorization
- ✅ Session-based authentication
- ✅ Faculty-scoped access control
- ✅ Role-based permissions (admin only)

### Input Validation
- ✅ Email format validation
- ✅ Password strength requirements
- ✅ Token format validation
- ✅ CSRF token validation

### Database Security
- ✅ Prepared statements (all queries)
- ✅ SQL injection protection
- ✅ XSS prevention (output escaping)

### Business Logic Protection
- ✅ Rate limiting (5 invites/hour)
- ✅ One-time use tokens
- ✅ Self-deactivation prevention
- ✅ Last-admin protection
- ✅ Cross-faculty isolation

### Audit & Compliance
- ✅ All actions logged to audit_logs
- ✅ IP address tracking
- ✅ Timestamp tracking
- ✅ Actor/target tracking

---

## 🧪 Testing

See **`ADMIN_MANAGEMENT_TESTING.md`** for comprehensive testing instructions.

### Quick Test

```bash
# 1. Login as admin
Email: ahmad.hassan@upsi.edu.my
Password: Admin@123

# 2. Create invite
Go to: /admin/admin_create_invite.php
Email: test@upsi.edu.my

# 3. Register new admin
Copy registration link
Open in incognito window
Complete registration

# 4. Verify
Login as new admin
Check admin list
```

---

## 📊 Sample Data

### Test Admin Accounts

All test accounts use password: **`Admin@123`**

| Faculty | Email | Name |
|---------|-------|------|
| Computing | ahmad.hassan@upsi.edu.my | Dr. Ahmad Hassan |
| Computing | siti.nurhaliza@upsi.edu.my | Dr. Siti Nurhaliza |
| Business | wong.wei@upsi.edu.my | Prof. Wong Wei Liang |
| Business | kavitha.devi@upsi.edu.my | Dr. Kavitha Devi |
| Education | rozita.abdullah@upsi.edu.my | Dr. Rozita Abdullah |
| Education | lee.chong@upsi.edu.my | Mr. Lee Chong Wei |
| Science | azman.ibrahim@upsi.edu.my | Dr. Azman Ibrahim |
| Science | farah.liyana@upsi.edu.my | Dr. Farah Liyana |
| Arts | nurul.izzah@upsi.edu.my | Prof. Nurul Izzah |
| Arts | raja.mokhtar@upsi.edu.my | Dr. Raja Mokhtar |
| Music | ahmad.nawab@upsi.edu.my | Prof. Ahmad Nawab |
| Music | sheila.majid@upsi.edu.my | Ms. Sheila Majid |

---

## 🐛 Troubleshooting

### Common Issues

#### 1. "Database error" when creating invite
**Solution:** Check that `admin_invites` table exists and `admin_helpers.php` is included.

#### 2. Registration link shows "Invalid token"
**Solution:** Ensure token is exactly 32 hexadecimal characters. Check database for matching token.

#### 3. Cannot deactivate admin
**Check:**
- Are you trying to deactivate yourself? (Not allowed)
- Is this the last active admin? (Not allowed)
- Do you have permission (same faculty)?

#### 4. Rate limit not working
**Solution:** Check that `check_invite_rate_limit()` is called and `admin_invites` table has created_at timestamps.

#### 5. Email not sending
**Solution:** 
- Check `includes/email_helper.php` exists and configured
- Registration link is displayed on screen for manual sharing
- Email functionality is optional

---

## 📋 Password Requirements

New admin passwords must contain:
- ✅ At least 8 characters
- ✅ At least one uppercase letter (A-Z)
- ✅ At least one lowercase letter (a-z)
- ✅ At least one number (0-9)
- ✅ At least one special character (!@#$%^&*)

---

## 🔄 Workflow Diagram

```
┌─────────────────┐
│  Current Admin  │
└────────┬────────┘
         │
         ▼
┌─────────────────────┐
│  Create Invite      │
│  (Rate Limited)     │
└────────┬────────────┘
         │
         ▼
┌─────────────────────┐
│  Generate Token     │
│  Send Email         │
└────────┬────────────┘
         │
         ▼
┌─────────────────────┐
│  New User Receives  │
│  Registration Link  │
└────────┬────────────┘
         │
         ▼
┌─────────────────────┐
│  Complete Form      │
│  (Validate Token)   │
└────────┬────────────┘
         │
         ▼
┌─────────────────────┐
│  Create Account     │
│  Mark Token Used    │
└────────┬────────────┘
         │
         ▼
┌─────────────────────┐
│  Login as Admin     │
│  Access Dashboard   │
└─────────────────────┘
```

---

## 📞 Support & Maintenance

### Code Maintenance

All files include comprehensive comments explaining:
- Purpose of each function
- Security considerations
- Validation logic
- Database queries

### Adding New Features

To extend functionality:

1. **Add new admin actions:**
   - Create new PHP file in `admin/`
   - Include `admin_helpers.php`
   - Use `current_admin()` for auth
   - Add audit logging with `audit_log()`

2. **Modify permissions:**
   - Update helper functions in `admin_helpers.php`
   - Add new validation functions as needed

3. **Extend audit logging:**
   - Call `audit_log()` with new action types
   - Update audit log queries in testing docs

---

## ✅ Compliance Checklist

- ✅ All requirements met
- ✅ Prepared statements used throughout
- ✅ Session-based authentication implemented
- ✅ Soft-delete for admins (is_active flag)
- ✅ Comprehensive comments provided
- ✅ Input validation on all forms
- ✅ CSRF protection implemented
- ✅ Password hashing with password_hash()
- ✅ Faculty-scoped access control
- ✅ Rate limiting implemented
- ✅ Audit logging comprehensive
- ✅ No super-admin required
- ✅ Separate files for PHP, CSS, JS
- ✅ Testing documentation provided

---

## 📄 License

This system is part of the iReserve project for UPSI (Universiti Pendidikan Sultan Idris).

---

## 👨‍💻 Development Notes

### Technology Stack
- **Backend:** PHP 7.4+ with MySQLi
- **Database:** MySQL 5.7+ / MariaDB 10.2+
- **Frontend:** HTML5, CSS3, Vanilla JavaScript
- **Security:** Prepared statements, password hashing, CSRF tokens

### Code Standards
- PSR-12 PHP coding standard
- Comprehensive inline documentation
- Separation of concerns (MVC-like structure)
- Responsive mobile-first CSS
- Progressive enhancement JavaScript

---

**Version:** 1.0  
**Last Updated:** December 2, 2025  
**Author:** iReserve Development Team  
**System:** UPSI Facility Booking System

---

🎉 **Implementation Complete!** 

Your admin management system is ready for deployment and testing.
