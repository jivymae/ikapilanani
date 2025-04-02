<?php

include 'login_check.php'; // Ensure user is logged in
include 'db_config.php';

// Check if the user is logged in and has the correct role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'patient') {
    header('Location: pat_unauthorized.php'); // Redirect to an unauthorized page
    exit();
}

// Fetch patient information
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, email, first_name, last_name, profile_image FROM users WHERE user_id = ? AND role = 'patient'");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($username, $email, $first_name, $last_name, $profile_image);
$stmt->fetch();
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Profile - Dental Clinic Management System</title>
    <link rel="stylesheet" type="text/css" href="css/patient.css"> <!-- Link to the CSS file -->
</head>
<style>
/* General Styles */

/* Navbar Styles */

    /* General Styles */
/* General Styles */
       /* General Reset */
       body, h1, h2, p, ul, li, table, th, td {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
  font-family: Arial, sans-serif;
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
            padding: 0.7rem 1rem;
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
            list-style: none;
            display: flex;
            gap: 1rem;
            transition: all 0.3s ease;
        }

        .navbar .nav-links a {
            text-decoration: none;
            color: #fff;
            font-weight: ;
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
/* Main Content */
.main-content {
    padding: 20px;
    margin: 0;
}

.dashboard-container {
    max-width: 900px;
    margin: 0 auto;
}

/* Profile Information Section */
.profile-info {
    background-color: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
}

.profile-info h1 {
    font-size: 1.8rem;
    color: #00796b;
    margin-bottom: 15px;
    text-align: center;
}

.profile-info p {
    font-size: 1rem;
    margin: 10px 0;
    text-align: center;
}

.profile-info strong {
    color: #00796b;
}

/* Profile Image */
.profile-image-container {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 20px;
}

.profile-image-container img {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #00796b;
}

/* Button Styles */
.btn {
    display: block;
    background-color: #00bfff;
    color: #fff;
    text-align: center;
    padding: 12px 20px;
    border-radius: 5px;
    text-decoration: none;
    font-size: 16px;
    margin: 10px auto;
    max-width: 200px;
    transition: background-color 0.3s, transform 0.2s;
}

.btn:hover {
    background-color: #2980b9;
    transform: scale(1.05);
}

.btn:active {
    transform: scale(0.98);
}

/* Responsive Styles */
@media (max-width: 768px) {


    .main-content {
        padding: 15px;
    }

    .profile-info {
        padding: 15px;
    }

    .profile-info h1 {
        font-size: 1.6rem;
    }

    .btn {
        font-size: 14px;
        padding: 10px;
        width: 100%;
        max-width: none;
    }
}

@media (max-width: 480px) {


    .profile-info h1 {
        font-size: 1.4rem;
    }

    .btn {
        font-size: 14px;
        padding: 8px;
    }
}
@media (max-width: 768px) {
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
            <li><a href="patient_profile.php" class="active">Profile</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
    <script>
        function toggleMenu() {
            const navLinks = document.querySelector('.nav-links');
            navLinks.classList.toggle('show');
        }
    </script>

     <!-- Main Content -->
     <main class="main-content">
        <div class="dashboard-container">
            <section class="profile-info">
                <h1>Profile Information</h1>

                <!-- Display Profile Image -->
                <div class="profile-image-container">
                    <?php if (!empty($profile_image)): ?>
                        <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile Image">
                    <?php else: ?>
                        <img src="images/default-profile.png" alt="Default Profile Image">
                    <?php endif; ?>
                </div>

                <p><strong>First Name:</strong> <?php echo htmlspecialchars($first_name); ?></p>
                <p><strong>Last Name:</strong> <?php echo htmlspecialchars($last_name); ?></p>
                <p><strong>Username:</strong> <?php echo htmlspecialchars($username); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
                <a href="update_patient_profile.php" class="btn">Update Profile</a> <!-- Update Profile Button -->
                 <a href="patient_cancelled_app.php" class="btn">Cancelled Appointments</a> <!-- Cancelled Appointments Button -->
    <a href="patient_success_app.php" class="btn">Completed Appointments</a> <!-- Completed Appointments Button -->
    <a href="patient_book.php" class="btn">Book Appointments</a> <!-- Book Appointments Button -->
    
    <a href="patient_no_show.php" class="btn">No Shows Appointments</a> <!-- Completed Appointments Button -->
   
            </section>
        </div>
    </main>

   
</body>
</html>
