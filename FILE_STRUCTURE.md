# 📂 Admin Management - File Structure Overview

## Complete File Listing

```
ireserve/
│
├── 📄 admin_management_schema.sql           [Database Schema]
│   └── Creates: faculties, admin_invites, audit_logs
│   └── Modifies: users table
│   └── Includes: 12 seed admin accounts
│
├── 📘 ADMIN_MANAGEMENT_README.md            [Main Documentation]
│   └── Complete feature overview
│   └── Installation guide
│   └── Usage instructions
│   └── Security features
│
├── 📗 ADMIN_MANAGEMENT_TESTING.md           [Testing Guide]
│   └── Step-by-step testing workflow
│   └── Test accounts and credentials
│   └── Security testing procedures
│   └── Troubleshooting guide
│
├── 📙 QUICK_START.md                        [Quick Setup]
│   └── 5-minute setup guide
│   └── Quick tests
│   └── Common issues & fixes
│
├── includes/
│   └── 📜 admin_helpers.php                 [Core Functions - 400+ lines]
│       ├── current_admin()                  - Get current admin session
│       ├── count_active_admins()            - Count active admins per faculty
│       ├── is_same_faculty()                - Verify faculty membership
│       ├── audit_log()                      - Log administrative actions
│       ├── get_client_ip()                  - Get real client IP
│       ├── generate_csrf_token()            - Create CSRF tokens
│       ├── validate_csrf_token()            - Validate CSRF tokens
│       ├── check_invite_rate_limit()        - Rate limiting check
│       ├── validate_email()                 - Email format validation
│       ├── email_exists_as_admin()          - Check duplicate admins
│       ├── validate_password_strength()     - Password requirements
│       ├── get_faculty_name()               - Get faculty name by ID
│       └── update_last_login()              - Update login timestamp
│
├── admin/
│   │
│   ├── 📄 admin_create_invite.php           [Create Invites - 200+ lines]
│   │   ├── Session authentication check
│   │   ├── Faculty validation
│   │   ├── CSRF token validation
│   │   ├── Email validation
│   │   ├── Rate limiting enforcement (5/hour)
│   │   ├── Token generation (32 chars)
│   │   ├── Database insert with prepared statement
│   │   ├── Email sending (optional)
│   │   ├── Audit logging
│   │   └── HTML form with validation
│   │
│   ├── 📄 admin_register.php                [Registration - 300+ lines]
│   │   ├── Token validation (format, exists, unused)
│   │   ├── Invite details display
│   │   ├── Name validation (3-255 chars)
│   │   ├── Password strength validation
│   │   ├── Password confirmation matching
│   │   ├── Transaction-based user creation
│   │   ├── Invite marking as used
│   │   ├── Audit logging
│   │   ├── Success/error handling
│   │   └── Styled registration form
│   │
│   ├── 📄 admin_list.php                    [Admin Dashboard - 250+ lines]
│   │   ├── Faculty-scoped admin query
│   │   ├── Active admin count
│   │   ├── Pending invites query
│   │   ├── Statistics cards display
│   │   ├── Admin table with status badges
│   │   ├── Action buttons (deactivate)
│   │   ├── Self-deactivation prevention
│   │   ├── Last-admin protection UI
│   │   ├── Registration link copying
│   │   └── Responsive layout
│   │
│   └── 📄 admin_deactivate.php              [Deactivation Handler - 180+ lines]
│       ├── POST-only enforcement
│       ├── Session authentication
│       ├── CSRF validation
│       ├── User ID validation
│       ├── Self-deactivation check
│       ├── Target user verification
│       ├── Role verification (admin only)
│       ├── Same-faculty verification
│       ├── Active status check
│       ├── Last-admin count check
│       ├── Soft-delete update (is_active=0)
│       ├── Audit logging
│       └── JSON/redirect response
│
├── assets/
│   │
│   ├── css/
│   │   └── 🎨 admin_management.css          [Styling - 500+ lines]
│   │       ├── Page layouts & containers
│   │       ├── Header & navigation
│   │       ├── Statistics cards (gradient)
│   │       ├── Card components
│   │       ├── Table styling
│   │       ├── User info display
│   │       ├── Badge styles
│   │       ├── Button styles
│   │       ├── Form styling
│   │       ├── Alert/notification styles
│   │       ├── Info boxes
│   │       ├── Registration link boxes
│   │       ├── Empty states
│   │       ├── Responsive breakpoints
│   │       ├── Loading spinners
│   │       └── Utility classes
│   │
│   └── js/
│       └── ⚙️ admin_management.js           [JavaScript - 450+ lines]
│           ├── validateEmail()              - Email format check
│           ├── showInputError()             - Display validation errors
│           ├── clearInputError()            - Clear error messages
│           ├── checkPasswordStrength()      - Password requirements
│           ├── getPasswordStrengthMessage() - Error messages
│           ├── handleInviteFormSubmit()     - Invite form validation
│           ├── handleRegistrationFormSubmit() - Registration validation
│           ├── deactivateAdmin()            - Deactivation with confirm
│           ├── copyToClipboard()            - Copy functionality
│           ├── showNotification()           - Toast notifications
│           ├── setupPasswordMatchIndicator() - Real-time password match
│           ├── setupAutoTrim()              - Auto-trim inputs
│           ├── displaySessionMessages()     - PHP session messages
│           ├── addAnimationStyles()         - CSS animations
│           └── DOMContentLoaded handler     - Initialize all features
│
└── config.php                               [Existing - Database Connection]
    └── Database credentials
    └── MySQLi connection
```

