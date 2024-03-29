<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("db_connect.php");
require_once("inactivity_check.php");

$employee_id = $_SESSION['employeeId'] ?? 'N/A';

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

// ================================================================================== 

$selectModuleSql = "SELECT m.module_id, m.module_name, m.module_image , ma.module_id
                    FROM modules m
                    JOIN module_allocation ma ON m.module_id = ma.module_id AND ma.employee_id = '$employee_id'";
$selectModuleResult = $conn->query($selectModuleSql);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="shortcut icon" type="image/x-icon" href="Images/FE-logo-icon.ico" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>Short Answer Quiz Progress</title>

    <style>
        .card {
            transition: all 0.1s;
        }

        .card:hover {
            transform: scale(1.02);
        }
    </style>
</head>

<body class="d-flex flex-column min-vh-100 signature-bg-color">
    <?php require_once("nav-bar.php"); ?>

    <div class="container mt-5 mb-4 text-light">
        <div class="d-flex justify-content-start">
            <a class="btn btn-secondary btn-sm rounded-5 back-btn" href="javascript:history.go(-1)"> <i class="fa-solid fa-arrow-left"></i> Back </a>
        </div>
        <h1 class="text-center "><strong>Your Short Answer Progress</strong></h1>
    </div>

    <!-- Content Section -->
    <div class="container mt-2 mb-5">
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php
            if ($selectModuleResult->num_rows > 0) {
                while ($row = $selectModuleResult->fetch_assoc()) {
                    $module_image = $row['module_image'];
                    $module_name = $row['module_name'];
                    $module_id = $row['module_id'];
            ?>
                    <div class="col">
                        <a href="written-progress-more.php?module_id=<?php echo $module_id; ?>" class="card-link text-decoration-none">
                            <div class="card h-100 shadow">
                                <img src="<?php echo $module_image; ?>" class="card-img-top" alt="<?php echo $module_name; ?>">
                                <div class="card-body">
                                    <h5 class="card-title text-center"><?php echo $module_name; ?></h5>
                                </div>
                            </div>
                        </a>
                    </div>
            <?php
                }
            }
            ?>
        </div>
    </div>
    <?php require_once("footer_logout.php") ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>