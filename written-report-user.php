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

$userSql = "SELECT employee_id, full_name, profile_image FROM users";
$userResult = $conn->query($userSql);

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
    <title>Short Answer Quiz Module</title>

    <style>
        .card {
            transition: all 0.1s;
        }

        .card:hover {
            transform: scale(1.01);
        }
    </style>
</head>

<body class="d-flex flex-column min-vh-100 signature-bg-color">
    <?php require_once("nav-bar.php"); ?>

    <div class="container mb-4 text-light">
        <div class="d-flex justify-content-start mt-5">
            <a class="btn btn-secondary btn-sm rounded-5 back-btn" href="javascript:history.go(-1)"> <i class="fa-solid fa-arrow-left"></i> Back </a>
        </div>
        <h1 class="text-center mt-4"><strong>Short Answer Report</strong></h1>
    </div>

    <div class="container mt-2 mb-5">
        <div class="row row-cols-1 row-cols-md-4 g-5">
            <?php
            if ($userResult->num_rows > 0) {
                while ($row = $userResult->fetch_assoc()) {
                    $profile_image = $row['profile_image'];
                    $full_name = $row['full_name'];
                    $employee_id = $row['employee_id'];
            ?>
                    <div class="col">
                        <a href="written-report-module.php?employee_id=<?php echo $employee_id; ?>" class="card-link text-decoration-none">
                            <div class="card h-100 shadow">
                                <div class="d-flex justify-content-center">
                                    <?php
                                    if ($profile_image) {
                                        echo "<td><div><img src='$profile_image' alt='Profile Image' class='profile-pic mt-3' style='max-width: 5vh; max-height: 5vh'></div></td>";
                                    } else {
                                        $name_parts = explode(" ", $full_name);
                                        $initials = "";
                                        foreach ($name_parts as $part) {
                                            $initials .= strtoupper(substr($part, 0, 1));
                                        }
                                        echo "<td><strong class='mt-3 bg-secondary p-2 rounded-5 text-white'> $initials </strong></td>";
                                    }
                                    ?>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title text-center"><?php echo $full_name; ?></h5>

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


    <?php require_once("footer_logout.php"); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>