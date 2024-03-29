<?php
session_start();

require_once("db_connect.php");
// Checking the inactivity 
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

// ==================================================================================

$employee_id = $_GET['employee_id'];

// (Retrieve the employee ID, role, and department from the users table)
$emp_id_query = "SELECT u.role, d.department_name, u.full_name, u.profile_image FROM users u
    JOIN department d ON u.department_id = d.department_id
    WHERE u.employee_id = '$employee_id'";
// Execute the SQL query and store the result in the $result variable
$result = $conn->query($emp_id_query);

// Check if the query result is not empty and contains one or more rows of data
if ($result && $result->num_rows > 0) {
    // Fetch the next row of data from the result set and store it in the $row variable.
    $row = $result->fetch_assoc();

    // Assigning role and department from the row data
    $role = $row['role'];
    $department = $row['department_name'];
    $full_name = $row['full_name'];
    $profile_image = $row['profile_image'];


    // Set the employee_id value in session
    $_SESSION['employeeId'] = $employee_id;
} else {
    // Handle case when no data is found for the provided employee_id
    // You might want to set default values or display an error message
    $role = "Role Not Found";
    $department = "Department Not Found";
    $full_name = "User Not Found";
}

// Free up the memory used by the database query result
$result->free();

// ==================================================================================

// Retrieve modules from the 'modules' table that have been attempted by the user along with the highest score
// Query to retrieve attempted modules and their scores for a specific employee
$attemptedModulesQuery = "
SELECT 
    module_id, 
    module_name, 
    module_description, 
    module_image, 
    score,
    result_or_written_answer
FROM (
    SELECT 
        ma.module_id, 
        m.module_name, 
        m.module_description, 
        m.module_image, 
        r.score,
        COALESCE(MAX(r.score) OVER (PARTITION BY ma.module_id ORDER BY r.score DESC), wa.written_answer) AS result_or_written_answer,
        ROW_NUMBER() OVER (PARTITION BY ma.module_id ORDER BY r.score DESC) AS row_num
    FROM 
        module_allocation ma
    JOIN 
        modules m ON ma.module_id = m.module_id
    LEFT JOIN 
        results r ON ma.module_id = r.module_id AND ma.employee_id = r.employee_id
    LEFT JOIN 
        written_answers wa ON ma.module_id = wa.module_id AND ma.employee_id = wa.employee_id
    WHERE 
        m.is_archived = '0' 
        AND (r.employee_id = '$employee_id' OR wa.employee_id = '$employee_id')
) ranked_data
WHERE 
    row_num = 1
ORDER BY 
    module_id;
    ";
$attemptedModulesResult = $conn->query($attemptedModulesQuery);

// ==================================================================================

$licenseQuery = "
    SELECT licenses.license_id, licenses.license_name
    FROM users
    INNER JOIN user_licenses ON users.employee_id = user_licenses.employee_id
    INNER JOIN licenses ON user_licenses.license_id = licenses.license_id
    WHERE users.employee_id = '$employee_id'
    ";
$licenseQueryResult = $conn->query($licenseQuery);

function getCountForModule($conn, $moduleId, $employeeId)
{
    // Count the number of records for the current module and employee in written_results
    $countQuery = "SELECT COUNT(*) AS result_count FROM written_answers WHERE module_id = $moduleId AND employee_id = $employeeId";
    $countResult = $conn->query($countQuery);

    if ($countResult) {
        $countRow = $countResult->fetch_assoc();
        $resultCount = $countRow['result_count'];

        // Free up the memory used by the count result set
        $countResult->free();

        // Return the count value (even if it's 0)
        return $resultCount;
    } else {
        // Display an error message if the count query fails
        echo "Error executing the count query: " . $conn->error;
    }

    return null;
}

?>

<!-- ================================================================================== -->

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <!-- Linking external CSSS stylesheet to apply styles to the HTML document -->
    <link rel="stylesheet" type="text/css" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="shortcut icon" type="image/x-icon" href="Images/FE-logo-icon.ico" />

    <!-- Title of the page -->
    <title> Profile </title>
</head>
<style>
    .card {
        border: none;
    }

    .profile-container {
        width: 15vh;
        /* Set the desired width */
        height: 15vh;
        /* Set the desired height */
        border-radius: 50%;
        /* Make the container circular */
        overflow: hidden;
        /* Hide any overflow content */
        position: relative;

        background-color: #043f9d;
        color: #fff;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 30px;
        font-weight: bold;
    }

    .profile-pic {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
    }
</style>
<!-- ==================================================================================  -->

