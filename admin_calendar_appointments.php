<?php
// login_check.php - Ensure user is logged in
include 'login_check.php'; 

// db_config.php - Include database configuration
include 'db_config.php'; 

// admin_check.php - Ensure user is admin
include 'admin_check.php'; 

// Fetch appointments from the database for the selected month and year
$appointments = [];

function fetchAppointments($year, $month) {
    global $appointments, $conn;

    // Get the first and last date of the month
    $start_date = "$year-" . str_pad($month + 1, 2, '0', STR_PAD_LEFT) . "-01";
    $end_date = date("Y-m-t", strtotime($start_date));  // Last day of the month

    $sql = "SELECT appointment_id, appointment_date, appointment_time 
            FROM appointments 
            WHERE appointment_date BETWEEN '$start_date' AND '$end_date'";

    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        // Store the appointment details indexed by full appointment date
        $appointment_date = $row['appointment_date']; // Full date in 'YYYY-MM-DD' format
        $appointments[$appointment_date][] = [
            'appointment_id' => $row['appointment_id'],
            'appointment_time' => $row['appointment_time']
        ];
    }
}

// Get current month and year
$currentDate = new DateTime();
$selectedMonth = $currentDate->format('m') - 1; // months are zero-indexed
$selectedYear = $currentDate->format('Y');

// Fetch appointments for the current month
fetchAppointments($selectedYear, $selectedMonth);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Calendar for Appointments</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* style.css */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f4f4f4;
        }

        .calendar-container {
            background-color: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            border-radius: 8px;
            width: 100%;
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        h2 {
            margin: 0;
        }

        button {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
        }

        button:hover {
            color: #007bff;
        }

        .calendar-table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }

        .calendar-table th, .calendar-table td {
            text-align: center;
            padding: 10px;
            width: 14.28%;
            height: 50px;
        }

        .calendar-table th {
            background-color: #f0f0f0;
        }

        .calendar-table td {
            border: 1px solid #ddd;
        }

        .calendar-table td.empty {
            background-color: #f9f9f9;
        }

        .calendar-table td.appointment {
            background-color: #007bff;
            color: white;
            cursor: pointer;
        }

        .calendar-table td.appointment:hover {
            background-color: #0056b3;
        }

        .appointments {
            font-size: 0.8rem;
            color: #ffcc00;
            margin-top: 5px;
        }

    </style>
</head>
<body>
    <div class="calendar-container">
        <header class="calendar-header">
            <button id="prev-month">←</button>
            <h2 id="month-year"></h2>
            <button id="next-month">→</button>
        </header>
        
        <table class="calendar-table">
            <thead>
                <tr>
                    <th>Sun</th>
                    <th>Mon</th>
                    <th>Tue</th>
                    <th>Wed</th>
                    <th>Thu</th>
                    <th>Fri</th>
                    <th>Sat</th>
                </tr>
            </thead>
            <tbody id="calendar-body">
                <!-- Calendar dates will be dynamically inserted here -->
            </tbody>
        </table>
    </div>

    <script>
        // Initialize the appointment data from PHP
        const appointments = <?php echo json_encode($appointments); ?>;

        const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

        let currentDate = new Date();
        let selectedMonth = currentDate.getMonth();
        let selectedYear = currentDate.getFullYear();

        document.getElementById('prev-month').addEventListener('click', function() {
            selectedMonth--;
            if (selectedMonth < 0) {
                selectedMonth = 11;
                selectedYear--;
            }
            renderCalendar(selectedMonth, selectedYear);
        });

        document.getElementById('next-month').addEventListener('click', function() {
            selectedMonth++;
            if (selectedMonth > 11) {
                selectedMonth = 0;
                selectedYear++;
            }
            renderCalendar(selectedMonth, selectedYear);
        });

        function renderCalendar(month, year) {
            // Set the month/year header
            document.getElementById('month-year').textContent = `${monthNames[month]} ${year}`;

            // Get the first day of the month and the number of days in the month
            const firstDay = new Date(year, month, 1).getDay();
            const totalDays = new Date(year, month + 1, 0).getDate();

            // Clear the calendar body
            const calendarBody = document.getElementById('calendar-body');
            calendarBody.innerHTML = '';

            let date = 1;

            // Fill in the days
            for (let i = 0; i < 6; i++) { // 6 rows max
                const row = document.createElement('tr');

                for (let j = 0; j < 7; j++) {
                    const cell = document.createElement('td');

                    if (i === 0 && j < firstDay) {
                        cell.classList.add('empty');
                    } else if (date > totalDays) {
                        break;
                    } else {
                        cell.textContent = date;
                        
                        // Create the full appointment date (e.g., "2024-11-26")
                        const fullDate = `${year}-${String(month + 1).padStart(2, '0')}-${String(date).padStart(2, '0')}`;

                        // Check if there are any appointments on this date
                        if (appointments[fullDate]) {
                            cell.classList.add('appointment');
                            // Show appointment details (ID and time)
                            const appointmentDetails = appointments[fullDate].map(app => `${app.appointment_id} - ${app.appointment_time}`).join('<br>');
                            cell.innerHTML += `<div class="appointments">${appointmentDetails}</div>`;
                            cell.addEventListener('click', function() {
                                const appointmentsForThisDay = appointments[fullDate];
                                let appointmentText = `Appointments for ${date} ${monthNames[month]} ${year}:\n`;
                                appointmentsForThisDay.forEach(app => {
                                    appointmentText += `ID: ${app.appointment_id}, Time: ${app.appointment_time}\n`;
                                });
                                alert(appointmentText);
                            });
                        }

                        date++;
                    }

                    row.appendChild(cell);
                }

                calendarBody.appendChild(row);

                // Stop once we've added all the days
                if (date > totalDays) break;
            }
        }

        // Initialize the calendar
        renderCalendar(selectedMonth, selectedYear);
    </script>
</body>
</html>
