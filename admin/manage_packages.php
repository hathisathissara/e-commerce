<?php
session_start();
if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    header("location: index.php");
    exit;
}
require_once '../includes/db_connect.php';

// --- HANDLE DELETE PACKAGE ---
if (isset($_GET['delete_pkg_id'])) {
    $delete_id = intval($_GET['delete_pkg_id']);
    // Optional: First, get the image path to delete the file
    $stmt_select = $conn->prepare("SELECT package_image FROM packages WHERE package_id = ?");
    $stmt_select->bind_param("i", $delete_id);
    $stmt_select->execute();
    $result_select = $stmt_select->get_result();
    if ($result_select && $row = $result_select->fetch_assoc()) {
        if (!empty($row['package_image']) && file_exists('../' . $row['package_image'])) {
            unlink('../' . $row['package_image']);
        }
    }
    if ($stmt_select) $stmt_select->close();

    // Now delete the package. `ON DELETE CASCADE` in the DB will auto-delete related package_items.
    $stmt_delete = $conn->prepare("DELETE FROM packages WHERE package_id = ?");
    $stmt_delete->bind_param("i", $delete_id);
    $stmt_delete->execute();
    $stmt_delete->close();

    header("location: manage_packages.php?status=deleted");
    exit();
}


// --- HANDLE ADD NEW PACKAGE (Step 1) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_package'])) {
    $name = trim($_POST['package_name']);
    $price = trim($_POST['total_price']);
    $desc = trim($_POST['description']);
    $image_path = null;

    if (isset($_FILES['package_image']) && $_FILES['package_image']['error'] == 0) {
        $upload_dir = '../assets/images/packages/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        $file_name = 'pkg_' . time() . '_' . basename($_FILES['package_image']['name']);
        $target_file = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES['package_image']['tmp_name'], $target_file)) {
            $image_path = 'assets/images/packages/' . $file_name;
        }
    }

    $sql = "INSERT INTO packages (package_name, total_price, description, package_image) VALUES (?, ?, ?, ?)";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sdss", $name, $price, $desc, $image_path);
        $stmt->execute();
        $new_package_id = $conn->insert_id;
        $stmt->close();
        // Redirect to a page to add items to this new package
        header("location: package_add_items.php?package_id=" . $new_package_id);
        exit();
    }
}

// --- GET ALL PACKAGES TO DISPLAY ---
$sql_get_packages = "SELECT * FROM packages ORDER BY package_id DESC";
$packages = $conn->query($sql_get_packages)->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Packages</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Paste admin styles here from dashboard.php for consistency -->
    <style>
       :root {
            --primary-color: #0984e3;
            /* Blue for admin theme */
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

        .content-box {
            background-color: var(--white);
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        
        /* Form specific styles */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            font-weight: 600;
            display: block;
            margin-bottom: 8px;
        }

        input[type="text"],
        input[type="number"],
        textarea,
        input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
        }

        textarea {
            min-height: 80px;
            resize: vertical;
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

        /* Table styles */
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

        .actions a {
            text-decoration: none;
            padding: 8px 12px;
            font-size: 0.9em;
            border-radius: 5px;
            color: var(--white);
            margin-right: 5px;
        }

        .manage-btn {
            background: var(--secondary-color);
        }

        .delete-btn {
            background: var(--danger-color);
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
                <h1>Manage Packages</h1>
            </header>

            <div class="content-box">
                <h2>Create New Package</h2>
                <form action="manage_packages.php" method="post" enctype="multipart/form-data" class="form-grid">
                    <div class="form-group"><label>Package Name</label><input type="text" name="package_name" placeholder="e.g., Birthday Surprise" required></div>
                    <div class="form-group"><label>Package Price (LKR)</label><input type="number" step="0.01" name="total_price" placeholder="e.g., 4500.00" required></div>
                    <div class="form-group" style="grid-column: 1 / -1;"><label>Description</label><textarea name="description" placeholder="Short description..."></textarea></div>
                    <div class="form-group" style="grid-column: 1 / -1;"><label>Package Display Image</label><input type="file" name="package_image"></div>
                    <div class="form-group" style="grid-column: 1 / -1;"><button type="submit" name="add_package">Save & Add Items to Package</button></div>
                </form>
            </div>

            <div class="content-box">
                <h2>Existing Packages</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Package Name</th>
                            <th>Price</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($packages as $pkg): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($pkg['package_name']) ?></strong></td>
                                <td>LKR <?= number_format($pkg['total_price'], 2) ?></td>
                                <td class="actions">
                                    <a href="package_add_items.php?package_id=<?= $pkg['package_id'] ?>" class="manage-btn">Manage Items</a>
                                    <a href="manage_packages.php?delete_pkg_id=<?= $pkg['package_id'] ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this ENTIRE package and its items?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($packages)): ?>
                            <tr>
                                <td colspan="3" style="text-align:center;">No packages found. Create one above!</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>

</html>