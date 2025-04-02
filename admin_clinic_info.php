<?php
include 'login_check.php';  // Ensure user is logged in
include 'db_config.php';
include 'admin_check.php';  // Admin check

// Fetch the clinic info from the database (assuming clinic_id is 1)
$clinic_id = 1; // Default clinic_id (or adjust based on dynamic logic)

// Check if clinic info exists
$query = "SELECT * FROM clinic_info WHERE clinic_id = $clinic_id";
$result = mysqli_query($conn, $query);

// If no result found, insert default clinic info
if (mysqli_num_rows($result) == 0) {
    // Insert a default record
    $insert_query = "
        INSERT INTO clinic_info (clinic_name, clinic_address, clinic_contact, clinic_logo)
        VALUES ('Your Dental Clinic', '123 Clinic St, City, Country', '123-456-7890', 'default_logo.png')
    ";
    if (mysqli_query($conn, $insert_query)) {
        // After insertion, fetch the newly inserted record
        $result = mysqli_query($conn, $query);
    } else {
        die('Error inserting default clinic info: ' . mysqli_error($conn));
    }
}

$clinic = mysqli_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect the submitted data
    $clinic_name = $_POST['clinic_name'];
    $clinic_address = $_POST['clinic_address'];
    $clinic_contact = $_POST['clinic_contact'];

    // Handle clinic logo upload
    if ($_FILES['clinic_logo']['name']) {
        // Get the current logo from the database (if exists)
        $current_logo = $clinic['clinic_logo'];

        // Handle the new logo upload
        $logo_tmp_name = $_FILES['clinic_logo']['tmp_name'];
        $logo_name = $_FILES['clinic_logo']['name'];
        $logo_extension = pathinfo($logo_name, PATHINFO_EXTENSION);
        $logo_new_name = 'logo_' . time() . '.' . $logo_extension;
        $logo_path = 'images/' . $logo_new_name;

        // Move the uploaded logo to the correct directory
        if (move_uploaded_file($logo_tmp_name, $logo_path)) {
            // If a new logo is uploaded and there was an existing logo, delete the old one
            if ($current_logo && file_exists('images/' . $current_logo)) {
                unlink('images/' . $current_logo);
            }

            // Update the logo path in the database
            $update_logo_query = "UPDATE clinic_info SET clinic_logo = '$logo_new_name' WHERE clinic_id = $clinic_id";
            mysqli_query($conn, $update_logo_query);
        }
    }

    // Update the clinic details in the database (excluding logo for now)
    $update_query = "
        UPDATE clinic_info 
        SET 
            clinic_name = '$clinic_name',
            clinic_address = '$clinic_address',
            clinic_contact = '$clinic_contact'
        WHERE clinic_id = $clinic_id
    ";

    // Execute the update query
    if (mysqli_query($conn, $update_query)) {
        $message = "Clinic information updated successfully!";
    } else {
        $message = "Error updating clinic information: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Clinic Info - Dental Clinic Management System</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar Menu -->
        <aside class="sidebar">
            <h2 class="logo">
                <img src="images/cometaicon.png" alt="Dental Clinic Logo">
                <h1>Dental Clinic</h1>
            </h2>
            <ul>
                <li><a href="admin_dashboard.php">Dashboard</a></li>
                <li><a href="patients.php">Patients</a></li>
                <li><a href="dentists.php">Dentists</a></li>
                <li><a href="appointments.php">Appointments</a></li>
                <li><a href="admin_add_services.php">Add Services</a></li>
                <li><a href="reports.php">Reports</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="admin_settings.php">Settings</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <section id="clinic-info">
                <h1>Update Clinic Info</h1>

                <?php
                // Show error if clinic info is not found
                if (isset($error_message)) {
                    echo "<p class='error'>$error_message</p>";
                } elseif (isset($message)) {
                    echo "<p class='message'>$message</p>";
                }
                ?>

                <?php if ($clinic): ?>
                  <form action="admin_clinic_info.php" method="POST" enctype="multipart/form-data">
    <label for="clinic_name">Clinic Name:</label>
    <input type="text" name="clinic_name" id="clinic_name" value="<?php echo $clinic['clinic_name']; ?>" required>

    <label for="clinic_address">Clinic Address:</label>
    <textarea name="clinic_address" id="clinic_address" required><?php echo $clinic['clinic_address']; ?></textarea>

    <label for="clinic_contact">Clinic Contact:</label>
    <input type="text" name="clinic_contact" id="clinic_contact" value="<?php echo $clinic['clinic_contact']; ?>" required>

    <!-- File input for the logo -->
    <label for="clinic_logo">Clinic Logo (Upload new logo if you want to change):</label>
    <input type="file" name="clinic_logo" id="clinic_logo">

    <!-- Display the current logo if it exists -->
    <?php if ($clinic['clinic_logo']): ?>
        <p>Current Logo:</p>
        <img src="images/logos/<?php echo $clinic['clinic_logo']; ?>" alt="Current Clinic Logo" width="150">
    <?php endif; ?>

    <!-- Submit button -->
    <button type="submit">Update Info</button>
</form>

                <?php else: ?>
                    <p>Clinic information could not be found. Please check the database.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>
