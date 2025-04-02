<?php
include 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data from POST request
    $appointment_date = $_POST['appointment_date'];
    $appointment_times = explode(',', $_POST['appointment_time']); // Array of times

    $conflict_found = false;

    foreach ($appointment_times as $time) {
        // Check for conflicts in the database
        $stmt = $conn->prepare("SELECT appointment_id FROM appointments WHERE appointment_date = ? AND appointment_time = ?");
        $stmt->bind_param('ss', $appointment_date, $time);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $conflict_found = true;
            break;
        }
        $stmt->close();
    }

    // Return the result as a JSON response
    echo json_encode(['conflict' => $conflict_found]);
}
?>
