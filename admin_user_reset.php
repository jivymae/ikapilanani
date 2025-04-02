<?php
session_start();
include 'db_config.php';

// Ensure the user is a patient (to prevent misuse)
if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin') {
    header('Location: admin_dashboard.php');
    exit();
}

$message = '';
$username = '';
$reset_code = '';
$new_password = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $reset_code = trim($_POST['reset_code']);
    $new_password = trim($_POST['new_password']);

    // Validate the new password (ensure it's not too short, for example)
    if (strlen($new_password) < 6) {
        $message = "Password must be at least 6 characters long.";
    } else {
        // Check if the reset code exists and is valid
        $stmt = $conn->prepare("SELECT user_id, reset_code, reset_code_expiry, status FROM password_reset_requests WHERE username = ? AND reset_code = ? AND status = 'pending'");
        $stmt->bind_param("ss", $username, $reset_code);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Fetch details
            $stmt->bind_result($user_id, $stored_reset_code, $reset_code_expiry, $status);
            $stmt->fetch();

            // Check if the reset code has expired
            if (strtotime($reset_code_expiry) < time()) {
                $message = "Reset code has expired.";
            } else {
                // Hash the new password
                $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

                // Update the password in the users table
                $stmt_update = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
                $stmt_update->bind_param("si", $hashed_password, $user_id);
                if ($stmt_update->execute()) {
                    // Mark the reset request as 'completed'
                    $stmt_update_status = $conn->prepare("UPDATE password_reset_requests SET status = 'completed' WHERE reset_code = ?");
                    $stmt_update_status->bind_param("s", $reset_code);
                    $stmt_update_status->execute();

                    $message = "Password has been successfully reset!";
                } else {
                    $message = "Failed to update the password. Please try again later.";
                }
                $stmt_update->close();
            }
            $stmt->close();
        } else {
            $message = "Invalid username or reset code.";
        }
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Your Password</title>
</head>
<body>
    <h1>Reset Your Password</h1>
    <form method="POST" action="">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>" required>
        <br>

        <label for="reset_code">Reset Code:</label>
        <input type="text" id="reset_code" name="reset_code" value="<?php echo htmlspecialchars($reset_code, ENT_QUOTES, 'UTF-8'); ?>" required>
        <br>

        <label for="new_password">New Password:</label>
        <input type="password" id="new_password" name="new_password" required>
        <br>

        <button type="submit">Reset Password</button>
    </form>

    <?php if (!empty($message)) { echo '<p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>'; } ?>

    <a href="login.php">Back to Login</a>
</body>
</html>
