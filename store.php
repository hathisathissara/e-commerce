<?php
// Must be at the top
require_once 'includes/cart_functions.php';
require_once 'includes/db_connect.php';

// --- PHP LOGIC: Fetch Packages ---
$sql_packages = "SELECT * FROM packages WHERE is_active = 1";
$packages_result = $conn->query($sql_packages);
$packages = $packages_result ? $packages_result->fetch_all(MYSQLI_ASSOC) : [];

// --- PHP LOGIC: Fetch Individual Products (WITH STOCK) ---
$sql_products = "SELECT p.product_id, p.product_name, p.description,
        GROUP_CONCAT(pv.variant_id ORDER BY pv.variant_id SEPARATOR '||') as variant_ids,
        GROUP_CONCAT(pv.variant_value ORDER BY pv.variant_id SEPARATOR '||') as variant_values,
        GROUP_CONCAT(pv.price ORDER BY pv.variant_id SEPARATOR '||') as prices,
        GROUP_CONCAT(IFNULL(pv.stock_quantity, 0) ORDER BY pv.variant_id SEPARATOR '||') as stock_quantities,
        GROUP_CONCAT(IFNULL(pv.image, 'default.jpg') ORDER BY pv.variant_id SEPARATOR '||') as images
        FROM products p
        LEFT JOIN product_variants pv ON p.product_id = pv.product_id
        GROUP BY p.product_id ORDER BY p.created_at DESC";

