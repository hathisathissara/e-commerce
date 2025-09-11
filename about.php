<?php
// We still need to start the session for the cart count in the header
session_start();
?>

<!DOCTYPE html>
<html lang="si">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Savi’s creation </title>
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
            --white: #ffffff;
        }

        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            background-color: var(--background-color);
            color: var(--text-dark);
        }

        .container {
            max-width: 1100px;
            margin: auto;
            padding: 20px;
        }

        .section {
            padding: 60px 20px;
        }

        /* 1. Header & Navigation (Consistent with other pages) */
        .header {
            background: var(--white);
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
        .nav-links a.active {
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

        /* 2. About Page Specific Styles */
        .about-hero {
            background-color: var(--white);
            text-align: center;
            padding: 80px 20px;
        }

        .about-hero h1 {
            font-size: 3em;
            color: var(--text-dark);
            margin: 0;
            font-weight: 700;
        }

        .about-hero p {
            font-size: 1.2em;
            color: #636e72;
            max-width: 700px;
            margin: 15px auto 0;
        }

        .about-content {
            display: grid;
            grid-template-columns: 1fr;
            gap: 40px;
            align-items: center;
        }

        @media (min-width: 768px) {
            .about-content {
                grid-template-columns: 1fr 1fr;
            }
        }

        .about-content img {
            width: 100%;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .about-text h2 {
            font-size: 2em;
            margin-top: 0;
            color: var(--primary-color);
        }

        .about-text p {
            line-height: 1.8;
            color: #636e72;
        }

        /* 3. Values Section (Reusing feature box style) */
        .values-section {
            background-color: var(--white);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            text-align: center;
        }

        .feature-box {
            padding: 30px;
        }

        .feature-box .icon {
            font-size: 3em;
            color: var(--secondary-color);
            margin-bottom: 10px;
        }

        /* 4. CTA Section */
        .cta-banner {
            text-align: center;
        }

        .cta-btn {
            background: var(--primary-color);
            color: white;
            padding: 15px 30px;
            font-size: 1.2em;
            text-decoration: none;
            border-radius: 30px;
            font-weight: 700;
            display: inline-block;
            transition: transform 0.2s;
        }

        .cta-btn:hover {
            transform: scale(1.05);
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
    <header class="header">
        <div class="navbar container">
            <a href="index.php" class="logo">Savi’s creation </a>
            <nav class="nav-links">
                <a href="index.php">Home</a>
                <a href="store.php">Store</a>
                <a href="about.php" class="active">About Us</a>
            </nav>
           <div class="cart-icon">
                <a href="cart.php">Cart <span class="cart-count"><?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?></span></a>
            </div>
        </div>
    </header>

    <!-- 2. ABOUT HERO SECTION -->
    <section class="about-hero">
        <h1>Our Story</h1>
        <p>From a small dream to a beacon of joy, Savi’s creation  all about celebrating life's precious moments.</p>
    </section>

    <!-- 3. MAIN CONTENT SECTION -->
    <section class="section">
        <div class="container about-content">
            <div class="about-image">
                <img src="assets/images/501755748_1020474823598105_7453149769962878933_n.jpg" alt="A person wrapping a gift">
            </div>
            <div class="about-text">
                <h2>Crafting Happiness, One Gift at a Time.</h2>
                <p>
                    Savi’s creation  started with a simple idea: a gift is not just an item, it's a feeling. It's the warmth of a hug, the brightness of a smile, and the unspoken words of love and appreciation.
                </p>
                <p>
                    Founded by Savi, a passionate creator with an eye for detail, our little corner of the internet is dedicated to curating beautiful, high-quality gifts that help you express what's in your heart. Every product and package is chosen with care, ensuring it brings nothing but happiness to you and your loved ones.
                </p>
            </div>
        </div>
    </section>

    <!-- 4. OUR VALUES SECTION -->
    <section class="section values-section">
        <div class="container">
            <h2 style="text-align:center; font-size:2.5em; margin-bottom: 40px;">What We Stand For</h2>
            <div class="features-grid">
                <div class="feature-box">
                    <span class="icon">💖</span>
                    <h3>Creativity & Passion</h3>
                    <p>Every gift is a piece of art, crafted with passion and a creative touch to make it truly unique.</p>
                </div>
                <div class="feature-box">
                    <span class="icon">✨</span>
                    <h3>Unmatched Quality</h3>
                    <p>We source only the best materials and products, because your special moments deserve nothing less.</p>
                </div>
                <div class="feature-box">
                    <span class="icon">😊</span>
                    <h3>Customer Happiness</h3>
                    <p>Your satisfaction is our greatest reward. We're here to make your gifting experience joyful and seamless.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- 5. CALL TO ACTION SECTION -->
    <section class="section cta-banner">
        <div class="container">
            <h2 style="font-size:2em; margin-bottom: 10px;">Ready to find the perfect gift?</h2>
            <p style="font-size: 1.2em; color: #636e72; margin-bottom: 30px;">Browse our full collection and let us help you make someone's day special.</p>
            <a href="store.php" class="cta-btn">Explore The Store</a>
        </div>
    </section>

    <!-- 6. FOOTER (Consistent) -->
    <footer class="footer">
        <div class="container">
            <p>© <?php echo date("Y"); ?> Savi’s creation Corner. All Rights Reserved.</p>
        </div>
    </footer>

</body>

</html>