---

## 📊 Lines of Code Summary

| Component | File | Lines | Description |
|-----------|------|-------|-------------|
| **Backend** | admin_helpers.php | ~400 | Core helper functions |
| | admin_create_invite.php | ~200 | Invite creation logic |
| | admin_register.php | ~300 | Registration processing |
| | admin_list.php | ~250 | Admin listing & UI |
| | admin_deactivate.php | ~180 | Deactivation handler |
| **Frontend** | admin_management.css | ~500 | Complete styling |
| | admin_management.js | ~450 | Client-side logic |
| **Database** | admin_management_schema.sql | ~200 | Schema + seed data |
| **Docs** | All .md files | ~1,500 | Documentation |
| **TOTAL** | | **~3,980** | Complete system |

---

## 🔄 Data Flow Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                    ADMIN INVITE FLOW                        │
└─────────────────────────────────────────────────────────────┘

1. CREATE INVITE
   ┌─────────────┐
   │   Admin A   │ ───── Opens admin_create_invite.php
   └──────┬──────┘
          │
          ▼
   ┌─────────────────┐
   │ Rate Limit OK?  │ ───── check_invite_rate_limit()
   └──────┬──────────┘
          │ YES
          ▼
   ┌─────────────────┐
   │ Email Valid?    │ ───── validate_email()
   └──────┬──────────┘
          │ YES
          ▼
   ┌─────────────────┐
   │ Not Duplicate?  │ ───── email_exists_as_admin()
   └──────┬──────────┘
          │ YES
          ▼
   ┌─────────────────┐
   │ Generate Token  │ ───── bin2hex(random_bytes(16))
   └──────┬──────────┘
          │
          ▼
   ┌─────────────────┐
   │  INSERT INTO    │ ───── admin_invites table
   │  admin_invites  │
   └──────┬──────────┘
          │
          ▼
   ┌─────────────────┐
   │  audit_log()    │ ───── Log 'create_invite'
   └──────┬──────────┘
          │
          ▼
   ┌─────────────────┐
   │  Send Email +   │ ───── Email with link
   │  Show Link      │
   └─────────────────┘

