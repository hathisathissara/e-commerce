<?php
// Include the database connection file
require_once '../includes/db_connect.php';

// Check if variant_id is set in the URL
if (isset($_GET['variant_id']) && !empty($_GET['variant_id'])) {

    // Get the variant_id from the URL and convert it to an integer
    $variant_id_to_delete = intval($_GET['variant_id']);

    // --- First, find the image path to delete the physical file ---
    $sql_select = "SELECT image FROM product_variants WHERE variant_id = ?";
    if ($stmt_select = $conn->prepare($sql_select)) {
        $stmt_select->bind_param("i", $variant_id_to_delete);
        if ($stmt_select->execute()) {
            $result = $stmt_select->get_result();
            
            // Check if get_result() was successful AND a record was found
            if ($result && $result->num_rows == 1) {
                $variant = $result->fetch_assoc();
                
                // Check if an image path exists and the file is on the server
                if (!empty($variant['image']) && file_exists('../' . $variant['image'])) {
                    // Delete the physical image file
                    unlink('../' . $variant['image']);
                }
            }
        }
        $stmt_select->close();
    }

    // --- Now, prepare and execute the delete statement for the database record ---
    $sql_delete = "DELETE FROM product_variants WHERE variant_id = ?";

    if ($stmt_delete = $conn->prepare($sql_delete)) {
        // Bind the variant_id to the statement
        $stmt_delete->bind_param("i", $variant_id_to_delete);

        // **(FIX 1) EXECUTE THE DELETE QUERY **
        // Execute the statement and check if it was successful
        if ($stmt_delete->execute()) {
            
            // **(FIX 2) REDIRECT ONLY ON SUCCESS **
            // Deletion was successful, now prepare the redirect URL
            $redirect_url = "view_products.php"; // Default fallback
            if (isset($_GET['return_pid'])) {
                $redirect_url = "product_details.php?product_id=" . intval($_GET['return_pid']) . "&status=variant_deleted";
            }
            header("location: " . $redirect_url);
            exit();

        } else {
            // If deletion fails
            echo "Oops! Something went wrong while deleting the record. Please try again later.";
        }
        
        // **(FIX 3) CLOSE STATEMENT INSIDE THE IF BLOCK **
        $stmt_delete->close();

    } else {
        // If prepare() fails
        echo "Oops! Something went wrong with the database query preparation.";
    }

} else {
    // If no variant_id is provided, redirect back to the products page
    header("location: view_products.php");
    exit();
}

// Close the database connection
$conn->close();