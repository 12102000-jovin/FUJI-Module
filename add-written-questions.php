<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("db_connect.php");
require_once("inactivity_check.php");

$role = $_SESSION['userRole'];

// Check if the user is not logged in, then redirect to the login page
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Logout script
if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    session_destroy();
    header("Location: login.php");
    exit();
}

$module_id = $_GET['module_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addWrittenQuestion'])) {
    // Sanitise and get the inoput values
    $writtenQuestion = htmlspecialchars($_POST['writtenQuestion']);

    // Insert the question into the database
    $add_query = "INSERT INTO written_questions (question, module_id) VALUES ('$writtenQuestion', '$module_id')";
    $add_result = $conn->query($add_query);

    if ($add_result) {
        echo "Question added successfully!";
    } else {
        echo "Error adding question: " . $conn->error;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="shortcut icon" type="image/x-icon" href="Images/FE-logo-icon.ico" />
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>Written Quiz List</title>
</head>

<style>

</style>

<body class="d-flex flex-column min-vh-100">
    <?php require_once("nav-bar.php"); ?>

    <div class="container">
        <div class="text-center mt-5">
            <h1>Add Written Question</h1>
        </div>
    </div>

    <div class="container">
        <form method="POST">
            <div class="mb-3 mt-5">
                <label for="writtenQuestion" class="form-label"><strong> Written Question </strong></label>
                <textarea class='form-control' name='writtenQuestion' id='writtenQuestion' rows='3'></textarea>
            </div>

            <div class="mt-5 text-center">
                <button type="submit" name="addWrittenQuestion" class="btn btn-dark"> Add Question </button>
            </div>
        </form>
    </div>

    <!-- Footer Section -->
    <footer class="bg-light text-center py-4 mt-auto shadow-lg">
        <div class="container">
            <p class="mb-0 font-weight-bold" style="font-size: 1.5vh"><strong>&copy; <?php echo date('Y'); ?> FUJI Training Module. All rights reserved.</strong></p>
        </div>
    </footer>

    <!-- Logout Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to logout?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="?logout=true" class="btn btn-danger">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>