<?php
include 'login_check.php'; // Ensure user is logged in
include 'db_config.php';
include 'admin_check.php'; // Include the admin check helper

// Check if the page is allowed
$page = basename($_SERVER['PHP_SELF']);
if (!isAdminPage($page)) {
    header('Location: unauthorized.php'); // Redirect to an error or dashboard page
    exit();
}

// Get the user ID from the query string
if (isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    
    // Fetch the dentist details based on user_id (replace dentist_id with user_id)
    $stmt = $conn->prepare("SELECT u.user_id, u.username, u.email, u.first_name, u.last_name, u.created_at, 
                                   d.license_number, d.emergency_contact, d.updated_at 
                            FROM users u 
                            LEFT JOIN dentists d ON u.user_id = d.user_id 
                            WHERE u.user_id = ? AND u.role = 'dentist'");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $dentist = $result->fetch_assoc();

        // Fetch dentist availability using user_id (correct usage)
        $availability_stmt = $conn->prepare("SELECT day FROM dentist_availability WHERE user_id = ?");
        $availability_stmt->bind_param('i', $user_id);  // Use user_id instead of dentist_id
        $availability_stmt->execute();
        $availability_result = $availability_stmt->get_result();

        $day_mapping = [
            'M' => 'Monday',
            'T' => 'Tuesday',
            'W' => 'Wednesday',
            'Th' => 'Thursday',
            'F' => 'Friday',
            'Sa' => 'Saturday',
            'Su' => 'Sunday',
        ];

        $availability = [];
        while ($row = $availability_result->fetch_assoc()) {
            // Map the days properly based on the mapping
            if (isset($day_mapping[$row['day']])) {
                $availability[] = $day_mapping[$row['day']]; // Map the abbreviation to the full name
            } else {
                // If mapping fails, keep the original value
                $availability[] = $row['day'];
            }
        }

        $availability_stmt->close();
    } else {
        $message = "Dentist not found.";
    }
    $stmt->close();
} else {
    $message = "Invalid request.";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Dentist - Dental Clinic Management System</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
</head>
<style>
    /* General Styles */
    body {
        font-family: Arial, sans-serif;
        background-color: #f4f4f9;
        color: #333;
        margin: 0;
        padding: 0;
    }

    .container {
        display: flex;
        min-height: 100vh;
    }

    /* Main Content */
    .main-content {
        flex-grow: 1;
        padding: 30px;
        background-color: #fff;
    }

    #view-dentist {
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
        background-color: #fafafa;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    #view-dentist h1 {
        font-size: 32px;
        margin-bottom: 20px;
        color: #333;
    }

    #view-dentist table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    #view-dentist table th, #view-dentist table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #ccc;
    }

    #view-dentist table th {
        background-color: #f0f0f0;
        color: #555;
    }

    #view-dentist table td {
        color: #333;
    }

    #view-dentist a {
        color: #0066cc;
        text-decoration: none;
        font-weight: bold;
    }

    #view-dentist a:hover {
        text-decoration: underline;
    }

    #view-dentist p {
        font-size: 18px;
        color: #d9534f;
        background-color: #f2dede;
        padding: 10px;
        border-radius: 5px;
    }

    @media (max-width: 768px) {
        .container {
            flex-direction: column;
        }

        .sidebar {
            width: 100%;
            height: auto;
        }

        .main-content {
            padding: 20px;
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
                <li><a href="admin_dashboard.php" class="<?php echo $page == 'admin_dashboard.php' ? 'active' : ''; ?>">Dashboard</a></li>
                <li><a href="patients.php">Patients</a></li>
                <li><a href="dentists.php" class="<?php echo $page == 'dentists.php' ? 'active' : ''; ?>">Dentists</a></li>
                <li><a href="appointments.php">Appointments</a></li>
                <li><a href="admin_add_services.php">Add Services</a></li>
                <li><a href="reports.php">Reports</a></li>
                <li><a href="admin_settings.php">Settings</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <section id="view-dentist">
                <h1>View Dentist</h1>
                <?php if (isset($message)): ?>
                    <p><?php echo htmlspecialchars($message); ?></p>
                <?php endif; ?>

                <?php if (isset($dentist)): ?>
                    <table>
                        <tr>
                            <th>Username:</th>
                            <td><?php echo htmlspecialchars($dentist['username']); ?></td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td><?php echo htmlspecialchars($dentist['email']); ?></td>
                        </tr>
                        <tr>
                            <th>First Name:</th>
                            <td><?php echo htmlspecialchars($dentist['first_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Last Name:</th>
                            <td><?php echo htmlspecialchars($dentist['last_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Created At:</th>
                            <td><?php echo htmlspecialchars($dentist['created_at']); ?></td>
                        </tr>
                        <tr>
                            <th>License Number:</th>
                            <td><?php echo htmlspecialchars($dentist['license_number']); ?></td>
                        </tr>
                        <tr>
                            <th>Emergency Contact:</th>
                            <td><?php echo htmlspecialchars($dentist['emergency_contact']); ?></td>
                        </tr>
                        <tr>
                            <th>Updated At:</th>
                            <td><?php echo htmlspecialchars($dentist['updated_at']); ?></td>
                        </tr>
                        <tr>
                            <th>Availability:</th>
                            <td>
                                <?php if (!empty($availability)): ?>
                                    <ul>
                                        <?php foreach ($availability as $day): ?>
                                            <li><?php echo htmlspecialchars($day); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p>No availability set.</p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                    <a href="admin_edit_dentist.php?id=<?php echo $user_id; ?>">Edit</a> |
                    <a href="dentists.php">Back to List</a>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>
