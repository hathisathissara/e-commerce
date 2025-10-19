<?php
session_start();
if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    header("location: index.php");
    exit;
}
require_once '../includes/db_connect.php';

// Define the available types of slides for the dropdown
$predefined_titles = [
    "Customer Feedback",
    "New Arrival",
    "Special Offer",
    "Seasonal Promotion",
    "Just For You"
];

// --- ACTION HANDLER: This block handles all POST and GET actions ---
// --- Handle Add New Slide Form ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_slide'])) {
    $title = trim($_POST['title']);
    $subtitle = trim($_POST['subtitle']);
    $link_url = trim($_POST['link_url']);
    $display_order = intval($_POST['display_order']);
    $image_path = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = '../assets/images/slider/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_name = time() . '_' . basename($_FILES['image']['name']);
        $target_file = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image_path = 'assets/images/slider/' . $file_name;

            $sql = "INSERT INTO slides (title, subtitle, image_path, link_url, display_order) VALUES (?, ?, ?, ?, ?)";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("ssssi", $title, $subtitle, $image_path, $link_url, $display_order);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
    header("location: manage_slider.php?status=added");
    exit();
}

// --- Handle Delete Slide ---
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    // First, get the image path to delete the physical file
    $stmt_select = $conn->prepare("SELECT image_path FROM slides WHERE slide_id = ?");
    $stmt_select->bind_param("i", $delete_id);
    $stmt_select->execute();
    $result_select = $stmt_select->get_result();
    if ($result_select && $row = $result_select->fetch_assoc()) {
        if (!empty($row['image_path']) && file_exists('../' . $row['image_path'])) {
            unlink('../' . $row['image_path']);
        }
    }
    if ($stmt_select) $stmt_select->close();

    // Then, delete the record from the database
    $stmt_delete = $conn->prepare("DELETE FROM slides WHERE slide_id = ?");
    $stmt_delete->bind_param("i", $delete_id);
    $stmt_delete->execute();
    $stmt_delete->close();

    header("location: manage_slider.php?status=deleted");
    exit();
}


// --- DATA FETCHER: Fetch all slides to display on the page ---
$slides = [];
$sql_fetch = "SELECT * FROM slides ORDER BY display_order ASC, slide_id DESC";
$result_fetch = $conn->query($sql_fetch);
if ($result_fetch) {
    $slides = $result_fetch->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Slider - Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0984e3; --secondary-color: #6c5ce7; --danger-color: #d63031;
            --success-color: #27ae60; --light-bg: #f5f6fa; --text-dark: #2d3436;
            --text-light: #636e72; --white: #ffffff; --border-color: #dfe6e9;
        }
        body { font-family: 'Poppins', sans-serif; background-color: var(--light-bg); margin: 0; color: var(--text-dark); }
        .admin-wrapper { display: flex; }
        .sidebar { width: 250px; background-color: var(--text-dark); color: var(--white); height: 100vh; position: sticky; top: 0; }
        .sidebar-header { padding: 20px; text-align: center; background: rgba(0,0,0,0.2); }
        .sidebar-header h2 { margin: 0; }
        .sidebar-nav { list-style: none; padding: 20px 0; margin: 0; }
        .sidebar-nav a { display: block; color: var(--white); padding: 15px 20px; text-decoration: none; transition: background 0.2s; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background: var(--primary-color); }
        .sidebar-nav a.logout { background-color: var(--danger-color); position: absolute; bottom: 0; width: 100%; box-sizing: border-box; }
        .main-content { flex: 1; padding: 30px; }
        .main-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .content-box { background-color: var(--white); padding: 25px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px; }
        h1, h2 { margin-top: 0; }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 15px; }
        label { font-weight: 600; display: block; margin-bottom: 8px; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 5px; font-family: 'Poppins', sans-serif; font-size: 1em; }
        .action-btn { background: var(--primary-color); color: var(--white); padding: 12px 20px; font-weight: 600; border: none; border-radius: 5px; cursor: pointer; width: 100%; margin-top: 10px; }
        
        /* --- NEW STYLES for the Slide Grid --- */
        .slides-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr); /* Default to 3 columns */
            gap: 20px;
        }
        /* Responsive behavior */
        @media (max-width: 992px) { .slides-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 576px) { .slides-grid { grid-template-columns: 1fr; } }
        
        .slide-card {
            background-color: var(--white); border-radius: 8px; overflow: hidden;
            box-shadow: 0 4px 10px rgba(0,0,0,0.07); display: flex; flex-direction: column;
            border: 1px solid var(--border-color);
        }
        .slide-card img { width: 100%; height: 180px; object-fit: cover; }
        .slide-card-content { padding: 15px; flex-grow: 1; display: flex; flex-direction: column; justify-content: space-between; }
        .slide-card-content h4 { margin: 0 0 5px; font-size: 1.1em; color: var(--text-dark); }
        .slide-card-content p { margin: 0 0 15px; font-size: 0.9em; color: var(--text-light); flex-grow: 1; }
        .slide-card-actions { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-color); padding-top: 10px; margin-top: 10px; }
        .delete-btn { background-color: var(--danger-color); color: white; text-decoration: none; padding: 8px 12px; border-radius: 4px; font-size: 0.9em; }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php
        require "layout/header.php"
        ?>

        <main class="main-content">
            <header class="main-header"><h1>Manage Homepage Slider</h1></header>
            
            <div class="content-box">
                <h2>Add New Slide</h2>
                <form action="manage_slider.php" method="post" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="form-group"><label>Slide Type</label><select name="title" required><option value="" disabled selected>-- Select Type --</option><?php foreach ($predefined_titles as $t): ?><option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option><?php endforeach; ?></select></div>
                        <div class="form-group"><label>Subtitle</label><input type="text" name="subtitle" placeholder="e.g., Customer Name or Offer details"></div>
                        <div class="form-group"><label>Link URL (Optional)</label><input type="text" name="link_url" placeholder="e.g., store.php"></div>
                        <div class="form-group"><label>Display Order</label><input type="number" name="display_order" value="0" placeholder="e.g., 10, 20"></div>
                    </div>
                    <div class="form-group" style="margin-top:20px;"><label>Image (Recommended size: 1200x500 pixels)</label><input type="file" name="image" required style="border: 1px dashed var(--border-color); padding: 20px;"></div>
                    <button type="submit" name="add_slide" class="action-btn">Add Slide</button>
                </form>
            </div>

            <div class="content-box">
                <h2>Current Slides</h2>
                <?php if (empty($slides)): ?>
                    <p>No slides found. Add one using the form above.</p>
                <?php else: ?>
                    <div class="slides-grid">
                        <?php foreach ($slides as $slide): ?>
                            <div class="slide-card">
                                <img src="../<?= htmlspecialchars($slide['image_path']) ?>" alt="<?= htmlspecialchars($slide['title']) ?>">
                                <div class="slide-card-content">
                                    <div>
                                        <h4><?= htmlspecialchars($slide['title']) ?></h4>
                                        <p><?= htmlspecialchars($slide['subtitle']) ?></p>
                                    </div>
                                    <div class="slide-card-actions">
                                        <small>Order: <?= htmlspecialchars($slide['display_order']) ?></small>
                                        <a href="manage_slider.php?delete_id=<?= $slide['slide_id'] ?>" class="delete-btn" onclick="return confirm('Are you sure?')">Delete</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>