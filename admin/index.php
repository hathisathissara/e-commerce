<?php
// Start the session
session_start();

// If user is already logged in, redirect to the dashboard
if (isset($_SESSION["admin_loggedin"]) && $_SESSION["admin_loggedin"] === true) {
    header("location: dashboard.php");
    exit;
}

require_once '../includes/db_connect.php';

$username = $password = "";
$username_err = $password_err = $login_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Check if username is empty
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter username.";
    } else {
        $username = trim($_POST["username"]);
    }
    
    // Check if password is empty
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validate credentials
    if (empty($username_err) && empty($password_err)) {
        $sql = "SELECT id, username, password FROM admins WHERE username = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_username);
            $param_username = $username;
            
            if ($stmt->execute()) {
                $stmt->store_result();
                
                // Check if username exists
                if ($stmt->num_rows == 1) {
                    $stmt->bind_result($id, $username, $hashed_password);
                    if ($stmt->fetch()) {
                        // Verify password
                        if (password_verify($password, $hashed_password)) {
                            // Password is correct, so start a new session
                            session_start();
                            
                            // Store data in session variables
                            $_SESSION["admin_loggedin"] = true;
                            $_SESSION["admin_id"] = $id;
                            $_SESSION["admin_username"] = $username;
                            
                            // Redirect user to dashboard
                            header("location: dashboard.php");
                            exit();
                        } else {
                            $login_err = "Invalid username or password.";
                        }
                    }
                } else {
                    $login_err = "Invalid username or password.";
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            $stmt->close();
        }
    }
    $conn->close();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-g"><title>Admin Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root{--primary-color:#0984e3;--light-bg:#f5f6fa;--text-dark:#2d3436;--danger-color:#d63031;--white:#ffffff;--border-color:#dfe6e9;}
        body { font-family: 'Poppins', sans-serif; background: var(--light-bg); display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-wrapper { background: var(--white); padding: 40px; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); width: 360px; text-align: center; }
        .login-wrapper h2 { margin-top: 0; color: var(--text-dark); font-weight: 700; font-size: 1.8em; }
        .login-wrapper p { color: #636e72; margin-bottom: 25px; }
        .form-group { margin-bottom: 20px; text-align: left; }
        label { font-weight: 600; display: block; margin-bottom: 8px; }
        input[type="text"], input[type="password"] { width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 5px; box-sizing: border-box; }
        input[type="submit"] { width: 100%; border: none; background: var(--primary-color); color: var(--white); padding: 14px; border-radius: 5px; cursor: pointer; font-size: 1.1em; font-weight: 600; }
        .alert { padding: 12px; background: #ff7675; color: var(--white); border-radius: 5px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <h2>Savi’s creations Admin</h2>
        <p>Please enter your credentials to login.</p>
        <?php if (!empty($login_err)): echo '<div class="alert">' . $login_err . '</div>'; endif; ?>
        <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group"><label>Username</label><input type="text" name="username" required></div>
            <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
            <div class="form-group" style="margin-top:30px;"><input type="submit" value="Login"></div>
        </form>
    </div>
</body>
</html>