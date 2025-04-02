<?php
session_start(); // Start session to check success flag

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if the appointment was successfully booked
if (!isset($_SESSION['appointment_success']) || $_SESSION['appointment_success'] !== true) {
    // If not, redirect back to appointments page
    header('Location: patient_appointments.php');
    exit();
}

// Clear the success flag after successful display
unset($_SESSION['appointment_success']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Success</title>
    <style>
              body, h1, h2, p {
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


/* Navbar */
        /* Success Page Styles */
        .appointment-success {
            background-color: #ffffff;
            padding: 30px;
            max-width: 800px;
            width: 100%;
            margin: 50px auto;
            border-radius: 15px;
            box-shadow: 0px 6px 15px rgba(0, 0, 0, 0.2);
            text-align: center;
            color: #333;
        }

        .appointment-success h2 {
            font-size: 2rem;
            color: #00bfff;
            margin-bottom: 20px;
            border-bottom: 2px solid #00bfff;
            display: inline-block;
            padding-bottom: 10px;
        }

        .appointment-success p {
            font-size: 1.2rem;
            line-height: 1.6;
            margin-bottom: 20px;
            color: #555;
        }

        .appointment-success a {
            display: inline-block;
            background-color: #00bfff;
            color: white;
            padding: 12px 30px;
            font-size: 1.2rem;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .appointment-success a:hover {
            background-color: #0056b3;
            transform: scale(1.05);
        }

        .appointment-success a:active {
            background-color: #004085;
            transform: scale(1);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .appointment-success {
                padding: 20px;
                width: 90%;
            }

            .appointment-success h2 {
                font-size: 1.6rem;
            }

            .appointment-success p {
                font-size: 1rem;
            }

            .appointment-success a {
                font-size: 1rem;
                padding: 10px 20px;
            }
        }

        @media (max-width: 480px) {
            .appointment-success {
                padding: 15px;
                width: 95%;
            }

            .appointment-success h2 {
                font-size: 1.4rem;
            }

            .appointment-success p {
                font-size: 0.9rem;
            }

            .appointment-success a {
                font-size: 0.9rem;
                padding: 8px 15px;
            }
        }
        /* Responsive Design */
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
</head>
<body>
<nav class="navbar">
        <div class="logo">
            <img src="images/cometaicon.png" alt="Dental Clinic Logo">
            <h1>LAD DCAMS</h1>
        </div>
        <div class="hamburger" onclick="toggleMenu()">
            <span></span>
            <span></span>
            <span></span>
        </div>
        <ul class="nav-links">
            <li><a href="patient_dashboard.php">Dashboard</a></li>
            <li><a href="patient_appointments.php"  >Appointments</a></li>
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
    <div class="appointment-success">
        <h2>Success! Your appointment is booked.</h2>
        <p>Your appointment has been successfully booked. Please wait for confirmation from the dental clinic.</p>
        <a href="patient_book.php">Go back to your appointments</a>
    </div>
</body>
</html>
