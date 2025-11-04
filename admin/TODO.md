# Admin Page TODO List

## Pending Tasks

### 1. Logo iReserve
- [ ] Add iReserve logo to the dashboard/main pages
- [ ] Ensure logo is responsive and properly styled
- [ ] Verify logo displays correctly in both expanded and collapsed sidebar states

### 2. Search Function
- [x] Implement global search functionality in the topbar search input
- [x] Add search capability across bookings (ref_code, user, item)
- [x] Add search capability across inventory items
- [x] Add search capability across users
- [x] Display search results in a unified interface
- [x] Add search suggestions/autocomplete
- [ ] Fix search errors (deferred for later adjustment)

### 3. Settings Page
- [x] Create admin settings page (settings.php)
- [x] Add settings navigation link in sidebar
- [x] Implement profile settings (name, email, password change)
- [x] Add system settings (categories management, system info)
- [x] Implement settings save/update functionality with file upload

### 4. Responsive Pages (All Admin Pages)
- [x] Ensure mobile-friendly layouts across all admin pages (Dashboard, Bookings, Inventory, Users, Settings)
- [x] Add/adjust media queries in `assets/css/admin.css` for navigation, dashboard, search
- [x] Add comprehensive responsive styles to `assets/css/bookings.css`
- [x] Verify responsive styles in `assets/css/inventory.css`, `admin_userList.css`, `settings.css`
- [x] Sidebar: auto-collapse on small screens; toggle remains accessible
- [x] Tables: enable horizontal scroll or stacked layout on small screens
- [x] Pagination: wrap neatly and stay tappable on mobile
- [x] Modals: fit within viewport height with internal scroll when needed
- [x] Fix sidebar navigation text visibility on mobile/tablet (CSS conflict resolved)
- [ ] Test breakpoints: 480px, 768px, 991px, 1200px+ on all pages

---

## Completed Tasks
- [x] Dashboard with real data from database
- [x] Bookings page with pagination
- [x] Inventory management
- [x] User management
- [x] Sidebar navigation with proper active states
- [x] Topbar with user profile image
- [x] Welcome message on dashboard with user's name/email
- [x] Unified topbar across all admin pages
- [x] Back button positioning (right-aligned, outside cards)
- [x] Global search with live autocomplete (search.php backend + admin.js frontend)
- [x] Settings page with profile management, password change, and category CRUD
- [x] Comprehensive responsive design for all admin pages
- [x] Sidebar navigation text visibility fix for mobile/tablet
