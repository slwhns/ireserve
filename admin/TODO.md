# Admin Page TODO List

## Pending Tasks

### 1. Logo iReserve
- [ ] Add iReserve logo to the dashboard/main pages
- [ ] Ensure logo is responsive and properly styled
- [ ] Verify logo displays correctly in both expanded and collapsed sidebar states

### 2. Search Function
- [ ] Implement global search functionality in the topbar search input
- [ ] Add search capability across bookings (ref_code, user, item)
- [ ] Add search capability across inventory items
- [ ] Add search capability across users
- [ ] Display search results in a unified interface
- [ ] Add search suggestions/autocomplete

### 3. Settings Page
- [ ] Create admin settings page (settings.php)
- [ ] Add settings navigation link in sidebar
- [ ] Implement profile settings (name, email, password change)
- [ ] Add system settings (notifications, preferences)
- [ ] Add user management settings
- [ ] Add booking policies configuration
- [ ] Implement settings save/update functionality

### 4. Responsive Pages (All Admin Pages)
- [ ] Ensure mobile-friendly layouts across all admin pages (Dashboard, Bookings, Inventory, Users, Settings)
- [ ] Add/adjust media queries in `assets/css/admin.css` (or new `assets/css/responsive.css`)
- [ ] Sidebar: auto-collapse on small screens; toggle remains accessible
- [ ] Tables: enable horizontal scroll or stacked layout on small screens
- [ ] Pagination: wrap neatly and stay tappable on mobile
- [ ] Modals: fit within viewport height with internal scroll when needed
- [ ] Test breakpoints: 320px, 375px, 768px, 1024px

---

## Completed Tasks
- [x] Dashboard with real data from database
- [x] Bookings page with pagination
- [x] Inventory management
- [x] User management
- [x] Sidebar navigation with proper active states
- [x] Topbar with user profile image