2. REGISTRATION
   ┌─────────────┐
   │  New User   │ ───── Opens registration link
   └──────┬──────┘
          │
          ▼
   ┌─────────────────┐
   │ Token Valid?    │ ───── Check format, exists, unused
   └──────┬──────────┘
          │ YES
          ▼
   ┌─────────────────┐
   │  Display Form   │ ───── Show email, faculty, name fields
   └──────┬──────────┘
          │
          ▼
   ┌─────────────────┐
   │  Submit Form    │ ───── POST name, password
   └──────┬──────────┘
          │
          ▼
   ┌─────────────────┐
   │ Validate Data   │ ───── Name, password strength
   └──────┬──────────┘
          │ VALID
          ▼
   ┌─────────────────┐
   │ BEGIN TRANS.    │ ───── Database transaction
   └──────┬──────────┘
          │
          ▼
   ┌─────────────────┐
   │  INSERT INTO    │ ───── users table
   │  users          │
   └──────┬──────────┘
          │
          ▼
   ┌─────────────────┐
   │ UPDATE invite   │ ───── Mark used = 1
   └──────┬──────────┘
          │
          ▼
   ┌─────────────────┐
   │  audit_log()    │ ───── Log 'register_admin'
   └──────┬──────────┘
          │
          ▼
   ┌─────────────────┐
   │ COMMIT TRANS.   │ ───── Success!
   └─────────────────┘

3. DEACTIVATION
   ┌─────────────┐
   │   Admin B   │ ───── Clicks deactivate on admin_list.php
   └──────┬──────┘
          │
          ▼
   ┌─────────────────┐
   │  Confirm?       │ ───── JavaScript confirmation
   └──────┬──────────┘
          │ YES
          ▼
   ┌─────────────────┐
   │ POST to         │ ───── admin_deactivate.php
   │ deactivate.php  │
   └──────┬──────────┘
          │
          ▼
   ┌─────────────────┐
   │ CSRF Valid?     │ ───── validate_csrf_token()
   └──────┬──────────┘
          │ YES
          ▼
   ┌─────────────────┐
   │ Not Self?       │ ───── Check actor_id != target_id
   └──────┬──────────┘
          │ YES
          ▼
   ┌─────────────────┐
   │ Same Faculty?   │ ───── is_same_faculty()
   └──────┬──────────┘
          │ YES
          ▼
   ┌─────────────────┐
   │ Not Last Admin? │ ───── count_active_admins() > 1
   └──────┬──────────┘
          │ YES
          ▼
   ┌─────────────────┐
   │ UPDATE users    │ ───── SET is_active = 0
   └──────┬──────────┘
          │
          ▼
   ┌─────────────────┐
   │  audit_log()    │ ───── Log 'deactivate_admin'
   └──────┬──────────┘
          │
          ▼
   ┌─────────────────┐
   │ Redirect to     │ ───── Back to admin_list.php
   │ admin_list.php  │
   └─────────────────┘
```

---

## 🔐 Security Layers

```
┌────────────────────────────────────────────────────────┐
│              SECURITY ARCHITECTURE                     │
└────────────────────────────────────────────────────────┘

Layer 1: AUTHENTICATION
├── Session-based (ireserve_admin)
├── current_admin() validation
└── Role check (must be 'admin')

Layer 2: AUTHORIZATION
├── Faculty-scoped queries
├── is_same_faculty() checks
└── Resource ownership validation

Layer 3: INPUT VALIDATION
├── Email format (regex)
├── Password strength (8+ chars, upper, lower, num, special)
├── Token format (32 hex chars)
└── Name length (3-255 chars)

Layer 4: CSRF PROTECTION
├── generate_csrf_token() on forms
├── validate_csrf_token() on POST
└── Timing-safe comparison

Layer 5: SQL INJECTION PROTECTION
├── Prepared statements (100% coverage)
├── Parameter binding
└── No string concatenation in queries

Layer 6: BUSINESS LOGIC PROTECTION
├── Rate limiting (5/hour)
├── Self-deactivation prevention
├── Last-admin protection
└── One-time token usage

Layer 7: AUDIT & MONITORING
├── All actions logged
├── IP address tracking
├── Timestamp tracking
└── Actor/target tracking
```

---

## 🗄️ Database Relationships

```
┌─────────────────┐
│   faculties     │
│─────────────────│
│ faculty_id (PK) │◄────┐
│ name            │     │
└─────────────────┘     │
                        │ FK
