<?php
include 'login_check.php';  // Ensure user is logged in
include 'db_config.php';
include 'admin_check.php';  // Admin check

// Fetch the user profile data from the database
$user_id = $_SESSION['user_id'];  // Assuming the user ID is stored in the session after login
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Fetch barangays for the dropdown
$barangay_sql = "SELECT barangay_id, barangay_name FROM barangays";
$barangay_result = $conn->query($barangay_sql);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    // Get updated profile data from POST request
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $barangay_id = $_POST['barangay'];  // New barangay field (ID)
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];

    // Phone number validation (11 digits starting with '0')
    if (!preg_match('/^0\d{10}$/', $phone)) {
        $error_message = "Phone number must be 11 digits, starting with '0'.";
    } else {
        // Sanitize the phone number to keep only digits (if any non-digit characters are included)
        $phone = preg_replace('/\D/', '', $phone); // Remove non-digit characters

        // If a new profile image is uploaded
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $profile_image = 'uploads/' . basename($_FILES['profile_image']['name']);
            move_uploaded_file($_FILES['profile_image']['tmp_name'], $profile_image);

            // Update profile with new image
            $sql = "UPDATE users SET first_name = ?, last_name = ?, phone = ?, address = ?, barangay_id = ?, dob = ?, gender = ?, profile_image = ? WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssssi", $first_name, $last_name, $phone, $address, $barangay_id, $dob, $gender, $profile_image, $user_id);
        } else {
            // If no new image, update without the image
            $sql = "UPDATE users SET first_name = ?, last_name = ?, phone = ?, address = ?, barangay_id = ?, dob = ?, gender = ? WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssssi", $first_name, $last_name, $phone, $address, $barangay_id, $dob, $gender, $user_id);
        }

        if ($stmt->execute()) {
            $success_message = "Profile updated successfully!";
        } else {
            $error_message = "Error updating profile. Please try again.";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - Dental Clinic Management System</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
</head>
<style>
    /* General Styles */
body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f9;
    margin: 0;
    padding: 0;
    color: #333;
}

.container {
    display: flex;
}



.main-content {
    margin-left: 260px; /* Offset for sidebar */
    padding: 40px;
    width: 100%;
}

#profile {
    max-width: 900px;
    margin: 0 auto;
    background-color: #ffffff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.main-content h1 {
    font-size: 32px;
    margin-bottom: 30px;
    color: #333;
    text-align: center;
}

form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

label {
    font-size: 16px;
    font-weight: bold;
    color: #444;
}

input, select, textarea {
    padding: 12px;
    font-size: 16px;
    border-radius: 5px;
    border: 1px solid #ccc;
    width: 100%;
    box-sizing: border-box;
}

textarea {
    height: 120px;
}

button {
    background-color: #0066cc;
    color: white;
    padding: 12px 20px;
    border: none;
    border-radius: 5px;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s;
}

button:hover {
    background-color: #005bb5;
}

/* Alert Styles */
.alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 5px;
}

.alert.success {
    background-color: #4caf50;
    color: white;
}

.alert.error {
    background-color: #f44336;
    color: white;
}

/* Profile Image Styles */
.profile-image img {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: 50%;
}

.profile-image p {
    font-size: 14px;
    color: #666;
    margin-top: 10px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .sidebar {
        width: 200px;
    }

    .main-content {
        margin-left: 0;
        padding: 20px;
    }

    h1 {
        font-size: 28px;
    }

    form {
        gap: 15px;
    }

    input, select, textarea, button {
        font-size: 14px;
        padding: 10px;
    }
}

    </style>
<body>
    <div class="container">
        <!-- Sidebar Menu -->
        <aside class="sidebar">
            <h2 class="logo">
                <img src="images/lads.png" alt="Dental Clinic Logo">
                <h1>LAD DCAMS</h1>
            </h2>
            <ul>
                <li><a href="admin_dashboard.php">Dashboard</a></li>
                <li><a href="patients.php">Patients</a></li>
                <li><a href="dentists.php">Dentists</a></li>
                <li><a href="appointments.php">Appointments</a></li>
                <li><a href="admin_add_services.php">Add Services</a></li>
                <li><a href="reports.php">Reports</a></li>
               
                <li><a href="admin_settings.php">Settings</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <section id="profile">
                <h1>Admin Profile</h1>

                <?php if (isset($success_message)): ?>
                    <div class="alert success"><?php echo $success_message; ?></div>
                <?php elseif (isset($error_message)): ?>
                    <div class="alert error"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <form action="admin_profile.php" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                               pattern="^0\d{10}$" 
                               maxlength="11" 
                               title="Phone number must be 11 digits, starting with '0'" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" required><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </div>

                    <!-- Barangay Dropdown -->
                    <div class="form-group">
                        <label for="barangay">Barangay</label>
                        <select id="barangay" name="barangay" required>
                            <option value="">Select Barangay</option>
                            <?php while ($barangay = $barangay_result->fetch_assoc()): ?>
                                <option value="<?php echo $barangay['barangay_id']; ?>"
                                    <?php echo ($user['barangay_id'] == $barangay['barangay_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($barangay['barangay_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="dob">Date of Birth</label>
                        <input type="date" id="dob" name="dob" value="<?php echo htmlspecialchars($user['dob'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender" required>
                            <option value="male" <?php echo $user['gender'] == 'male' ? 'selected' : ''; ?>>Male</option>
                            <option value="female" <?php echo $user['gender'] == 'female' ? 'selected' : ''; ?>>Female</option>
                            <option value="other" <?php echo $user['gender'] == 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="profile_image">Profile Image</label>
                        <input type="file" id="profile_image" name="profile_image">
                        <?php if ($user['profile_image']): ?>
                            <p>Current Image: <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile Image" width="100"></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                    </div>
                </form>
            </section>
        </main>
    </div>
</body>
</html>
