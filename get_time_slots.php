<?php
include 'db_config.php'; // Make sure DB connection is included

if (isset($_GET['day_of_week'])) {
    $day_of_week = $_GET['day_of_week'];

    // Query to fetch available time slots for the given day of the week
    $stmt = $conn->prepare("SELECT time_slot FROM available_time_slots WHERE day_of_week = ?");
    $stmt->bind_param("s", $day_of_week);
    $stmt->execute();
    $stmt->bind_result($time_slot);

    // Collect all the available time slots into an array
    $time_slots = [];
    while ($stmt->fetch()) {
        $time_slots[] = $time_slot;
    }
    $stmt->close();

    // Return the available time slots as a JSON response
    echo json_encode($time_slots);
} else {
    echo json_encode([]); // Return an empty array if day_of_week is not provided
}

$conn->close();
?>
