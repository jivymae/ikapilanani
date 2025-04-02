<?php
include 'db_config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if all required fields are set
    if (isset($_POST['username'], $_POST['password'], $_POST['confirm_password'], $_POST['first_name'], $_POST['last_name'], $_POST['email'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $email = $_POST['email'];

        // Check if passwords match
        if ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } elseif (!isValidPassword($password)) {
            $error = 'Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, and one number.';
        } else {
            // Hash the password before storing it
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Prepare SQL query to insert the admin user
            $query = "INSERT INTO users (username, password_hash, role, first_name, last_name, email, created_at) VALUES (?, ?, 'admin', ?, ?, ?, NOW())";
            $stmt = $conn->prepare($query);

            if ($stmt === false) {
                $error = 'Prepare failed: ' . $conn->error;
            } else {
                $stmt->bind_param("sssss", $username, $hashed_password, $first_name, $last_name, $email);

                if ($stmt->execute()) {
                    echo "Admin registered successfully.";
                } else {
                    $error = 'Error: ' . $stmt->error;
                }

                $stmt->close();
            }
            $conn->close();
        }
    } else {
        $error = 'Missing form fields.';
    }
}

// Function to validate password strength
function isValidPassword($password) {
    return strlen($password) >= 8 
        && preg_match('/[A-Z]/', $password)   // At least one uppercase letter
        && preg_match('/[a-z]/', $password)   // At least one lowercase letter
        && preg_match('/[0-9]/', $password);  // At least one number
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register Admin</title>
</head>
<body>
    <form method="POST" action="">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>

        <label for="confirm_password">Confirm Password:</label>
        <input type="password" id="confirm_password" name="confirm_password" required>

        <label for="first_name">First Name:</label>
        <input type="text" id="first_name" name="first_name" required>

        <label for="last_name">Last Name:</label>
        <input type="text" id="last_name" name="last_name" required>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>

        <input type="submit" value="Register">
    </form>
    <?php if (!empty($error)) { echo '<p class="error">' . htmlspecialchars($error) . '</p>'; } ?>
</body>
</html>
