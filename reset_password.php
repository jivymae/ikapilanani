<?php
session_start();
include 'db_config.php';

// Set the default timezone to the Philippines
date_default_timezone_set('Asia/Manila');

$message = '';

// Function to check password strength (alphanumeric and minimum 8 characters)
function isStrongPassword($password) {

    // Updated regex to allow alphanumeric and special characters
    return preg_match('/^(?=.*[a-zA-Z])(?=.*\d).{8,}$/', $password);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $reset_token = trim($_POST['reset_code']);
    $new_password = trim($_POST['new_password']);

    // Validate input
    if (empty($username) || empty($reset_token) || empty($new_password)) {
        $message = "All fields are required.";
    } elseif (!isStrongPassword($new_password)) {
        $message = "Password must be at least 8 characters long and include both letters and numbers (alphanumeric).";
    } else {
        // Validate the reset token (check if the token is valid and not expired)
        $stmt = $conn->prepare("SELECT user_id, expires_at, reset_token, ps_id FROM password_resets WHERE expires_at > NOW() AND user_id = (SELECT user_id FROM users WHERE username = ? AND (role = 'patient' OR role = 'dentist'))");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($user_id, $expires_at, $hashed_token, $ps_id);
            $stmt->fetch();

            // Verify the reset token
            if (password_verify($reset_token, $hashed_token)) {
                // Update the password in the users table
                $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
                $stmt->bind_param("si", $hashed_password, $user_id);
                $stmt->execute();

                // Optionally, delete the reset token after successful use
                $stmt = $conn->prepare("DELETE FROM password_resets WHERE ps_id = ?"); // Delete the token using the primary key (ps_id)
                $stmt->bind_param("i", $ps_id);
                $stmt->execute();

                $message = "Your password has been updated successfully.";
            } else {
                $message = "Invalid reset token.";
            }
        } else {
            $message = "Invalid reset token or it has expired.";
        }

        $stmt->close();
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
</head>
<body>
    <h1>Reset Password</h1>
    <form method="POST" action="">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>
        <br>
        <label for="reset_code">Reset Token:</label>
        <input type="text" id="reset_code" name="reset_code" required>
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
