<?php
session_start();
include 'db_config.php';

// Redirect non-dentists to login page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'dentist') {
    header('Location: login.php');
    exit();
}

// Fetch dentist details
$dentist_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, email, phone, address FROM users WHERE user_id = ? AND role = 'dentist'");
$stmt->bind_param("i", $dentist_id);
$stmt->execute();
$stmt->bind_result($username, $email, $phone, $address);
$stmt->fetch();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dentist Profile</title>
    <link rel="stylesheet" type="text/css" href="css/dentist.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Main Content */
        .main-content {
           
            padding: 40px;
            width: calc(100% - 250px);
            background-color: #f8f9fa;
            min-height: 100vh;
        }

        /* Profile Header */
        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .profile-header h1 {
            color: #6a5acd; /* Purple color */
            font-size: 28px;
            margin: 0;
            display: flex;
            align-items: center;
        }

        .profile-header h1 i {
            margin-right: 15px;
            color: #8878c3;
        }

        /* Profile Card */
        .profile-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
            padding: 30px;
            position: relative;
            overflow: hidden;
            max-width: 800px;
            margin: 0 auto;
        }

        .profile-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 8px;
            height: 100%;
            background: linear-gradient(to bottom, #8878c3, #6a5acd);
        }

        /* Profile Info */
        .profile-info {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .info-item {
            background: #f9f5ff;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #8878c3;
            transition: transform 0.3s ease;
        }

        .info-item:hover {
            transform: translateY(-3px);
        }

        .info-item strong {
            color: #6a5acd;
            display: block;
            margin-bottom: 8px;
            font-size: 16px;
        }

        .info-item p {
            color: #555;
            margin: 0;
            font-size: 18px;
        }

        /* Update Button */
        .update-btn {
            background: linear-gradient(135deg, #8878c3, #6a5acd);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-decoration: none;
            margin-top: 20px;
        }

        .update-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
            background: linear-gradient(135deg, #7a68b3, #5a4abd);
        }

        .update-btn i {
            margin-right: 10px;
        }

        /* Responsive Design */
        @media screen and (max-width: 992px) {
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 30px 20px;
            }
            
            .profile-info {
                grid-template-columns: 1fr;
            }
        }

        @media screen and (max-width: 576px) {
            .profile-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .profile-card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <nav class="navbar">
            <div class="logo">
                <img src="images/lads.png" alt="Dental Clinic Logo">
                <h1>LAD DCAMS</h1>
            </div>
            
            <nav>
                <a href="dentist_dashboard.php">Dashboard</a>
                <a href="dentist_profile.php" class="active">Profile</a>
                <a href="dentist_patient_appointments.php">Appointments</a>
                <a href="dentist_patient_records.php">Patient Records</a>
                <a href="logout.php">Logout</a>
            </nav>
        </nav>
    </div>
    
    <div class="main-content">
        <div class="profile-header">
            <h1><i class="fas fa-user-md"></i> Dentist Profile</h1>
        </div>
        
        <div class="profile-card">
            <div class="profile-info">
                <div class="info-item">
                    <strong>Username</strong>
                    <p><?php echo htmlspecialchars($username ?? 'N/A'); ?></p>
                </div>
                <div class="info-item">
                    <strong>Email</strong>
                    <p><?php echo htmlspecialchars($email ?? 'N/A'); ?></p>
                </div>
                <div class="info-item">
                    <strong>Phone</strong>
                    <p><?php echo htmlspecialchars($phone ?? 'N/A'); ?></p>
                </div>
                <div class="info-item">
                    <strong>Address</strong>
                    <p><?php echo htmlspecialchars($address ?? 'N/A'); ?></p>
                </div>
            </div>
            
            <a href="update_dentist_profile.php" class="update-btn">
                <i class="fas fa-user-edit"></i> Update Profile
            </a>
        </div>
    </div>
</body>
</html>