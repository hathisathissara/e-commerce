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
    <title>Savi’s creation  Corner - Welcome</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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

        .section {
            padding: 60px 0;
        }

        /* 1. Header & Navigation */
        .header {
            background: #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo {
            font-size: 1.5em;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
        }

        .nav-link {
            color: var(--text-dark) !important;
            font-weight: 600;
            transition: color 0.3s;
        }

        .nav-link.active,
        .nav-link:hover {
            color: var(--primary-color) !important;
        }

        .cart-icon a {
            font-weight: 600;
            text-decoration: none;
            color: #fff !important;
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
            height: 60vh;
            max-height: 500px;
        }

        @media (max-width: 768px) {
            .swiper-container {
                height: 35vh;
                max-height: 250px;
            }
            .slide-content h2 {
                font-size: 1.5em !important;
            }
            .slide-content {
                padding: 15px !important;
            }
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
        .feature-box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            text-align: center;
            margin-bottom: 30px;
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

        .product-card {
            background: #fff;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
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

        @media (max-width: 768px) {
            .product-card img {
                height: 180px;
            }
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

        /* 6. Footer */
        .footer {
            background: var(--text-dark);
            color: var(--text-light);
            padding: 40px 20px;
        }

        .footer-content {
            text-align: center;
        }
    </style>
</head>

<body>

    <header class="header">
        <nav class="navbar navbar-expand-lg navbar-light bg-white container">
            <a href="index.php" class="logo navbar-brand">Savi’s creation </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a href="index.php" class="nav-link active">Home</a>
                    </li>
                    <li class="nav-item">
                        <a href="store.php" class="nav-link">Store</a>
                    </li>
                    <li class="nav-item">
                        <a href="about.php" class="nav-link">About Us</a>
                    </li>
                </ul>
                <div class="cart-icon ms-lg-3 mt-3 mt-lg-0">
                    <a href="cart.php">Cart <span class="cart-count"><?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?></span></a>
                </div>
            </div>
        </nav>
    </header>

    <?php if (!empty($slides)): ?>
        <div class="swiper-container mt-3">
            <div class="swiper-wrapper">
                <?php foreach ($slides as $slide): ?>
                    <div class="swiper-slide" style="background-image: url('<?php echo htmlspecialchars($slide['image_path']); ?>');">
                        <div class="slide-content mx-auto">
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
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-4">
                    <div class="feature-box h-100">
                        <span class="icon">🚚</span>
                        <h3>Islandwide Delivery</h3>
                        <p>Fast and secure delivery to your doorstep, anywhere in Sri Lanka.</p>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="feature-box h-100">
                        <span class="icon">🎁</span>
                        <h3>Premium Quality</h3>
                        <p>Handpicked gifts made with love and attention to every detail.</p>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="feature-box h-100">
                        <span class="icon">💖</span>
                        <h3>Unforgettable Moments</h3>
                        <p>We help you create memories that last a lifetime.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section featured-products">
        <div class="container">
            <h2 class="section-title">Our New Arrivals</h2>
            <div class="row g-4">
                <?php foreach ($featured_products as $product): ?>
                    <div class="col-12 col-sm-6 col-lg-3 d-flex">
                        <div class="product-card w-100">
                            <img src="<?php echo htmlspecialchars($product['image'] ?? 'https://via.placeholder.com/300'); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                            <div class="product-info d-flex flex-column">
                                <div>
                                    <h3><?php echo htmlspecialchars($product['product_name']); ?></h3>
                                    <p class="price">From LKR <?php echo number_format($product['starting_price'], 2); ?></p>
                                </div>
                                <a href="store.php" class="view-btn mt-auto">Choose Your Option</a>
                            </div>
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

    <footer class="footer">
        <div class="container footer-content">
            <p>© <?php echo date("Y"); ?> Savi’s creation  Corner. All Rights Reserved.</p>
            <p>Colombo, Sri Lanka</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
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