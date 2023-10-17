<?php
session_start();

// Check if the user is logged in and has an active session
if (isset($_SESSION['username']) && isset($_SESSION['logged_in'])) {
    // Check if the last activity timestamp is set
    if (isset($_SESSION['last_activity'])) {
        // Set the inactivity threshold (in seconds)
        $inactivityThreshold = 7200; // (adjust as needed)

        // Calculate the time difference between the current time and the last activity
        $lastActivityTime = strtotime($_SESSION['last_activity']);
        $currentTime = time();
        $timeDifference = $currentTime - $lastActivityTime;

        // Check if the user has been inactive for a certain amount of time
        if ($timeDifference > $inactivityThreshold) {
            // Perform any necessary action (e.g., log out the user)
            session_unset();
            session_destroy();

            // Redirect back to the form page with an error message
            $error_message = "Your session has expired please login again.";
            header("Location: login.php?error=" . urlencode($error_message));
            exit();
        }
    }

    // Update the last activity timestamp
    $_SESSION['last_activity'] = date('Y-m-d H:i:s');

    // Check if the user has been deactivated
    $username = $_SESSION['username'];

    $stmt_check_status = $conn->prepare("SELECT is_active FROM users WHERE username = ?");
    $stmt_check_status->bind_param("s", $username);
    $stmt_check_status->execute();
    $stmt_check_status->bind_result($is_active);
    $stmt_check_status->fetch();
    $stmt_check_status->close();

    if ($is_active == 0) {
        // Perform session termination
        session_unset();
        session_destroy();

        $error_message = "Access to your account has been disabled.";
        header("Location: login.php?error=" . urlencode($error_message));
        exit();
    }
}
