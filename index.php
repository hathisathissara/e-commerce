<?php
session_start();
require_once 'includes/db_connect.php';

// Fetch slides for the homepage
$slides = [];
$sql_slides = "SELECT * FROM slides WHERE is_active = 1 ORDER BY display_order ASC";
$result_slides = $conn->query($sql_slides);
if ($result_slides && $result_slides->num_rows > 0) {
    $slides = $result_slides->fetch_all(MYSQLI_ASSOC);
}

// Fetch a few new products to feature on the homepage
$sql_products = "SELECT p.product_id, p.product_name, MIN(pv.price) as starting_price, 
                       (SELECT image FROM product_variants WHERE product_id = p.product_id LIMIT 1) as image
                FROM products p
                INNER JOIN product_variants pv ON p.product_id = pv.product_id
                GROUP BY p.product_id, p.product_name, p.created_at
                ORDER BY p.created_at DESC
                LIMIT 4";

$featured_products = [];
$result_products = $conn->query($sql_products);
if ($result_products && $result_products->num_rows > 0) {
    $featured_products = $result_products->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="si">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Savi’s creation Corner - Welcome</title>
    <!-- Swiper JS CSS -->
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
    <!-- Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #e84393;
            --secondary-color: #0984e3;
            --text-dark: #333333;
            --text-light: #f1f2f6;
            --background-color: #f7f1e3;
        }

        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            background-color: var(--background-color);
            color: var(--text-dark);
        }

        .container {
            max-width: 1200px;
            margin: auto;
            padding: 20px;
        }

        .section {
            padding: 60px 20px;
        }

        /* 1. Header & Navigation */
        .header {
            background: #fff;
            padding: 10px 40px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5em;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
        }

        .nav-links a {
            margin: 0 15px;
            text-decoration: none;
            color: var(--text-dark);
            font-weight: 600;
            transition: color 0.3s;
        }

        .nav-links a:hover,
        .nav-links a.active,
        .cart a:hover {
            color: var(--primary-color);
        }

        .cart-icon a {
            font-weight: 600;
            text-decoration: none;
            color: #fff;
            background: var(--secondary-color);
            padding: 8px 15px;
            border-radius: 20px;
        }

        .cart-count {
            background: var(--primary-color);
            padding: 2px 7px;
            border-radius: 50%;
            font-size: 0.8em;
            margin-left: 5px;
        }

        /* 2. Swiper Slider */
        .swiper-container {
            width: 100%;
            height: 80vh;
            max-height: 500px;
        }

        .swiper-slide {
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            background-size: cover;
            background-position: center;
        }

        .slide-content {
            background: rgba(0, 0, 0, 0.5);
            padding: 40px;
            border-radius: 10px;
            color: white;
            max-width: 600px;
        }

        .slide-content h2 {
            font-size: 3em;
            margin: 0;
        }

        .slide-content p {
            font-size: 1.2em;
            margin: 10px 0 20px;
        }

        .slide-btn {
            background: var(--primary-color);
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
        }

        .swiper-button-next,
        .swiper-button-prev {
            color: white;
        }

        /* 3. Features Section */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            text-align: center;
        }

        .feature-box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .feature-box .icon {
            font-size: 3em;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        /* 4. Featured Products Section */
        .section-title {
            text-align: center;
            margin-bottom: 40px;
            font-size: 2.5em;
            font-weight: 700;
            color: var(--text-dark);
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
        }

        .product-card {
            background: #fff;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.12);
        }

        .product-card img {
            width: 100%;
            height: 280px;
            object-fit: cover;
        }

        .product-info {
            padding: 20px;
            text-align: left;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .product-info h3 {
            margin: 0 0 10px;
            font-size: 1.3em;
        }

        .product-info .price {
            font-weight: 600;
            color: var(--primary-color);
            font-size: 1.4em;
            margin: 10px 0;
        }

        .view-btn {
            display: block;
            text-align: center;
            margin-top: 15px;
            padding: 12px;
            background: var(--secondary-color);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
        }

        /* 5. CTA Section */
        .cta-banner {
            background: linear-gradient(45deg, var(--secondary-color), var(--primary-color));
            color: white;
            padding: 60px 20px;
            text-align: center;
            border-radius: 15px;
            margin: 40px 0;
        }

        .cta-banner h2 {
            font-size: 2em;
            margin-top: 0;
        }

        .cta-btn {
            background: white;
            color: var(--primary-color);
            padding: 15px 30px;
            font-size: 1.2em;
            text-decoration: none;
            border-radius: 30px;
            font-weight: 700;
            display: inline-block;
        }

        /* 5. Footer */
        .footer {
            background: var(--text-dark);
            color: var(--text-light);
            padding: 40px 20px;
            text-align: center;
        }
    </style>
</head>

<body>

    <!-- 1. HEADER SECTION (Consistent) -->
    <?php require "layout/header.php" ?>

    <?php if (!empty($slides)): ?>
        <div class="swiper-container">
            <div class="swiper-wrapper">
                <?php foreach ($slides as $slide): ?>
                    <div class="swiper-slide" style="background-image: url('<?php echo htmlspecialchars($slide['image_path']); ?>');">
                        <div class="slide-content">
                            <h2><?php echo htmlspecialchars($slide['title']); ?></h2>
                            <p><?php echo htmlspecialchars($slide['subtitle']); ?></p>
                            <a href="<?php echo htmlspecialchars($slide['link_url'] ?? 'store.php'); ?>" class="slide-btn">Shop Now</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
            <div class="swiper-pagination"></div>
        </div>
    <?php endif; ?>

    <section class="section features-section">
        <div class="container features-grid">
            <div class="feature-box">
                <span class="icon">🚚</span>
                <h3>Islandwide Delivery</h3>
                <p>Fast and secure delivery to your doorstep, anywhere in Sri Lanka.</p>
            </div>
            <div class="feature-box">
                <span class="icon">🎁</span>
                <h3>Premium Quality</h3>
                <p>Handpicked gifts made with love and attention to every detail.</p>
            </div>
            <div class="feature-box">
                <span class="icon">💖</span>
                <h3>Unforgettable Moments</h3>
                <p>We help you create memories that last a lifetime.</p>
            </div>
        </div>
    </section>

    <section class="section featured-products">
        <div class="container">
            <h2 class="section-title">Our New Arrivals</h2>
            <div class="product-grid">
                <?php foreach ($featured_products as $product): ?>
                    <div class="product-card">
                        <img src="<?php echo htmlspecialchars($product['image'] ?? 'https://via.placeholder.com/300'); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                        <div class="product-info">
                            <div>
                                <h3><?php echo htmlspecialchars($product['product_name']); ?></h3>
                                <p class="price">From LKR <?php echo number_format($product['starting_price'], 2); ?></p>
                            </div>
                            <a href="store.php" class="view-btn">Choose Your Option</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <div class="container">
        <div class="cta-banner">
            <h2>Explore Our Full Collection</h2>
            <p>Find the perfect gift for every occasion and every special person in your life.</p>
            <a href="store.php" class="cta-btn">Browse All Gifts</a>
        </div>
    </div>

    <!-- 6. FOOTER (Consistent) -->
    <?php require "layout/footer.php" ?>

    <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
    <script>
        new Swiper('.swiper-container', {
            loop: true,
            autoplay: {
                delay: 4000,
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
        });
    </script>
</body>

</html>