$result_products = $conn->query($sql_products);
$all_products = [];
if ($result_products) {
    while ($row = $result_products->fetch_assoc()) {
        $variants = [];
        if (!empty($row['variant_ids'])) {
            $ids = explode('||', $row['variant_ids']);
            $values = explode('||', $row['variant_values']);
            $prices = explode('||', $row['prices']);
            $stock_quantities = explode('||', $row['stock_quantities']);
            $images = explode('||', $row['images']);

            for ($i = 0; $i < count($ids); $i++) {
                if (!empty($ids[$i])) {
                    $variants[] = [
                        'id' => $ids[$i],
                        'value' => $values[$i],
                        'price' => $prices[$i],
                        'stock' => intval($stock_quantities[$i]),
                        'image' => ($images[$i] == 'default.jpg' ? 'https://via.placeholder.com/400' : $images[$i])
                    ];
                }
            }
        }
        $row['variants_data'] = $variants;
        $all_products[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="si">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Our Store - Savi's creation </title>
    <!-- Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet" />
    <style>
        :root {
            --primary-color: #e84393;
            --secondary-color: #0984e3;
            --text-dark: #333333;
            --text-light: #f1f2f6;
            --background-color: #f7f1e3;
            --out-of-stock-color: #e74c3c;
            --disabled-color: #bdc3c7;
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
            padding: 40px 20px;
        }

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

        .page-title {
            text-align: center;
            margin-bottom: 40px;
            font-size: 2.5em;
            font-weight: 700;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 30px;
        }

        @media (max-width: 992px) {
            .product-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 576px) {
            .product-grid {
                grid-template-columns: 1fr;
            }
        }

        .product-card {
            background: #fff;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.12);
        }

        .product-card.out-of-stock {
            opacity: 0.7;
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

        .stock-status {
            font-size: 0.9em;
            font-weight: 600;
            margin: 5px 0;
            padding: 5px 10px;
            border-radius: 15px;
            text-align: center;
        }

        .stock-status.in-stock {
            background-color: #d4edda;
            color: #155724;
        }

        .stock-status.low-stock {
            background-color: #fff3cd;
            color: #856404;
        }

        .stock-status.out-of-stock {
            background-color: #f8d7da;
            color: var(--out-of-stock-color);
        }

        .open-modal-btn,
        .add-package-btn,
        .view-items-btn {
            display: block;
            width: 100%;
            margin-top: 10px;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-align: center;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .open-modal-btn {
            background: var(--secondary-color);
            color: white;
        }

        .open-modal-btn:disabled {
            background: var(--disabled-color);
            cursor: not-allowed;
        }

        .add-package-btn {
            background: var(--primary-color);
            color: white;
        }

        .view-items-btn {
            background-color: #2d3436;
            color: #fff;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 999;
            padding-top: 80px;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.6);
        }

        .modal-content {
            background-color: #fff;
            margin: auto;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 700px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        @media (min-width: 768px) {
            .modal-content {
                flex-direction: row;
                gap: 30px;
            }
        }

        .modal-image {
            width: 100%;
        }

        .modal-image img {
            width: 100%;
            border-radius: 8px;
        }

        @media (min-width: 768px) {
            .modal-image {
                width: 45%;
            }

            .modal-details {
                width: 55%;
            }
        }

        .modal-details {
            width: 100%;
        }

        .close-btn,
        .close {
            align-self: flex-end;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #aaa;
            margin-top: -10px;
        }

        .close-btn:hover,
        .close:hover {
            color: red;
        }

        .package-item-list {
            margin-top: 20px;
            padding-left: 20px;
        }

        .package-item-list li {
            margin-bottom: 10px;
            list-style-type: disc;
        }

        .button-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        /* Modal Form Controls */
        .modal select,
        .modal input[type="number"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 1em;
        }

        .modal .price {
            font-size: 1.5em;
            font-weight: bold;
            color: var(--primary-color);
            margin: 20px 0;
        }

        .modal .stock-info {
            font-size: 1em;
            margin: 10px 0;
        }

        .add-to-cart-btn {
            background-color: var(--primary-color);
            color: white;
            padding: 12px;
            width: 100%;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1em;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .add-to-cart-btn:hover {
            background-color: #d63074;
        }

        .add-to-cart-btn:disabled {
            background-color: var(--disabled-color);
            cursor: not-allowed;
        }

        .out-of-stock-overlay {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: var(--out-of-stock-color);
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: 600;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 10px 15px;
            border-radius: 8px;
            margin: 10px 0;
            font-weight: 600;
            text-align: center;
            display: none;
        }

        .success-message.show {
            display: block;
        }

        /* Package Success Message */
        .package-success-message {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #d4edda;
            color: #155724;
            border: 2px solid #c3e6cb;
            padding: 20px 30px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1.1em;
            text-align: center;
            z-index: 1000;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            display: none;
            animation: slideIn 0.3s ease-out;
        }

        .package-success-message.show {
            display: block;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translate(-50%, -60%);
            }

            to {
                opacity: 1;
                transform: translate(-50%, -50%);
            }
        }

        /* Overlay for package success message */
        .success-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
        }

        .success-overlay.show {
            display: block;
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

    <main class="section">
        <div class="container">
            <!-- Special Packages Section -->
            <?php if (!empty($packages)): ?>
                <div class="packages-section">
                    <h1 class="page-title">Special Gift Packages</h1>
                    <div class="product-grid">
                        <?php foreach ($packages as $pkg): ?>
                            <div class="product-card">
                                <img src="<?= htmlspecialchars($pkg['package_image'] ?? 'https://via.placeholder.com/300') ?>" alt="<?= htmlspecialchars($pkg['package_name']) ?>">
                                <div class="product-info">
                                    <div>
                                        <h3><?= htmlspecialchars($pkg['package_name']) ?></h3>
                                        <p class="price">LKR <?= number_format($pkg['total_price'], 2) ?></p>
                                    </div>

                                    <div class="button-group">
                                        <button type="button" class="view-items-btn" onclick="openPackagePopup(<?= $pkg['package_id'] ?>)">
                                            View Package Items
                                        </button>

                                        <!-- Add to Cart -->
                                        <form id="addPackageForm_<?= $pkg['package_id'] ?>" onsubmit="return submitPackage(event, <?= $pkg['package_id'] ?>)">
                                            <input type="hidden" name="action" value="add_package" />
                                            <input type="hidden" name="package_id" value="<?= $pkg['package_id'] ?>" />
                                            <button type="submit" class="add-package-btn">Add Package to Cart</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Modal for Package Items -->
                <div id="packageModal" class="modal" style="display:none;">
                    <div class="modal-content">
                        <span class="close" onclick="closePackagePopup()">&times;</span>
                        <h2>Package Items</h2>
                        <div id="packageItemsContent">Loading...</div>
                    </div>
                </div>
            <?php endif; ?>

            <h1 class="page-title">Our Full Collection</h1>
            <div class="product-grid">
                <?php foreach ($all_products as $index => $product): ?>
                    <?php if (!empty($product['variants_data'])): ?>
                        <?php
                        $first_variant = $product['variants_data'][0];
                        $first_image = $first_variant['image'];
                        $start_price = $first_variant['price'];

                        // Check if all variants are out of stock
                        $all_out_of_stock = true;
                        $min_stock = PHP_INT_MAX;
                        foreach ($product['variants_data'] as $variant) {
                            if ($variant['stock'] > 0) {
                                $all_out_of_stock = false;
                            }
                            $min_stock = min($min_stock, $variant['stock']);
                        }

                        // Determine stock status for display
                        $stock_status = '';
                        $stock_class = '';
                        if ($all_out_of_stock) {
                            $stock_status = 'Out of Stock';
                            $stock_class = 'out-of-stock';
                        } elseif ($min_stock <= 5) {
                            $stock_status = 'Low Stock';
                            $stock_class = 'low-stock';
                        } else {
                            $stock_status = 'In Stock';
                            $stock_class = 'in-stock';
                        }
                        ?>
                        <div class="product-card <?= $all_out_of_stock ? 'out-of-stock' : '' ?>">
                            <?php if ($all_out_of_stock): ?>
                                <div class="out-of-stock-overlay">Out of Stock</div>
                            <?php endif; ?>
                            <img src="<?php echo htmlspecialchars($first_image); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                            <div class="product-info">
                                <div>
                                    <h3><?php echo htmlspecialchars($product['product_name']); ?></h3>
                                    <p class="price">From LKR <?php echo number_format($start_price, 2); ?></p>
                                    <div class="stock-status <?= $stock_class ?>">
                                        <?= $stock_status ?>
                                    </div>
                                </div>
                                <button class="open-modal-btn" onclick="openModal(<?php echo $index; ?>)" <?= $all_out_of_stock ? 'disabled' : '' ?>>
                                    <?= $all_out_of_stock ? 'Out of Stock' : 'Choose Your Option' ?>
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <!-- The Modal Popup -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal()">×</span>
            <div class="modal-image">
                <img id="modalProductImage" src="" alt="Product Image" />
            </div>
            <div class="modal-details">
                <h2 id="modalProductName">Product Name</h2>
                <p id="modalProductDesc">Product description goes here.</p>
                <form id="addToCartForm" onsubmit="return submitCart(event)">
                    <input type="hidden" name="action" value="add" />
                    <input type="hidden" name="variant_id" id="modalVariantId" value="" />
                    <div style="margin: 15px 0;">
                        <label><b>Select Type:</b></label><br />
                        <select id="modalVariantSelect" name="variant_id" style="width:100%; margin-top:5px; padding:10px;"></select>
                    </div>
                    <div style="margin: 15px 0;">
                        <label><b>Quantity:</b></label><br />
                        <input type="number" id="modalQuantity" name="quantity" value="1" min="1" max="100" style="width:100px; margin-top:5px; padding:10px;" />
                    </div>
                    <div id="modalPrice" class="price">LKR 0.00</div>
                    <div id="modalStockInfo" class="stock-info"></div>
                    <div id="successMessage" class="success-message">
                        Item added to cart successfully!
                    </div>
                    <button type="submit" id="modalAddToCartBtn" class="add-to-cart-btn">Add to Cart</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Package Success Message -->
    <div id="successOverlay" class="success-overlay"></div>
    <div id="packageSuccessMessage" class="package-success-message">
        Package added to cart successfully!
    </div>
    <!-- 6. FOOTER (Consistent) -->
    <?php require "layout/footer.php" ?>



    <script>
        const productsData = <?php echo json_encode($all_products); ?>;
        const modal = document.getElementById("productModal"),
            modalProductName = document.getElementById("modalProductName"),
            modalProductDesc = document.getElementById("modalProductDesc"),
            modalProductImage = document.getElementById("modalProductImage"),
            modalVariantSelect = document.getElementById("modalVariantSelect"),
            modalVariantIdInput = document.getElementById("modalVariantId"),
            modalPrice = document.getElementById("modalPrice"),
            modalStockInfo = document.getElementById("modalStockInfo"),
            modalQuantity = document.getElementById("modalQuantity"),
            modalAddToCartBtn = document.getElementById("modalAddToCartBtn"),
            successMessage = document.getElementById("successMessage"),
            packageSuccessMessage = document.getElementById("packageSuccessMessage"),
            successOverlay = document.getElementById("successOverlay");

        function openModal(e) {
            const t = productsData[e];
            modalProductName.textContent = t.product_name;
            modalProductDesc.textContent = t.description;
            modalVariantSelect.innerHTML = "";

            // Hide success message when opening modal
            successMessage.classList.remove("show");

            t.variants_data.forEach(variant => {
                const option = document.createElement("option");
                option.value = variant.id;
                option.textContent = variant.value + (variant.stock <= 0 ? " (Out of Stock)" : "");
                option.dataset.price = variant.price;
                option.dataset.image = variant.image;
                option.dataset.stock = variant.stock;
                option.disabled = variant.stock <= 0;
                modalVariantSelect.appendChild(option);
            });

            updateModalInfo();
            modal.style.display = "block";
        }

        function closeModal() {
            modal.style.display = "none";
            // Hide success message when closing modal
            successMessage.classList.remove("show");
        }

        modalVariantSelect.addEventListener("change", updateModalInfo);

        function updateModalInfo() {
            const selectedOption = modalVariantSelect.options[modalVariantSelect.selectedIndex];
            if (selectedOption) {
                const stock = parseInt(selectedOption.dataset.stock);

                modalVariantIdInput.value = selectedOption.value;
                modalPrice.textContent = "LKR " + parseFloat(selectedOption.dataset.price).toFixed(2);
                modalProductImage.src = selectedOption.dataset.image;

                // Update stock info
                if (stock <= 0) {
                    modalStockInfo.innerHTML = '<span style="color: var(--out-of-stock-color); font-weight: bold;">Out of Stock</span>';
                    modalQuantity.disabled = true;
                    modalQuantity.value = 0;
                    modalQuantity.max = 0;
                    modalAddToCartBtn.disabled = true;
                    modalAddToCartBtn.textContent = "Out of Stock";
                } else {
                    if (stock <= 5) {
                        modalStockInfo.innerHTML = `<span style="color: #856404; font-weight: bold;">Only ${stock} items left!</span>`;
                    } else {
                        modalStockInfo.innerHTML = `<span style="color: #155724; font-weight: bold;">${stock} items available</span>`;
                    }
                    modalQuantity.disabled = false;
                    modalQuantity.value = 1;
                    modalQuantity.max = stock;
                    modalAddToCartBtn.disabled = false;
                    modalAddToCartBtn.textContent = "Add to Cart";
                }
            }
        }

        window.onclick = function(e) {
            if (e.target == modal) closeModal();
        };

        // AJAX submit for product add to cart
        function submitCart(e) {
            e.preventDefault();

            const selectedOption = modalVariantSelect.options[modalVariantSelect.selectedIndex];
            const stock = parseInt(selectedOption.dataset.stock);
            const quantity = parseInt(modalQuantity.value);

            if (stock <= 0) {
                alert("This item is out of stock!");
                return false;
            }

            if (quantity > stock) {
                alert(`Only ${stock} items available in stock!`);
                return false;
            }

            const form = document.getElementById("addToCartForm");
            const formData = new FormData(form);

            fetch("includes/cart_functions.php", {
                    method: "POST",
                    body: formData
                })
                .then(res => res.text())
                .then(() => {
                    updateCartCount();
                    // Show success message in modal
                    successMessage.classList.add("show");

                    // Auto-hide message after 3 seconds
                    setTimeout(() => {
                        successMessage.classList.remove("show");
                    }, 3000);
                })
                .catch(console.error);

            return false;
        }

        // AJAX submit for package add to cart
        function submitPackage(e, packageId) {
            e.preventDefault();

            const form = document.getElementById("addPackageForm_" + packageId);
            const formData = new FormData(form);

            fetch("includes/cart_functions.php", {
                    method: "POST",
                    body: formData
                })
                .then(res => res.text())
                .then(() => {
                    updateCartCount();
                    showPackageSuccessMessage();
                })
                .catch(console.error);

            return false;
        }

        function showPackageSuccessMessage() {
            successOverlay.classList.add("show");
            packageSuccessMessage.classList.add("show");

            // Auto-hide after 3 seconds
            setTimeout(() => {
                hidePackageSuccessMessage();
            }, 3000);
        }

        function hidePackageSuccessMessage() {
            successOverlay.classList.remove("show");
            packageSuccessMessage.classList.remove("show");
        }

        // Close package success message when clicking overlay
        successOverlay.addEventListener("click", hidePackageSuccessMessage);

        function updateCartCount() {
            fetch("includes/cart_count.php")
                .then(res => res.text())
                .then(count => {
                    document.querySelector(".cart-count").textContent = count;
                });
        }

        function openPackagePopup(packageId) {
            const modal = document.getElementById("packageModal");
            const content = document.getElementById("packageItemsContent");

            content.innerHTML = "Loading...";

            fetch("get_package_items.php?package_id=" + packageId)
                .then(response => response.json())
                .then(data => {
                    if (data.length > 0) {
                        const list = document.createElement("ul");
                        list.classList.add("package-item-list");

                        data.forEach(item => {
                            const li = document.createElement("li");
                            li.textContent = `${item.product_name} (${item.variant_value}) x ${item.quantity}`;
                            list.appendChild(li);
                        });

                        content.innerHTML = '';
                        content.appendChild(list);
                    } else {
                        content.innerHTML = "No items found in this package.";
                    }
                })
                .catch(() => {
                    content.innerHTML = "Failed to load items.";
                });

            modal.style.display = "block";
        }

        function closePackagePopup() {
            document.getElementById("packageModal").style.display = "none";
        }

        // Close modal when clicked outside
        window.onclick = function(event) {
            const modal = document.getElementById("packageModal");
            if (event.target === modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>

</html>