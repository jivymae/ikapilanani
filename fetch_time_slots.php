<?php
include 'db_config.php';  // Include the database configuration

// Check if the 'day_of_week' parameter is passed in the query string
$day_of_week = isset($_GET['day_of_week']) ? $_GET['day_of_week'] : '';

if ($day_of_week) {
    // Prepare a SQL query to fetch the available time slots for the selected day
    $stmt = $conn->prepare("SELECT start_time FROM time_slots WHERE day_of_week = ? ORDER BY start_time");
    $stmt->bind_param('s', $day_of_week); // Use 's' for string parameter
    $stmt->execute();
    $stmt->bind_result($start_time);

    $time_slots = [];
    while ($stmt->fetch()) {
        // Store the time slots in an array
        $time_slots[] = $start_time;
    }

    $stmt->close();

    // Return the time slots as a JSON response
    echo json_encode($time_slots);
} else {
    // If no day_of_week is provided, return an empty array
    echo json_encode([]);
}

?>