┌─────────────────┐     │
│     users       │     │
│─────────────────│     │
│ id (PK)    │◄────┼───┐
│ name            │     │   │
│ email (UNIQUE)  │     │   │
│ password        │     │   │
│ role (ENUM)     │     │   │
│ faculty_id (FK) │─────┘   │ FK
│ is_active       │         │
│ last_login      │         │
│ created_at      │         │
└─────────────────┘         │
        ▲                   │
        │ FK                │
        │                   │
┌─────────────────┐         │
│ admin_invites   │         │
│─────────────────│         │
│ token (PK)      │         │
│ email           │         │
│ faculty_id (FK) │─────────┘
│ created_by (FK) │─────────┐
│ created_at      │         │
│ used            │         │
│ used_at         │         │
│ used_by (FK)    │─────────┤
└─────────────────┘         │
                            │
┌─────────────────┐         │
│  audit_logs     │         │
│─────────────────│         │
│ id (PK)         │         │
│ actor_id (FK)   │─────────┘
│ action          │
│ target          │
│ details         │
│ ip              │
│ created_at      │
└─────────────────┘
```

---

## 📝 Function Dependencies

```
admin_create_invite.php
│
├── requires: config.php
├── requires: admin_helpers.php
├── requires: email_helper.php
│
└── uses:
    ├── current_admin()
    ├── validate_email()
    ├── email_exists_as_admin()
    ├── check_invite_rate_limit()
    ├── generate_csrf_token()
    ├── validate_csrf_token()
    ├── get_faculty_name()
    ├── audit_log()
    └── send_email() [optional]

admin_register.php
│
├── requires: config.php
├── requires: admin_helpers.php
│
└── uses:
    ├── validate_password_strength()
    ├── audit_log()
    └── password_hash()

admin_list.php
│
├── requires: config.php
├── requires: admin_helpers.php
│
└── uses:
    ├── current_admin()
    ├── get_faculty_name()
    ├── count_active_admins()
    └── generate_csrf_token()

admin_deactivate.php
│
├── requires: config.php
├── requires: admin_helpers.php
│
└── uses:
    ├── current_admin()
    ├── validate_csrf_token()
    ├── is_same_faculty()
    ├── count_active_admins()
    └── audit_log()
```

---

## 🎯 Key Metrics

| Metric | Value |
|--------|-------|
| **Total Files Created** | 11 files |
| **Total Lines of Code** | ~3,980 lines |
| **Database Tables** | 4 tables |
| **Helper Functions** | 14 functions |
| **Security Checks** | 7 layers |
| **Password Requirements** | 5 rules |
| **Rate Limit** | 5 invites/hour |
| **Seed Admins** | 12 accounts |
| **Faculties** | 6 faculties |
| **Documentation Pages** | 4 markdown files |

---

## 🔄 Update/Maintenance Points

### To Add New Faculty:
```sql
INSERT INTO faculties (name) VALUES ('New Faculty Name');
```

### To Reset Test Data:
```sql
TRUNCATE admin_invites;
UPDATE users SET is_active = 1 WHERE role = 'admin';
TRUNCATE audit_logs;
```

### To Change Rate Limit:
Edit in `includes/admin_helpers.php`:
```php
// Line ~280 in check_invite_rate_limit()
return (int)$row['count'] < 5;  // Change 5 to new limit
```

### To Add Audit Log Action:
```php
audit_log($actor_id, 'new_action_name', $target, $details);
```

---

## ✅ Completion Checklist

- [x] Database schema created
- [x] Helper functions implemented
- [x] Invite creation page created
- [x] Registration page created
- [x] Admin list page created
- [x] Deactivation handler created
- [x] CSS styling complete
- [x] JavaScript functionality complete
- [x] Documentation written
- [x] Testing guide created
- [x] Quick start guide created
- [x] File structure documented

---

**📊 Total Development:**
- Backend PHP: ~1,330 lines
- Frontend (CSS+JS): ~950 lines
- Database SQL: ~200 lines
- Documentation: ~1,500 lines
- **Grand Total: ~3,980 lines**

---

*This structure provides a complete, production-ready admin management system with comprehensive security, validation, and user experience features.*
