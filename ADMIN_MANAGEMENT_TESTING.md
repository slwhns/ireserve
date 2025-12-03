# Admin Management Testing Guide
# iReserve - UPSI Facility Booking System

This guide provides comprehensive instructions for testing the Admin Invite and Admin Management feature.

---

## 📋 Table of Contents

1. [Prerequisites](#prerequisites)
2. [Database Setup](#database-setup)
3. [Test Accounts](#test-accounts)
4. [Testing Workflow](#testing-workflow)
5. [Feature Testing Checklist](#feature-testing-checklist)
6. [Security Testing](#security-testing)
7. [Common Issues](#common-issues)
8. [Expected Results](#expected-results)

---

## 🔧 Prerequisites

Before testing, ensure you have:

- **PHP 7.4+** with mysqli extension
- **MySQL 5.7+** or MariaDB 10.2+
- **Web Server** (Apache/Nginx) configured for PHP
- **Email Configuration** (optional, for testing email invitations)
- Access to phpMyAdmin or MySQL command line
- Modern web browser (Chrome, Firefox, Edge)

---

## 💾 Database Setup

### Step 1: Run the Schema SQL

1. Open phpMyAdmin or MySQL command line
2. Select your database (`ireserve_db`)
3. Execute the SQL file: `admin_management_schema.sql`

```bash
# Using MySQL command line:
mysql -u root -p ireserve_db < admin_management_schema.sql
```

### Step 2: Verify Tables Created

Run this query to verify all tables exist:

```sql
SHOW TABLES LIKE '%faculties%';
SHOW TABLES LIKE '%admin_invites%';
SHOW TABLES LIKE '%audit_logs%';
```

You should see:
- `faculties`
- `admin_invites`
- `audit_logs`
- `users` (modified)

### Step 3: Verify Sample Data

Check that faculties were created:

```sql
SELECT * FROM faculties;
```

Should return 6 faculties:
1. Faculty of Computing and Meta-Technology
2. Faculty of Business and Management
3. Faculty of Education and Human Development
4. Faculty of Science and Technology
5. Faculty of Arts and Social Sciences
6. Faculty of Music and Performing Arts

### Step 4: Verify Seed Admins

Check seed admin accounts:

```sql
SELECT u.id, u.name, u.email, f.name as faculty, u.is_active
FROM users u
JOIN faculties f ON u.faculty_id = f.faculty_id
WHERE u.role = 'admin'
ORDER BY f.name;
```

Should return 12 admin accounts (2 per faculty).

---

## 👤 Test Accounts

### Default Test Admin Credentials

**All test accounts use the same password:** `Admin@123`

**Computing Faculty:**
- Email: `ahmad.hassan@upsi.edu.my`
- Email: `siti.nurhaliza@upsi.edu.my`

**Business Faculty:**
- Email: `wong.wei@upsi.edu.my`
- Email: `kavitha.devi@upsi.edu.my`

**Education Faculty:**
- Email: `rozita.abdullah@upsi.edu.my`
- Email: `lee.chong@upsi.edu.my`

**Science Faculty:**
- Email: `azman.ibrahim@upsi.edu.my`
- Email: `farah.liyana@upsi.edu.my`

**Arts Faculty:**
- Email: `nurul.izzah@upsi.edu.my`
- Email: `raja.mokhtar@upsi.edu.my`

**Music Faculty:**
- Email: `ahmad.nawab@upsi.edu.my`
- Email: `sheila.majid@upsi.edu.my`

---

## 🧪 Testing Workflow

### Complete Test Flow (30-45 minutes)

#### Phase 1: Initial Login and Navigation

1. **Login as Admin**
   - Navigate to: `http://localhost/ireserve/index.php`
   - Login with: `ahmad.hassan@upsi.edu.my` / `Admin@123`
   - Should redirect to admin dashboard

2. **Navigate to Admin Management**
   - Click on "Admin Management" in sidebar (or navigate to `/ireserve/admin/admin_list.php`)
   - Should see list of admins for Computing faculty only
   - Should see 2 admins listed

#### Phase 2: Create Admin Invite

3. **Create First Invite**
   - Click "Create Invite" button
   - Enter email: `newadmin1@upsi.edu.my`
   - Click "Send Invitation"
   - Should see success message with registration link

4. **Test Rate Limiting**
   - Create 4 more invites with different emails
   - Try to create a 6th invite
   - Should see error: "Rate limit exceeded. You can only create 5 invites per hour."

5. **Test Duplicate Email**
   - Try to create invite with email: `siti.nurhaliza@upsi.edu.my` (existing admin)
   - Should see error: "This email is already registered as an admin in your faculty."

#### Phase 3: Admin Registration

6. **Copy Registration Link**
   - From the success message, copy the registration link
   - Example: `http://localhost/ireserve/admin/admin_register.php?token=abc123...`

7. **Open Registration Link (Incognito Mode Recommended)**
   - Open link in new incognito/private window
   - Should see registration form with:
     - Email (pre-filled from invite)
     - Faculty name displayed
     - Name input field
     - Password fields

8. **Test Password Validation**
   - Enter name: `New Admin One`
   - Enter weak password: `test123`
   - Should see error about password requirements

9. **Complete Registration**
   - Enter name: `New Admin One`
   - Enter password: `NewAdmin@123`
   - Confirm password: `NewAdmin@123`
   - Click "Complete Registration"
   - Should see success message

10. **Verify Registration**
    - Try using the same registration link again
    - Should see error: "This invitation has already been used"

#### Phase 4: Verify New Admin

11. **Login as New Admin**
    - Go to login page
    - Login with: `newadmin1@upsi.edu.my` / `NewAdmin@123`
    - Should successfully login to admin dashboard

12. **Check Admin List**
    - Back in original admin account window
    - Refresh admin list page
    - Should now see 3 active admins for Computing faculty

#### Phase 5: Deactivation Testing

13. **Test Self-Deactivation Prevention**
    - As admin `ahmad.hassan@upsi.edu.my`
    - Try to deactivate yourself
    - Should see disabled button or "Cannot deactivate yourself" message

14. **Deactivate Another Admin**
    - Click "Deactivate" button next to `siti.nurhaliza@upsi.edu.my`
    - Confirm the action
    - Should see success message
    - Admin should now show as "Inactive"

15. **Test Last Admin Protection**
    - Deactivate `newadmin1@upsi.edu.my`
    - Now only `ahmad.hassan@upsi.edu.my` is active
    - Try to deactivate `ahmad.hassan@upsi.edu.my`
    - Should see error: "Cannot deactivate the last active administrator"

#### Phase 6: Cross-Faculty Security

16. **Login to Different Faculty**
    - Logout from Computing faculty admin
    - Login as: `wong.wei@upsi.edu.my` / `Admin@123` (Business faculty)
    - Navigate to Admin Management

17. **Verify Faculty Isolation**
    - Should only see Business faculty admins (2 admins)
    - Should NOT see Computing faculty admins
    - Statistics should show Business faculty counts only

#### Phase 7: Audit Logging

18. **Check Audit Logs**
    - Run this query in phpMyAdmin:

```sql
SELECT 
    al.id,
    actor.name as actor,
    al.action,
    al.target,
    al.details,
    al.ip,
    al.created_at
FROM audit_logs al
LEFT JOIN users actor ON al.actor_id = actor.id
ORDER BY al.created_at DESC
LIMIT 20;
```

Expected audit entries:
- `create_invite` - when invite was created
- `register_admin` - when new admin registered
- `deactivate_admin` - when admin was deactivated

---

## ✅ Feature Testing Checklist

### Authentication & Authorization

- [ ] Only logged-in admins can access admin management pages
- [ ] Admins without faculty_id cannot create invites
- [ ] Faculty-scoped access (admin only sees their faculty)
- [ ] Session properly maintained across pages

### Invite Creation

- [ ] Email format validation works
- [ ] Duplicate email check prevents re-inviting existing admins
- [ ] Rate limiting (5 per hour) enforced
- [ ] CSRF token validation works
- [ ] Registration link generated correctly
- [ ] Email sent successfully (if email configured)

### Registration

- [ ] Token validation (must exist, not used, valid format)
- [ ] Used tokens cannot be reused
- [ ] Invalid tokens show error message
- [ ] Name validation (min 3 characters)
- [ ] Password strength requirements enforced
- [ ] Password confirmation matching works
- [ ] User created with correct role and faculty
- [ ] Invite marked as used after registration

### Admin List

- [ ] Shows all admins for current faculty only
- [ ] Active/inactive status displayed correctly
- [ ] Statistics cards show correct counts
- [ ] Pending invites section shows unused invites
- [ ] Registration links copyable

### Deactivation

- [ ] Cannot deactivate self
- [ ] Cannot deactivate last active admin
- [ ] CSRF protection works
- [ ] Success message shown
- [ ] Admin marked as inactive (not deleted)
- [ ] Audit log entry created

### UI/UX

- [ ] Forms have proper validation messages
- [ ] Success/error messages display clearly
- [ ] Responsive design works on mobile
- [ ] Buttons disabled when appropriate
- [ ] Copy-to-clipboard works for registration links
- [ ] Page redirects work correctly

### Security

- [ ] All queries use prepared statements
- [ ] CSRF tokens validated on POST requests
- [ ] Passwords hashed with password_hash()
- [ ] Faculty isolation strictly enforced
- [ ] Audit logs capture all critical actions
- [ ] SQL injection attempts blocked
- [ ] XSS attempts escaped/sanitized

---

## 🔒 Security Testing

### Test SQL Injection

Try these in the email field:
```
test@test.com'; DROP TABLE users; --
test@test.com' OR '1'='1
admin@test.com' UNION SELECT * FROM users--
```

**Expected:** All should be safely handled by prepared statements.

### Test XSS

Try these in the name field during registration:
```
<script>alert('XSS')</script>
<img src=x onerror=alert('XSS')>
```

**Expected:** Should be displayed as plain text, not executed.

### Test CSRF

1. Create invite form in browser
2. Get the CSRF token from form HTML
3. Try submitting with an old/invalid CSRF token
4. **Expected:** Should see "Invalid security token" error

### Test Cross-Faculty Access

1. As Computing admin, note a Business admin's id
2. Try manually POSTing to `admin_deactivate.php` with Business admin's ID
3. **Expected:** Should be blocked with "Access denied" message

---

## ❌ Common Issues

### Issue 1: Tables Not Created

**Symptom:** Error about missing tables

**Solution:** 
```sql
-- Check if users table exists
SHOW TABLES LIKE 'users';

-- If it exists, use ALTER statements in schema file
-- If not, use CREATE statements
```

### Issue 2: Email Not Sending

**Symptom:** Invite created but no email received

**Solution:** 
- Check `includes/email_helper.php` configuration
- Verify SMTP settings
- Check PHP error log
- Registration link is still shown on screen for manual sharing

### Issue 3: Cannot Login as New Admin

**Symptom:** Registration successful but login fails

**Solution:**
```sql
-- Check if user was created
SELECT * FROM users WHERE email = 'newadmin1@upsi.edu.my';

-- Check password hash exists
-- Verify is_active = 1
-- Try resetting password in database
```

### Issue 4: Rate Limit Not Working

**Symptom:** Can create more than 5 invites per hour

**Solution:**
```sql
-- Check invites created
SELECT COUNT(*) FROM admin_invites 
WHERE created_by = 1 
AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR);

-- Manually delete test invites to reset
DELETE FROM admin_invites WHERE created_by = 1;
```

### Issue 5: Session Issues

**Symptom:** Logged out unexpectedly or session data lost

**Solution:**
- Check that session_name is consistent: `ireserve_admin`
- Verify session files writable
- Check browser cookies enabled

---

## ✨ Expected Results Summary

### Database State After Testing

**faculties table:** 6 faculties

**users table:** 
- 12 seed admins (2 per faculty)
- 1+ new admin created via invite
- Some admins marked inactive

**admin_invites table:**
- 5+ invite records
- Some marked as used (used=1)
- Some unused (used=0)

**audit_logs table:**
- Multiple entries for:
  - create_invite
  - register_admin
  - deactivate_admin
  - deactivate_admin_denied (if tested)

### Page Access Results

| Page | URL | Expected Result |
|------|-----|-----------------|
| Admin List | `/admin/admin_list.php` | Shows faculty admins, stats, pending invites |
| Create Invite | `/admin/admin_create_invite.php` | Form to create invite with validation |
| Registration | `/admin/admin_register.php?token=...` | Form to complete registration |
| Deactivate | `/admin/admin_deactivate.php` | POST only, redirects after action |

---

## 📊 Testing Queries

### View All Active Admins by Faculty

```sql
SELECT f.name as faculty, COUNT(*) as active_admins
FROM users u
JOIN faculties f ON u.faculty_id = f.faculty_id
WHERE u.role = 'admin' AND u.is_active = 1
GROUP BY f.faculty_id, f.name
ORDER BY f.name;
```

### View Unused Invites

```sql
SELECT 
    ai.email,
    f.name as faculty,
    creator.name as created_by,
    ai.created_at
FROM admin_invites ai
JOIN faculties f ON ai.faculty_id = f.faculty_id
JOIN users creator ON ai.created_by = creator.id
WHERE ai.used = 0
ORDER BY ai.created_at DESC;
```

### View Recent Audit Activity

```sql
SELECT 
    DATE_FORMAT(al.created_at, '%Y-%m-%d %H:%i') as time,
    actor.name as who,
    al.action as what,
    al.target,
    al.ip
FROM audit_logs al
LEFT JOIN users actor ON al.actor_id = actor.id
ORDER BY al.created_at DESC
LIMIT 30;
```

---

## 🎯 Success Criteria

The feature is working correctly if:

✅ Admins can create invites within their faculty only  
✅ Rate limiting prevents abuse  
✅ Registration links work and are one-time use  
✅ Password validation enforces strong passwords  
✅ New admins can login and access admin panel  
✅ Admin list shows correct faculty-scoped data  
✅ Deactivation has proper safeguards (self, last admin)  
✅ All actions are logged to audit_logs  
✅ Security checks prevent unauthorized access  
✅ UI is responsive and user-friendly  

---

## 📞 Support

If you encounter issues not covered in this guide:

1. Check PHP error logs: `php_error.log`
2. Check MySQL error logs
3. Review browser console for JavaScript errors
4. Verify all required files are uploaded
5. Check file permissions (PHP files should be readable)

---

**Testing completed successfully?** 🎉

You now have a fully functional Admin Invite and Management system with:
- Faculty-based admin organization
- Secure invite-based registration
- Comprehensive audit logging
- Robust security controls
- Professional UI/UX

---

*Last Updated: December 2, 2025*  
*Version: 1.0*  
*System: iReserve - UPSI Facility Booking*
