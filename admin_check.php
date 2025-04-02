<?php
// admin_check.php
function isAdminPage($page) {
    // List of pages that admin can access
    $allowed_pages = [
        'admin_dashboard.php',
        'patients.php',
        'dentists.php',
        'appointments.php',
        'admin_add_services.php',
        'reports.php',
        'profile.php',
        'settings.php',
        'add_dentist.php',
        'view_dentist.php',
        'admin_confirm_reschedule.php',
        'admin_view_reschedule.php',
        'admin_appointment_reports.php',
        'admin_edit_dentist.php',
        'admin_show_services.php',
        'admin_edit_service.php',
        'admin_delete_service.php',
        'admin_archive_dentist.php',
        'admin_settings.php',
        'admin_clinic_info.php',
        'admin_calendar_appointments.php'

    ];

    return in_array($page, $allowed_pages);
}
?>
