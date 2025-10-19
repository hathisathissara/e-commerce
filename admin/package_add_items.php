<?php
session_start();
if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    header("location: index.php");
    exit;
}
require_once '../includes/db_connect.php';

if (!isset($_GET['package_id'])) {
    header("location: manage_packages.php");
    exit;
}
$package_id = intval($_GET['package_id']);

// --- ADD ITEM TO PACKAGE ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_item_to_package'])) {
    $variant_id = intval($_POST['variant_id']);
    $quantity = intval($_POST['quantity']);
    if ($variant_id > 0 && $quantity > 0) {
        $sql_add = "INSERT INTO package_items (package_id, variant_id, quantity) VALUES (?, ?, ?)";
        $stmt_add = $conn->prepare($sql_add);
        $stmt_add->bind_param("iii", $package_id, $variant_id, $quantity);
        $stmt_add->execute();
        $stmt_add->close();
    }
    header("Location: package_add_items.php?package_id=" . $package_id . "&status=item_added");
    exit;
}

// --- REMOVE ITEM FROM PACKAGE ---
if (isset($_GET['remove_item_id'])) {
    $item_to_remove = intval($_GET['remove_item_id']);
    $sql_remove = "DELETE FROM package_items WHERE item_id = ? AND package_id = ?";
    $stmt_remove = $conn->prepare($sql_remove);
    $stmt_remove->bind_param("ii", $item_to_remove, $package_id);
    $stmt_remove->execute();
    $stmt_remove->close();
    header("Location: package_add_items.php?package_id=" . $package_id . "&status=item_removed");
    exit;
}

// --- DATA FETCHING ---
$package = $conn->query("SELECT * FROM packages WHERE package_id = $package_id")->fetch_assoc();
if (!$package) {
    header("location: manage_packages.php");
    exit;
}
$all_variants = $conn->query("SELECT pv.variant_id, p.product_name, pv.variant_value FROM product_variants pv JOIN products p ON pv.product_id = p.product_id ORDER BY p.product_name, pv.variant_value ASC")->fetch_all(MYSQLI_ASSOC);
$package_contents = $conn->query("SELECT pi.item_id, p.product_name, pv.variant_value, pi.quantity FROM package_items pi JOIN product_variants pv ON pi.variant_id = pv.variant_id JOIN products p ON pv.product_id = p.product_id WHERE pi.package_id = $package_id")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Package Items</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Paste admin styles here -->
    <style>
        :root {
            --primary-color: #0984e3;
            --secondary-color: #e84393;
            --danger-color: #d63031;
            --success-color: #27ae60;
            --light-bg: #f5f6fa;
            --text-dark: #2d3436;
            --text-light: #636e72;
            --white: #ffffff;
            --border-color: #dfe6e9;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-bg);
            margin: 0;
            color: var(--text-dark);
        }

        .admin-wrapper {
            display: flex;
        }

        .sidebar {
            width: 250px;
            background-color: var(--text-dark);
            color: var(--white);
            height: 100vh;
            position: sticky;
            top: 0;
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
            background: rgba(0, 0, 0, 0.2);
        }

        .sidebar-header h2 {
            margin: 0;
        }

        .sidebar-nav {
            list-style: none;
            padding: 20px 0;
            margin: 0;
        }

        .sidebar-nav a {
            display: block;
            color: var(--white);
            padding: 15px 20px;
            text-decoration: none;
            transition: background 0.2s;
        }

        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background: var(--primary-color);
        }

        .sidebar-nav a.logout {
            background-color: var(--danger-color);
            position: absolute;
            bottom: 0;
            width: 100%;
            box-sizing: border-box;
        }

        .main-content {
            flex: 1;
            padding: 30px;
        }

        .main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .main-header a {
            text-decoration: none;
            color: var(--primary-color);
            font-weight: 600;
        }

        .content-box {
            background-color: var(--white);
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            font-weight: 600;
            display: block;
            margin-bottom: 8px;
        }

        select,
        input[type="number"] {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
        }

        button {
            background: var(--primary-color);
            color: var(--white);
            padding: 12px 20px;
            font-weight: 600;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            text-align: left;
            padding: 12px 15px;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            background-color: var(--light-bg);
        }

        .remove-link {
            color: var(--danger-color);
            text-decoration: none;
            font-weight: 600;
        }

        .status-msg {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            color: var(--white);
        }

        .status-msg.success {
            background-color: var(--success-color);
        }

        .status-msg.removed {
            background-color: var(--danger-color);
        }

        /* A new two-column layout */
        .columns-container {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 30px;
        }

        @media(max-width: 900px) {
            .columns-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="admin-wrapper">
        <?php
        require "layout/header.php"
        ?>
        <main class="main-content">
            <header class="main-header">
                <h1>Editing Package: "<?= htmlspecialchars($package['package_name']) ?>"</h1>
                <a href="manage_packages.php">← Back to All Packages</a>
            </header>

            <!-- Status message -->
            <?php if (isset($_GET['status'])): ?>
                <?php if ($_GET['status'] == 'item_added'): ?><div class="status-msg success">Item added to the package successfully!</div><?php endif; ?>
                <?php if ($_GET['status'] == 'item_removed'): ?><div class="status-msg removed">Item removed from the package.</div><?php endif; ?>
            <?php endif; ?>

            <div class="columns-container">
                <div class="content-box">
                    <h2>Add New Item</h2>
                    <form action="package_add_items.php?package_id=<?= $package_id ?>" method="post">
                        <div class="form-group">
                            <label>Select an Item to Add</label>
                            <select name="variant_id" required>
                                <option value="" disabled selected>-- Choose a variant --</option>
                                <?php foreach ($all_variants as $variant): ?>
                                    <option value="<?= $variant['variant_id'] ?>">
                                        <?= htmlspecialchars($variant['product_name']) . ' - ' . htmlspecialchars($variant['variant_value']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Quantity in this Package</label>
                            <input type="number" name="quantity" value="1" min="1" required>
                        </div>
                        <button type="submit" name="add_item_to_package">Add Item to Package</button>
                    </form>
                </div>

                <div class="content-box">
                    <h2>Items Currently in this Package</h2>
                    <?php if (empty($package_contents)): ?>
                        <p>No items have been added to this package yet. Use the form on the left.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Item Name</th>
                                    <th>Quantity</th>
                                    <th style="text-align:right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($package_contents as $content): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($content['product_name']) ?></strong> (<?= htmlspecialchars($content['variant_value']) ?>)</td>
                                        <td><?= $content['quantity'] ?></td>
                                        <td style="text-align:right"><a href="package_add_items.php?package_id=<?= $package_id ?>&remove_item_id=<?= $content['item_id'] ?>" onclick="return confirm('Are you sure you want to remove this item from the package?');" class="remove-link">Remove</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>

</html>