<body class="d-flex flex-column min-vh-100 signature-bg-color">

    <?php require_once("nav-bar.php"); ?>
    <!-- ==================================================================================  -->

    <div class="container">
        <div class="container">
            <div class="mt-5 mb-5">
                <div class="d-flex justify-content-start mt-5">
                    <a class="btn btn-secondary btn-sm rounded-5 back-btn" href="javascript:history.go(-1)"> <i class="fa-solid fa-arrow-left"></i> Back </a>
                </div>
                <div class="d-flex justify-content-center mb-3">
                    <h1 class="text-white"><strong>Profile</strong></h1>
                </div>
                <div class="row d-flex justify-content-center">
                    <div class="card col-md-4 m-1 shadow">
                        <div class="card-body">
                            <div class="d-flex justify-content-center mt-5">
                                <div class="profile-container bg-gradient shadow-lg">
                                    <?php
                                    if ($profile_image) {
                                        echo "<td><div class='profile-container bg-gradient shadow-lg'><img src='$profile_image' alt='Profile Image' class='profile-pic'></div></td>";
                                    } else {
                                        $name_parts = explode(" ", $full_name);
                                        $initials = "";
                                        foreach ($name_parts as $part) {
                                            $initials .= strtoupper(substr($part, 0, 1));
                                        }
                                        echo $initials;
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="d-flex justify-content-center mt-1">
                                <h3><?php echo $full_name ?> </h3>
                            </div>
                            <div class="mt-5">
                                <h4><strong> Details </strong></h4>
                                <hr>
                            </div>
                            <div class="mt-3">
                                <p> <strong>Employee Id: </strong><?php echo $employee_id ?> </p>
                            </div>
                            <div>
                                <p> <strong>Department: </strong><?php echo $department ?> </p>
                            </div>
                            <div>
                                <p> <strong>Role: </strong><?php echo ucwords($role) ?> </p>
                            </div>
                            <div class="mt-5">
                                <h4><strong> Licenses </strong></h4>
                                <hr>
                            </div>
                            <div>
                                <?php
                                if ($licenseQueryResult->num_rows > 0) {
                                    while ($row = $licenseQueryResult->fetch_assoc()) {
                                        // Fetching data from the row
                                        $license_name = $row['license_name'];
                                ?>
                                        <p><strong> <?php echo $license_name; ?> </strong></p>
                                <?php
                                    }
                                } else {
                                    echo "<p>No license found.</p>";
                                }
                                ?>
                            </div>
                        </div>
                    </div>

                    <div class="card col-md-7 m-1 shadow">
                        <div class="card-body">
                            <h2 class="text-center">History</h2>
                            <div class="d-flex justify-content-center">
                                <div class="row">
                                    <?php
                                    if ($attemptedModulesResult->num_rows > 0) {
                                        while ($row = $attemptedModulesResult->fetch_assoc()) {
                                            // Fetching data from the row
                                            $module_id = $row['module_id'];
                                            $module_name = $row['module_name'];
                                            $module_image = $row['module_image'];
                                            $module_score = $row['score'];

                                            // Check if the module has already been added with a higher score
                                            if (isset($highestScores[$module_id]) && $highestScores[$module_id] >= $module_score) {
                                                continue; // Skip this module
                                            }

                                            // Store the highest score for the module
                                            $highestScores[$module_id] = $module_score;
                                    ?>
                                            <!-- Displaying each module in a card -->
                                            <div class="card mb-3 border border-dark p-4">
                                                <div class="row g-0">
                                                    <div class="col-md-4 p-2 align-self-center text-center">
                                                        <img src="<?php echo $module_image; ?>" alt="<?php echo $module_name; ?>" class="img-fluid" style="max-height: 100px; object-fit: contain;">
                                                    </div>
                                                    <div class="col-md-8">
                                                        <div class="card-body">
                                                            <h5 class="card-title"><?php echo $module_name; ?></h5>
                                                            <?php
                                                            // Get the count for the current module
                                                            $countForModule = getCountForModule($conn, $module_id, $employee_id);

                                                            if ($module_score !== null && $countForModule > 0) {
                                                                // Both MCQ score and Short Answer attempted
                                                                echo "<p class='card-text' style='margin-bottom: 0;'>Highest MCQ Score: $module_score%</p>
                                                                <div class='progress' style='height: 5px; margin-bottom: 10px;'>
                                                                  <div class='progress-bar signature-bg-color' role='progressbar' style='width: $highestScores[$module_id]%;' aria-valuenow='$highestScores[$module_id]' aria-valuemin='0' aria-valuemax='100'></div>
                                                                </div>
                                                                <a href='written-progress-more.php?module_id=$module_id' class='badge badge-pill bg-success tooltips' data-toggle='tooltip' data-bs-placement='right' title='Check Module' style='text-decoration: none; margin-top: 10px;'>Short Answer Attempted</a>";
                                                            } elseif ($countForModule > 0) {
                                                                // Short Answer attempted but no MCQ score
                                                                echo "<a href='written-progress-more.php?module_id=$module_id' class='badge badge-pill bg-success tooltips' data-toggle='tooltip' data-bs-placement='right' title='Check Module' style='text-decoration: none'>Short Answer Attempted</a>";
                                                            } elseif ($module_score !== null) {
                                                                // MCQ score but no Short Answer attempted
                                                                echo "<p class='card-text' style='margin-bottom: 0;'>Highest MCQ Score: $module_score%</p>
                                                                      <div class='progress' style='height: 5px;  margin-bottom: 10px;'>
                                                                        <div class='progress-bar signature-bg-color' role='progressbar' style='width: $highestScores[$module_id]%;' aria-valuenow='$highestScores[$module_id]' aria-valuemin='0' aria-valuemax='100'></div>
                                                                      </div>
                                                                      <a href='written-progress-more.php?module_id=$module_id' class='badge badge-pill bg-danger tooltips' data-toggle='tooltip' data-bs-placement='right' title='Check Module' style='text-decoration: none; margin-top: 10px;'>Short Answer Not Attempted</a>";
                                                            }
                                                            ?>

                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                    <?php
                                        }
                                    } else {
                                        echo "<p class='alert alert-danger mt-5'>No attempted modules found.</p>";
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================================================================== -->

    <?php require_once("footer_logout.php") ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Enabling the tooltip
        const tooltips = document.querySelectorAll('.tooltips');
        tooltips.forEach(t => {
            new bootstrap.Tooltip(t)
        })
    </script>
</body>

</html>