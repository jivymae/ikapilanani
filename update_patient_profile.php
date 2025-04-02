<?php
session_start();
include 'db_config.php'; // Ensure this file initializes $conn

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: login.php');
    exit();
}

$patient_id = $_SESSION['user_id'];

// Fetch current patient details
$stmt = $conn->prepare("SELECT username, email, phone, address, profile_image, barangay_id, first_name, last_name, dob, gender FROM users WHERE user_id = ? AND role = 'patient'");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$patient = $result->fetch_assoc();
$stmt->close();

// Initialize address to avoid warnings
$address = $patient['address'] ?? '';

// Fetch lists for dropdowns
$countries = $conn->query("SELECT * FROM countries");
$regions = $conn->query("SELECT * FROM regions");
$cities = $conn->query("SELECT * FROM cities");
$municipalities = $conn->query("SELECT * FROM municipalities");
$barangays = $conn->query("SELECT * FROM barangays");

// Initialize success and error messages
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_username = $_POST['username'];
    $new_email = $_POST['email'];
    $new_phone = $_POST['phone'];
    $new_address = $_POST['address'] ?? ''; // Ensure it's not null
    $barangay_id = $_POST['barangay_id'];
    $new_dob = $_POST['dob'];
    $new_gender = $_POST['gender'];

    // Validate phone number
    if (!preg_match('/^\d{11}$/', $new_phone)) {
        $error = 'Phone number must be exactly 11 digits.';
    }

    // Check for duplicate username
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND user_id != ?");
    $stmt->bind_param("si", $new_username, $patient_id);
    $stmt->execute();
    $stmt->bind_result($username_count);
    $stmt->fetch();
    $stmt->close();

    if ($username_count > 0) {
        $error = 'Username already exists. Please choose another.';
    }

    // Check for duplicate email
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND user_id != ?");
    $stmt->bind_param("si", $new_email, $patient_id);
    $stmt->execute();
    $stmt->bind_result($email_count);
    $stmt->fetch();
    $stmt->close();

    if ($email_count > 0) {
        $error = 'Email already exists. Please use another.';
    }

    // Handle file upload
    $profile_image = $patient['profile_image'];
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

        if (!empty($new_username) && $new_username !== $patient['username']) {
            $update_fields[] = "username = ?";
            $update_values[] = $new_username;
        }
        if (!empty($new_email) && $new_email !== $patient['email']) {
            $update_fields[] = "email = ?";
            $update_values[] = $new_email;
        }
        if (!empty($new_phone) && $new_phone !== $patient['phone']) {
            $update_fields[] = "phone = ?";
            $update_values[] = $new_phone;
        }
        if (!empty($new_address) && $new_address !== $patient['address']) {
            $update_fields[] = "address = ?";
            $update_values[] = $new_address;
        }
        if ($barangay_id != $patient['barangay_id']) {
            $update_fields[] = "barangay_id = ?";
            $update_values[] = $barangay_id;
        }
        if ($profile_image !== $patient['profile_image']) {
            $update_fields[] = "profile_image = ?";
            $update_values[] = $profile_image;
        }
        if (!empty($new_dob) && $new_dob !== $patient['dob']) {
            $update_fields[] = "dob = ?";
            $update_values[] = $new_dob;
        }
        if ($new_gender !== $patient['gender']) {
            $update_fields[] = "gender = ?";
            $update_values[] = $new_gender;
        }

        if (empty($update_fields)) {
            $error = 'No changes made.';
        } else {
            $update_sql = "UPDATE users SET " . implode(', ', $update_fields) . " WHERE user_id = ?";
            $stmt = $conn->prepare($update_sql);
            $types = str_repeat('s', count($update_values)) . 'i'; // Add 'i' for user_id (integer)
            $update_values[] = $patient_id; // Add patient ID to the values
            $stmt->bind_param($types, ...$update_values);

            if ($stmt->execute()) {
                $success = 'Profile updated successfully!';
            } else {
                $error = 'Failed to update profile.';
            }
            $stmt->close();
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
    <title>Update Profile - Dental Clinic Management System</title>
    <style>
       /* General Reset */
         /* General Styles */
         body, h1, h2, p, ul, li, div, table, th, td {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
  font-family: Arial;
  margin: 0;
  padding: 0;
  color: #333;
  background-color: #f4f4f4;
}


        /* Navigation Bar */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #00bfff;
            color: #fff;
            padding: 0.4rem 1rem;
            position: relative;
        }
        .navbar .logo {
            display: flex;
            align-items: center;
        }

        .navbar .logo img {
            height: 50px;
            margin-right: 10px;
        }

        .navbar .logo h1 {
            font-size: 1.5rem;
            font-weight: bold;
        }

        .navbar .nav-links {
            font-size: 1.2rem;
            list-style: none;
            display: flex;
            gap: 1.7rem;
            transition: all 0.3s ease;
        }

        .navbar .nav-links a {
            text-decoration: none;
            color: #fff;
            font-weight:  ;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .navbar .nav-links a.active, .navbar .nav-links a:hover {
            background-color: #0056b3;
        }

        /* Hamburger Menu */
        .hamburger {
            display: none;
            flex-direction: column;
            justify-content: space-around;
            width: 30px;
            height: 25px;
            cursor: pointer;
        }

        .hamburger span {
            background-color: #fff;
            height: 3px;
            width: 100%;
            border-radius: 3px;
        }

       
        /* General Styles */
ul.navigation {
    list-style-type: none;
    padding: 0;
    margin: 20px 0;
    display: flex;
    flex-direction: column;
    align-items: center;
}

ul.navigation li {
    margin-bottom: 15px;
}

ul.navigation .btn {
    background-color: #00bfff;
    color: white;
    padding: 12px 25px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 16px;
    display: inline-block;
    width: auto;
    text-align: center;
    transition: background-color 0.3s, transform 0.2s ease-in-out;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

/* Hover and Focus States */
ul.navigation .btn:hover {
    background-color: #0056b3;
    transform: translateY(-2px);
}

ul.navigation .btn:focus {
    outline: none;
    background-color: #0056b3;
}

ul.navigation .btn:active {
    transform: translateY(0);
}

.container {
    max-width: 600px;
    margin: 30px auto;
    background-color: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

.form-group {
    margin-bottom: 20px;
}

label {
    font-weight: bold;
    color: #555;
    font-size: 14px;
    margin-bottom: 5px;
    display: block;
}

input, select, textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 14px;
    box-sizing: border-box; /* Includes padding in width */
    transition: border-color 0.3s ease;
}

input:focus, select:focus, textarea:focus {
    border-color: #00bfff;
    outline: none;
}

textarea {
    resize: vertical;
    min-height: 120px;
}

button {
    background-color: #00bfff;
    color: white;
    padding: 12px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    width: 100%;
    transition: background-color 0.3s ease;
}

button:hover {
    background-color: #0056b3;
}

button:focus {
    outline: none;
}

.error {
    color: red;
    font-size: 14px;
    text-align: center;
    margin-bottom: 20px;
}

.success {
    color: green;
    font-size: 14px;
    text-align: center;
    margin-bottom: 20px;
}

img {
    max-width: 150px;
    margin-top: 10px;
    display: block;
    margin-left: auto;
    margin-right: auto;
}



@media (max-width: 480px) {

    .container {
        padding: 10px;
    }
}
@media (max-width: 768px) {

    .container {
        padding: 15px;
    }
    ul.navigation {
        padding-left: 10px;
        padding-right: 10px;
    }

    ul.navigation .btn {
        font-size: 14px;
        padding: 10px 20px;
    }

            .navbar .nav-links {
                display: none;
                flex-direction: column;
                position: absolute;
                top: 100%;
                left: 0;
                width: 100%;
                background-color: #00bfff;
                padding: 1rem 0;
                z-index: 10;
            }

            .navbar .nav-links.show {
                display: flex;
            }

            .hamburger {
                display: flex;
            }
        }

    </style>
</head>

<body>
        <!-- Navigation Bar -->
        <nav class="navbar">
        <div class="logo">
            <img src="images/lads.png" alt="Dental Clinic Logo">
            <h1>LAD DCAMS</h1>
        </div>
        <div class="hamburger" onclick="toggleMenu()">
            <span></span>
            <span></span>
            <span></span>
        </div>
        <ul class="nav-links">
            <li><a href="patient_dashboard.php">Dashboard</a></li>
            <li><a href="patient_appointments.php">Appointments</a></li>
            <li><a href="patient_message.php">Messages</a></li>
            <li><a href="patient_profile.php">Profile</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
    <script>
        function toggleMenu() {
            const navLinks = document.querySelector('.nav-links');
            navLinks.classList.toggle('show');
        }
    </script>



<div class="container">
<ul class="navigation">
    <li><a href="update_patient_profile.php" class="btn">Update Profile</a>
    <a href="change_password.php" class="btn">Change Password</a></li>
</ul>

    <h1>Update Profile</h1>
    
    <?php if (!empty($error)): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <p class="success"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data">

    <?php if ($patient['profile_image']): ?>
                <img src="<?php echo htmlspecialchars($patient['profile_image']); ?>" alt="Profile Image">
            <?php endif; ?>
        <div class="form-group">
            <label for="first_name">First Name:</label>
            <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($patient['first_name'] ?? ''); ?>" readonly>
        </div>
        <div class="form-group">
            <label for="last_name">Last Name:</label>
            <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($patient['last_name'] ?? ''); ?>" readonly>
        </div>
        <div class="form-group">
            <label for="dob">Date of Birth:</label>
            <input type="date" id="dob" name="dob" value="<?php echo htmlspecialchars($patient['dob'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="gender">Gender:</label>
            <select id="gender" name="gender">
                <option value="Male" <?php echo ($patient['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                <option value="Female" <?php echo ($patient['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                <option value="Other" <?php echo ($patient['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
            </select>
        </div>
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($patient['username'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($patient['email'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="phone">Phone Number:</label>
            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($patient['phone'] ?? ''); ?>"
                   pattern="\d{11}" maxlength="11" required
                   title="Phone number must be exactly 11 digits.">
        </div>
        <div class="form-group">
            <label for="address">Address:</label>
            <textarea id="address" name="address"><?php echo htmlspecialchars($address ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>

        <div class="form-group">
            <label for="barangay_id">Barangay:</label>
            <select id="barangay_id" name="barangay_id">
                <?php while ($row = $barangays->fetch_assoc()): ?>
                    <option value="<?php echo $row['barangay_id']; ?>" <?php if ($row['barangay_id'] == $patient['barangay_id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($row['barangay_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="profile_image">Profile Image:</label>
            <input type="file" id="profile_image" name="profile_image">
           
        </div>

        <button type="submit">Update Profile</button>
    </form>
</div>

</body>
</html>