<?php
    $current_page = basename($_SERVER['PHP_SELF']);
?>

<header class="header">
    <div class="navbar container">
        <a href="index.php" class="logo">Savi’s Creation</a>
        <nav class="nav-links">
            <a href="index.php" class="<?= ($current_page == 'index.php') ? 'active' : '' ?>">Home</a>
            <a href="store.php" class="<?= ($current_page == 'store.php') ? 'active' : '' ?>">Store</a>
            <a href="about.php" class="<?= ($current_page == 'about.php') ? 'active' : '' ?>">About Us</a>
        </nav>
        <div class="cart-icon">
            <a href="cart.php" class="<?= ($current_page == 'cart.php') ? 'active' : '' ?>">
                Cart <span class="cart-count"><?= isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?></span>
            </a>
        </div>
    </div>
</header>
