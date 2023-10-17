<?php
// Start the session, to load all the Session Variables
session_start();
// Connect to the database
require_once("db_connect.php");
// Checking the inactivity 
require_once("inactivity_check.php");
$role = $_SESSION['userRole'];

// Check if the user is not logged in, then redirect to the login page
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Check if the user wants to logout or not
if (isset($_GET['logout']) && ($_GET['logout']) === 'true') {
    // Terminate all the session
    session_destroy();
    header("Location: login.php");
    exit();
}

$moduleId = $_GET['module_id'];

// Fetch module data from the database
$query = "SELECT * FROM modules WHERE module_id = $moduleId"; // Update the query condition based on your requirements
$result = mysqli_query($conn, $query);
$moduleData = mysqli_fetch_assoc($result);

// Close the database connection
$conn->close();
?>

<!-- ================================================================================== -->

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="shortcut icon" type="image/x-icon" href="Images/FE-logo-icon.ico" />
    <!-- Linking external CSSS stylesheet to apply styles to the HTML document -->
    <link rel="stylesheet" type="text/css" href="style.css">

    <!-- Title of the page -->
    <title> Module Video</title>
</head>

<!-- ================================================================================== -->

<body class="d-flex flex-column min-vh-100">

    <?php require_once("nav-bar.php"); ?>

    <div class="text-white mt-5 p-3 bg-gradient signature-bg-color shadow">
        <div class="row justify-content-center">
            <div class="col-12 col-md-6 text-center">
                <h1 class="c" style="font-size:4vh">This is <?php echo $moduleData['module_name']; ?> Module</h1>
            </div>
        </div>
        <div class="container">
            <p class="fs-6 mt-5" style="font-size:3vh; text-align: justify;"><?php echo $moduleData['module_description']; ?></p>
        </div>
    </div>

    <!-- ================================================================================== -->

    <div class="container" style="width: 70%">
        <div class="alert alert-danger mt-5 text-center p-1 shadow" role="alert">
            <p class="mt-2" style="font-size: 1.3vh;"><strong>You must score every question correctly to complete this module.</strong></p>
            <p style="font-size: 1.3vh"><strong>Please watch the video below to answer the questions in this module accurately.</strong></p>
        </div>
    </div>

    <!-- ================================================================================== -->

    <div class="container mt-5">
        <div class="row">
            <div class="col-md-10 offset-md-1 col-12 text-center">
                <div class="embed-responsive video-container">
                    <video controls allowfullscreen>
                        <source src="<?php echo $moduleData['module_video']; ?>">
                    </video>
                </div>
            </div>
        </div>
    </div>

    <div class="container mb-5">
        <div class="d-flex justify-content-center" style="margin-top: 3vh">
            <a class="btn btn-secondary m-1" href="modules.php" role="button">Back</a>
            <button type="button" class="btn m-1 signature-btn shadow" onclick="window.location.href='module-quiz.php?module_id=<?php echo $moduleId; ?>';" style="text-decoration: none;">Start Module</button>
        </div>
    </div>

    <!-- ==================================================================================  -->

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

    <!-- Footer Section -->
    <footer class="bg-light text-center py-4 mt-auto shadow-lg">
        <div class="container">
            <p class="mb-0 font-weight-bold" style="font-size: 1.5vh"><strong>&copy; <?php echo date('Y'); ?> FUJI Training Module. All rights reserved.</strong></p>
        </div>
    </footer>

    <!-- ================================================================================== -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>