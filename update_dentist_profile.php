<?php
session_start();
include 'db_config.php'; // Ensure this file initializes $conn

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'dentist') {
    header('Location: login.php');
    exit();
}

$dentist_id = $_SESSION['user_id'];

// Fetch current dentist details
$stmt = $conn->prepare("SELECT username, email, phone, address, profile_image, password_hash, barangay_id FROM users WHERE user_id = ? AND role = 'dentist'");
$stmt->bind_param("i", $dentist_id);
$stmt->execute();
$result = $stmt->get_result();
$dentist = $result->fetch_assoc();
$stmt->close();

// Initialize variables to avoid warnings
$username = $dentist['username'] ?? '';
$email = $dentist['email'] ?? '';
$phone = $dentist['phone'] ?? '';
$address = $dentist['address'] ?? '';
$profile_image = $dentist['profile_image'] ?? '';
$barangay_id = $dentist['barangay_id'] ?? '';

// Fetch barangays
$barangays = $conn->query("SELECT barangay_id, barangay_name FROM barangays");

// Initialize success and error messages
$error = '';
$success = '';

// Store new values to retain after submission
$new_username = $username;
$new_email = $email;
$new_phone = $phone;
$new_address = $address;
$new_barangay_id = $barangay_id;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_username = $_POST['username'];
    $new_email = $_POST['email'];
    $new_phone = $_POST['phone'];
    $new_address = $_POST['address'] ?? ''; // Ensure it's not null
    $new_barangay_id = $_POST['barangay_id'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate phone number
    if (!preg_match('/^\d{11}$/', $new_phone)) {
        $error = 'Phone number must be exactly 11 digits.';
    }

    // Handle file upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        $upload_file = $upload_dir . basename($_FILES['profile_image']['name']);
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_file)) {
            $profile_image = $upload_file;
        } else {
            $error = 'Failed to upload image.';
        }
    }

    if (empty($error)) {
        // Prepare update fields and values
        $update_fields = [];
        $update_values = [];

        if (!empty($new_username) && $new_username !== $username) {
            $update_fields[] = "username = ?";
            $update_values[] = $new_username;
        }
        if (!empty($new_email) && $new_email !== $email) {
            $update_fields[] = "email = ?";
            $update_values[] = $new_email;
        }
        if (!empty($new_phone) && $new_phone !== $phone) {
            $update_fields[] = "phone = ?";
            $update_values[] = $new_phone;
        }
        if (!empty($new_address) && $new_address !== $address) {
            $update_fields[] = "address = ?";
            $update_values[] = $new_address;
        }
        if ($new_barangay_id !== $barangay_id) {
            $update_fields[] = "barangay_id = ?";
            $update_values[] = $new_barangay_id;
        }
        if ($profile_image !== $dentist['profile_image']) {
            $update_fields[] = "profile_image = ?";
            $update_values[] = $profile_image;
        }

        if (empty($update_fields) && empty($new_password)) {
            $error = 'No changes made.';
        } else {
            // Prepare update SQL statement
            $update_sql = "UPDATE users SET " . implode(', ', $update_fields) . " WHERE user_id = ?";
            $stmt = $conn->prepare($update_sql);

            // Ensure the types for binding parameters
            $types = str_repeat('s', count($update_values)) . 'i';
            $update_values[] = $dentist_id; // Add user ID for final binding
            $stmt->bind_param($types, ...$update_values);

            if ($stmt->execute()) {
                // Profile update was successful, now check if the password needs to be updated
                if (!empty($new_password)) {
                    if ($new_password === $confirm_password) {
                        if (password_verify($current_password, $dentist['password_hash'])) {
                            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                            // Prepare a new statement for updating the password
                            $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
                            $stmt->bind_param("si", $hashed_password, $dentist_id);
                            
                            if ($stmt->execute()) {
                                $success = 'Profile and password updated successfully!';
                            } else {
                                $error = 'Failed to update password.';
                            }
                        } else {
                            $error = 'Current password is incorrect.';
                        }
                    } else {
                        $error = 'New passwords do not match.';
                    }
                } else {
                    $success = 'Profile updated successfully!';
                }
            } else {
                $error = 'Failed to update profile.';
            }
            $stmt->close(); // Close the statement after use
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Dentist Profile</title>
    <style>
        .error {
            color: red;
        }
        .success {
            color: green;
        }
        .form-group {
            margin-bottom: 10px;
        }
        img {
            max-width: 200px;
            max-height: 200px;
        }
    </style>
</head>
<body>
    <h1>Update Dentist Profile</h1>
    
    <?php if (!empty($error)): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <p class="success"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>
    <form method="POST" action="" enctype="multipart/form-data">
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($new_username); ?>">
        </div>
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($new_email); ?>">
        </div>
        <div class="form-group">
            <label for="phone">Phone Number:</label>
            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($new_phone); ?>"
                   pattern="\d{11}" maxlength="11" required
                   title="Phone number must be exactly 11 digits.">
        </div>
        <div class="form-group">
            <label for="address">Address:</label>
            <textarea id="address" name="address"><?php echo htmlspecialchars($new_address, ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>
        <div class="form-group">
            <label for="barangay_id">Barangay:</label>
            <select id="barangay_id" name="barangay_id">
                <?php while ($row = $barangays->fetch_assoc()): ?>
                    <option value="<?php echo htmlspecialchars($row['barangay_id']); ?>" 
                            <?php if ($row['barangay_id'] == $new_barangay_id) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($row['barangay_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="profile_image">Profile Image:</label>
            <input type="file" id="profile_image" name="profile_image">
            <?php if ($profile_image): ?>
                <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile Image">
            <?php endif; ?>
        </div>
        <div class="form-group">
            <label for="current_password">Current Password:</label>
            <input type="password" id="current_password" name="current_password">
        </div>
        <div class="form-group">
            <label for="new_password">New Password:</label>
            <input type="password" id="new_password" name="new_password">
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirm New Password:</label>
            <input type="password" id="confirm_password" name="confirm_password">
        </div>
        <button type="submit">Update Profile</button>
    </form>
    <a href="logout.php">Logout</a>
</body>
</html>
