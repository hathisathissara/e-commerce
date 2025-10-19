<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar">
    <div class="sidebar-header">
        <h2>Savi’s Creation</h2>
        <p>Admin Panel</p>
    </div>
    <ul class="sidebar-nav">
        <li><a href="dashboard.php" class="<?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">Dashboard</a></li>
        <li><a href="manage_orders.php" class="<?= ($current_page == 'manage_orders.php') ? 'active' : '' ?>">Orders</a></li>
        <li><a href="view_products.php" class="<?= ($current_page == 'view_products.php') ? 'active' : '' ?>">Products</a></li>
        <li><a href="manage_packages.php" class="<?= ($current_page == 'manage_packages.php') ? 'active' : '' ?>">Packages</a></li>
        <li><a href="manage_customers.php" class="<?= ($current_page == 'manage_customers.php') ? 'active' : '' ?>">Customers</a></li>
        <li><a href="manage_slider.php" class="<?= ($current_page == 'manage_slider.php') ? 'active' : '' ?>">Manage Slider</a></li>
        <li><a href="logout.php" class="logout <?= ($current_page == 'logout.php') ? 'active' : '' ?>">Logout</a></li>
    </ul>
</aside>