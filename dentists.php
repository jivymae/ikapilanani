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

// Handle archive request
if (isset($_GET['archive'])) {
    $user_id = intval($_GET['archive']);
    
    // Begin a transaction
    $conn->begin_transaction();
    try {
        // Archive the dentist by setting archived to 1
        $stmt = $conn->prepare("UPDATE dentists SET archived = 1 WHERE user_id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stmt->close();

        // Commit the transaction
        $conn->commit();
        $message = "Dentist archived successfully!";
    } catch (Exception $e) {
        // Rollback the transaction if something fails
        $conn->rollback();
        $message = "Error: " . $e->getMessage();
    }
}

// Fetch active dentists from the database
$sql = "SELECT u.user_id, u.username, u.email, u.first_name, u.last_name 
        FROM users u 
        JOIN dentists d ON u.user_id = d.user_id 
        WHERE u.role = 'dentist' AND d.archived = 0";

$result = $conn->query($sql);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dentists - Dental Clinic Management System</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>


<style>
/* General Styling */
.container {
    display: flex;
    min-height: 100vh;
    background-color: #f4f4f4;
}


.main-content {
    flex-grow: 1;
    padding: 20px;
}

#dentists {
    background-color: white;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

#dentists h1 {
    font-size: 24px;
    margin-bottom: 20px;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

table th,
table td {
    padding: 12px;
    text-align: center;
    border: 1px solid #ddd;
}

table th {
    background-color: #f1f1f1;
    color: #333;
    font-weight: bold;
}

table td {
    color: #666;
}

table tr:hover {
    background-color: #f9f9f9;
}

/* Action Links Styling */
td a {
    color: #1a73e8;
    text-decoration: none;
    font-weight: bold;
    margin-right: 10px;
    display: inline-flex;
    align-items: center;
}

td a i {
    margin-right: 5px; /* Space between the icon and the text */
    font-size: 1.2rem; /* Adjust icon size */
}

td a:hover {
    text-decoration: underline;
    color: #000; /* Change the color when hovering */
}
/* Button Styling */
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
            <a href="add_dentist.php">Add Dentist</a>

            <section id="dentists">
                <h1>Dentists</h1>
                <?php if (isset($message)): ?>
                    <p><?php echo htmlspecialchars($message); ?></p>
                <?php endif; ?>

                <a href="admin_archive_dentist.php">Archive</a>
                
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
                                        <a href="view_dentist.php?id=<?php echo $row['user_id']; ?>">View</a> |
                                        <a href="admin_edit_dentist.php?id=<?php echo $row['user_id']; ?>">Edit</a> |
                                        <a href="?archive=<?php echo $row['user_id']; ?>" onclick="return confirm('Are you sure you want to archive this dentist?');">
                                            <i class="fas fa-archive"></i> <!-- Archive Icon -->
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">No dentists found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
</body>
</html>
