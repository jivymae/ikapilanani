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

// Handle restore request
if (isset($_GET['restore'])) {
    $user_id = intval($_GET['restore']);
    
    // Begin a transaction
    $conn->begin_transaction();
    try {
        // Restore the dentist by setting archived to 0
        $stmt = $conn->prepare("UPDATE dentists SET archived = 0 WHERE user_id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stmt->close();

        // Commit the transaction
        $conn->commit();
        $message = "Dentist restored successfully!";
    } catch (Exception $e) {
        // Rollback the transaction if something fails
        $conn->rollback();
        $message = "Error: " . $e->getMessage();
    }
}

// Fetch archived dentists from the database
$sql = "SELECT u.user_id, u.username, u.email, u.first_name, u.last_name 
        FROM users u 
        JOIN dentists d ON u.user_id = d.user_id 
        WHERE u.role = 'dentist' AND d.archived = 1";
$result = $conn->query($sql);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archived Dentists - Dental Clinic Management System</title>
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

#archived-dentists {
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
    background-color: #fafafa;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

#archived-dentists h1 {
    font-size: 32px;
    margin-bottom: 20px;
    color: #333;
}

#archived-dentists table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

#archived-dentists table th, #archived-dentists table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ccc;
}

#archived-dentists table th {
    background-color: #f0f0f0;
    color: #555;
}

#archived-dentists table td {
    color: #333;
}

#archived-dentists table a {
    color: #0066cc;
    text-decoration: none;
    font-weight: bold;
}

#archived-dentists table a:hover {
    text-decoration: underline;
}

#archived-dentists table td a {
    font-size: 14px;
    margin-left: 10px;
}

#archived-dentists p {
    font-size: 18px;
    color: #d9534f;
    background-color: #f2dede;
    padding: 10px;
    border-radius: 5px;
}

/* Additional Styling for Add Dentist Link */
#archived-dentists li {
    margin-bottom: 20px;
    list-style: none;
}

#archived-dentists li a {
    color: #0066cc;
    font-size: 18px;
    font-weight: bold;
}

#archived-dentists li a:hover {
    text-decoration: underline;
}

.main-content a {
    display: inline-block; /* Ensures the link looks like a block for better clickability */
    padding: 10px 20px; /* Adds spacing inside the button */
    margin: 10px 5px; /* Adds space between buttons */
    background-color: #3498db; /* Blue background */
    color: white; /* White text */
    text-decoration: none; /* Removes underline */
    font-size: 16px; /* Adjusts text size */
    font-weight: bold; /* Makes the text bold */
    border-radius: 5px; /* Adds rounded corners */
    border: none; /* Removes border */
    transition: background-color 0.3s ease, transform 0.2s ease; /* Adds smooth hover effect */
    cursor: pointer; /* Adds a pointer cursor */
    text-align: center; /* Centers the text inside the button */
}

.main-content a:hover {
    background-color: #2874a6; /* Darker blue on hover */
    transform: scale(1.05); /* Slightly enlarges button on hover */
    color: #fff; /* Ensures text remains white */
}

.main-content a:active {
    transform: scale(0.95); /* Makes the button shrink slightly when clicked */
}
/* Responsive Design */
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

    #archived-dentists {
        padding: 15px;
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
           
            <section id="archived-dentists">
                <h1>Archived Dentists</h1>
                <?php if (isset($message)): ?>
                    <p><?php echo htmlspecialchars($message); ?></p>
                <?php endif; ?>
                
                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['first_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['last_name']); ?></td>
                                    <td>
                                        <a href="?restore=<?php echo $row['user_id']; ?>" onclick="return confirm('Are you sure you want to restore this dentist?');">Restore</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">No archived dentists found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <a href="dentists.php">Back to Dentist List</a>

                </table>
            </section>
        </main>
    </div>
</body>
</html>
