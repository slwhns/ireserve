# Admin Page Structure Guide

## 📁 File Organization

```
admin/
├── includes/              # Reusable components
│   ├── auth.php          # Session & authentication check
│   ├── header.php        # HTML head, CSS links
│   ├── sidebar.php       # Navigation menu
│   ├── topbar.php        # Search bar & user info
│   └── footer.php        # Closing tags & scripts
├── _page_template.php    # Template for new pages
├── admin_page.php        # Dashboard
├── inventory.php         # Inventory list
├── inventory_add.php     # Add inventory item
└── userList.php          # User management
```

## 🚀 Creating a New Page (3 Simple Steps)

### Step 1: Copy the template
```bash
cp _page_template.php your_new_page.php
```

### Step 2: Configure the page variables (top of file)
```php
<?php
$pageTitle = 'User Settings';      // Browser tab title
$currentPage = 'settings';          // Active menu highlight
$requireAdmin = true;               // Admin-only page
$additionalCSS = '<link rel="stylesheet" href="/ireserve/assets/css/settings.css">';
$additionalJS = '<script src="/ireserve/assets/js/settings.js"></script>';
```

### Step 3: Add your content between the comments
```php
<div class="main">
    <?php include 'includes/topbar.php'; ?>

    <!-- Your page-specific content starts here -->
    <div class="details">
        <h2>My New Feature</h2>
        <!-- Your code here -->
    </div>
    <!-- Your page-specific content ends here -->

</div>
```

## 📝 Page Configuration Options

### `$pageTitle`
- Shows in browser tab: `Your Title | iReserve`
- Default: `'Admin Dashboard'`

### `$currentPage`
- Highlights the active menu item
- Options: `'dashboard'`, `'users'`, `'inventory'`, `'bookings'`, `'settings'`

### `$requireAdmin`
- Set to `true` for admin-only pages
- Redirects non-admin users automatically

### `$additionalCSS`
- Add page-specific stylesheets
- Example: `'<link rel="stylesheet" href="/ireserve/assets/css/my-page.css">'`

### `$additionalJS`
- Add page-specific JavaScript
- Example: `'<script src="/ireserve/assets/js/my-script.js"></script>'`
- Or inline: `'<script>alert("Hello");</script>'`

## 🎨 Common Page Layouts

### Simple Content Page
```php
<div class="main">
    <?php include 'includes/topbar.php'; ?>
    
    <div class="details">
        <div class="form-card">
            <h2>Page Title</h2>
            <p>Your content</p>
        </div>
    </div>
</div>
```

### Table/List Page
```php
<div class="main">
    <?php include 'includes/topbar.php'; ?>
    
    <div class="details">
        <div class="inventoryTable">
            <div class="cardHeader">
                <h3>Item List</h3>
                <a href="add.php" class="btn">Add New</a>
            </div>
            <table>
                <!-- Your table -->
            </table>
        </div>
    </div>
</div>
```

### Form Page
```php
<div class="main">
    <?php include 'includes/topbar.php'; ?>
    
    <div class="form-container">
        <div class="form-card">
            <form method="POST">
                <!-- Your form fields -->
            </form>
        </div>
    </div>
</div>
```

## 🔧 Modifying Components

### Update Sidebar Menu
Edit: `includes/sidebar.php`
```php
<li class="<?php echo ($currentPage == 'your-page') ? 'hovered' : ''; ?>">
    <a href="/ireserve/admin/your_page.php">
        <span class="icon"><i class="bi bi-your-icon"></i></span>
        <span class="title">Your Page</span>
    </a>
</li>
```

### Update Topbar
Edit: `includes/topbar.php`

### Change Page Title Format
Edit: `includes/header.php`

## ✅ Benefits of This Structure

1. **No Code Duplication** - Sidebar & topbar defined once
2. **Easy Maintenance** - Update sidebar in one place, changes everywhere
3. **Consistent Design** - All pages use same components
4. **Quick Development** - Copy template, add content, done!
5. **Clean Code** - Each page only contains its specific logic

## 📌 Example: Real Page

```php
<?php
// Configuration
$pageTitle = 'Manage Categories';
$currentPage = 'settings';
$additionalCSS = '<link rel="stylesheet" href="/ireserve/assets/css/categories.css">';

// Include components
require_once 'includes/auth.php';
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<div class="main">
    <?php include 'includes/topbar.php'; ?>

    <div class="details">
        <div class="inventoryTable">
            <div class="cardHeader">
                <h3>Categories</h3>
                <a href="category_add.php" class="btn">Add Category</a>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <td>ID</td>
                        <td>Name</td>
                        <td>Actions</td>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $result = $conn->query("SELECT * FROM categories");
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>{$row['category_id']}</td>";
                        echo "<td>{$row['name']}</td>";
                        echo "<td><button>Edit</button></td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
```

That's it! Your page is ready. 🎉
