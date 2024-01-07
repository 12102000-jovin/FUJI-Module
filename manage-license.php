<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connect to the database
require_once("db_connect.php");
// Checking the inactivity 
require_once("inactivity_check.php");

// Get the user's role from the session data
$role = $_SESSION['userRole'];

// Check if the user's role for access permission
if ($role !== 'admin' && $role !== 'supervisor') {
    session_destroy();
    $error_message = "Access Denied.";
    header("Location: login.php?error=" . urlencode($error_message));
    exit();
}

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

/* ================================================================================== */

$allLicenseSql = "SELECT license_id, license_name FROM licenses";
$allLicenseResult = $conn->query($allLicenseSql);

/* ================================================================================== */

$employee_id = $_GET['employee_id'];

// Query to fetch data from the user_licenses table based on the employee_id
$currentLicenseSql = "SELECT licenses.license_name, user_licenses.issue_date, user_licenses.expiration_date, user_licenses.license_id, user_licenses.license_number, user_licenses.license_image
                        FROM user_licenses
                        INNER JOIN licenses ON user_licenses.license_id = licenses.license_id
                        WHERE user_licenses.employee_id = $employee_id";
$currentLicenseResult = $conn->query($currentLicenseSql);

/* ================================================================================== */

$employeeDetailSql = "SELECT u.employee_id, u.full_name, d.department_name, u.profile_image
                        FROM users u
                        JOIN department d ON u.department_id = d.department_id
                        WHERE u.employee_id = $employee_id";

$employeeDetailResult = $conn->query($employeeDetailSql);

if ($employeeDetailResult->num_rows > 0) {
    // Fetch the data as an associative array
    $employeeDetailData = $employeeDetailResult->fetch_assoc();

    $full_name = $employeeDetailData['full_name'];
    $department = $employeeDetailData['department_name'];
    $profile_image = $employeeDetailData['profile_image'];
} else {
    echo "No employee found with ID: " . $employee_id;
}

/* ================================================================================== */

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $license_name = $_POST['license_name'];
    $issue_date = $_POST['issue_date'];
    $expiration_date = $_POST['expiration_date'];
    $license_number = $_POST['license_number'];
    $license_image = $_FILES["license_image"];

    // File paths
    $imagePath = "./Images/" . basename($license_image["name"]);

    // Move uploaded files to the specified directories
    move_uploaded_file($license_image["tmp_name"], $imagePath);

    // Retrieve license_id from licenses table
    $retrieve_license_query = "SELECT license_id FROM licenses WHERE license_name = ?";
    $stmt = $conn->prepare($retrieve_license_query);
    $stmt->bind_param("s", $license_name);
    $stmt->execute();
    $stmt->bind_result($license_id);
    $stmt->fetch();
    $stmt->close();

    // Check if license_id is retrieved
    if ($license_id) {
        // Check if license_name already exists for the employee in user_licenses table
        $check_license_query = "SELECT COUNT(*) FROM user_licenses WHERE employee_id = ? AND license_id = ?";
        $stmt = $conn->prepare($check_license_query);
        $stmt->bind_param("ii", $employee_id, $license_id);
        $stmt->execute();
        $stmt->bind_result($license_count);
        $stmt->fetch();
        $stmt->close();

        if ($license_count > 0) {
            // License already exists, show notification
            $_SESSION['licenseExist'] = true;
        } else {
            // License doesn't exist, proceed with insertion

            // Insert data into user_licenses table
            $insert_query = "INSERT INTO user_licenses (employee_id, license_id, license_number, issue_date, expiration_date, license_image) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("iissss", $employee_id, $license_id, $license_number, $issue_date, $expiration_date, $imagePath);

            if ($stmt->execute()) {
                // Set a session variable to indicate successful insertion

                $_SESSION['insert_success'] = true;

                // Redirect to the same page after inserting
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit();
            } else {
                echo "Error inserting data: " . $stmt->error;
            }
            $stmt->close();
        }
    } else {
        echo "License not found.";
    }
}

/* ================================================================================== */

$licenseNamesSql = "SELECT DISTINCT license_name FROM licenses";
$licenseNamesResult = $conn->query($licenseNamesSql);
$licenseNames = array();

if ($licenseNamesResult->num_rows > 0) {
    while ($row = $licenseNamesResult->fetch_assoc()) {
        $licenseNames[] = $row["license_name"];
    }
}

/* ================================================================================== */

if (isset($_GET['employee_id']) && isset($_GET['license_id']) && isset($_GET['delete'])) {
    $employee_id = $_GET['employee_id'];
    $license_id = $_GET['license_id'];

    $deleteSql = "DELETE FROM user_licenses WHERE employee_id = ? AND license_id = ?";
    $deleteStmt = mysqli_prepare($conn, $deleteSql);
    mysqli_stmt_bind_param($deleteStmt, "ii", $employee_id, $license_id);
    $deleteResult = mysqli_stmt_execute($deleteStmt);

    if ($deleteResult) {
        // Deletion successful, refresh the page
        $params = http_build_query(array('employee_id' => $employee_id, 'license_id' => $license_id));
        header("Location: manage-license.php?$params");
        exit();
    } else {
        // Deletion failed
        echo "Error deleting the license.";
    }
}

/* ================================================================================== */

// Check if the form is submitted for editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['license_id'])) {
    // Get form data
    $edited_license_id = $_POST['license_id'];
    $edited_issue_date = $_POST['issue_date'];
    $edited_expiration_date = $_POST['expiration_date'];
    $edited_license_number = $_POST['license_number'];
    $edited_license_image = $_FILES["license_image"];

    if ($edited_license_image["size"] > 0) {
        // File paths
        $editedImagePath = "./Images/" . basename($edited_license_image["name"]);

        // Move uploaded files to the specified directories
        move_uploaded_file($edited_license_image["tmp_name"], $editedImagePath);
    } else {
        $editedImagePath = $_POST['existing_license_image_path'];
    }

    // Update data in user_licenses table
    $update_query = "UPDATE user_licenses SET issue_date = ?, expiration_date = ?, license_number = ?, license_image = ? WHERE employee_id = ? AND license_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ssssii", $edited_issue_date, $edited_expiration_date, $edited_license_number, $editedImagePath, $employee_id, $edited_license_id);

    if ($stmt->execute()) {
        // Set a session variable to indicate successful update

        $_SESSION['update_success'] = true;

        // Redirect to the same page after updating
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    } else {
        echo "Error updating data: " . $stmt->error;
    }
    $stmt->close();
}

$conn->close();

/* ================================================================================== */
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <!-- Linking external CSS stylesheet to apply styles to the HTML document -->
    <link rel="stylesheet" type="text/css" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="shortcut icon" type="image/x-icon" href="Images/FE-logo-icon.ico" />
    <!-- Internal CSS for the HTML -->
    <style>
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

        .table thead th {
            background-color: #043f9d;
            color: white;
            border: 1px solid #043f9d !important;
        }

        @media (max-width:576px) {

            .addBtn,
            .manageBtn {
                font-size: 12px;
            }
        }

        .no-wrap {
            white-space: nowrap;
        }
    </style>

    <!-- Title of the page -->
    <title>Licenses</title>
</head>

<!-- ==================================================================================  -->

<body class="d-flex flex-column min-vh-100">

    <?php require_once("nav-bar.php"); ?>

    <!-- ================================================================================== -->

    <div class="container mt-5">
        <div class="d-flex justify-content-start">
            <a class="btn btn-secondary btn-sm rounded-5 back-btn" href="javascript:history.go(-1)"> <i class="fa-solid fa-arrow-left"></i> Back </a>
        </div>
        <div class="d-flex justify-content-center mb-3">
            <h1><strong>Manage License </strong></h1>
        </div>
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class=" bg-light rounded-3 p-4 shadow-lg">
                    <div class="container justify-content-center">
                        <div class="row align-items-center mt-4 mb-4">
                            <div class="col-md-6 d-flex flex-column justify-content-center align-items-center">
                                <div class="profile-container shadow-lg bg-gradient">
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
                                <div class="mt-1 m-2">
                                    <h4><?php echo $full_name; ?></h4>
                                </div>
                            </div>
                            <div class="col-md-5 signature-bg-color bg-gradient rounded-3 text-white d-flex shadow-lg">
                                <div class="p-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <div>
                                            <h4 class="mb-1">Employee Id:</h4>
                                            <p class="mb-0"><?php echo $employee_id; ?></p>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <h4 class="mb-1">Department:</h4>
                                        <p class="mb-0"><?php echo $department; ?></p>
                                    </div>
                                    <div>
                                        <a href="profile-more.php?employee_id=<?php echo $employee_id; ?>" class="btn btn-dark me-2">View Profile</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>

                    <div class="d-flex justify-content-between mb-3">
                        <div class="d-flex align-items-center">
                            <h4 class="my-0"><strong> User Licenses </strong></h4>
                        </div>
                        <button type="button" class="btn btn-dark addBtn" data-bs-toggle="modal" data-bs-target="#addLicenseModal">
                            + Add License
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover border">
                            <thead class="signature-bg-color">
                                <tr class="text-center align-middle">
                                    <th>License Image</th>
                                    <th>License Id</th>
                                    <th>License Name</th>
                                    <th>Issue Date</th>
                                    <th>Expiration Date</th>
                                    <th colspan="3"> Action</th>
                                </tr>
                            </thead>
                            <tbody class="align-middle">
                                <?php if ($currentLicenseResult->num_rows > 0) {
                                    while ($row = $currentLicenseResult->fetch_assoc()) { ?>
                                        <tr class="text-center">
                                            <td><img src=" <?php echo $row["license_image"] ?>" alt="<?php echo $row["license_image"]; ?>" class="img-fluid" style="max-height: 100px; max-width: 100px;">
                                            </td>
                                            <td><?php echo $row["license_number"]; ?></td>
                                            <td class="no-wrap"><?php echo $row["license_name"]; ?></td>
                                            <td class="no-wrap"><?php echo $row["issue_date"]; ?></td>
                                            <td class="no-wrap"><?php echo $row["expiration_date"]; ?></td>
                                            <td>
                                                <a href="#" onclick="showEditModal(<?php echo $employee_id; ?>, <?php echo $row['license_id']; ?>, '<?php echo $row['issue_date']; ?>', '<?php echo $row['expiration_date']; ?>', '<?php echo $row['license_number']; ?>', '<?php echo $row['license_image']; ?>')">
                                                    <i class="fa-regular fa-pen-to-square signature-color tooltips" data-toggle="tooltip" data-placement="top" title="Edit License"></i>
                                                </a>
                                            </td>
                                            <td>
                                                <a href="#" onclick="showDeleteConfirmation(<?php echo $employee_id; ?>, <?php echo $row['license_id']; ?>)">
                                                    <i class="text-danger fa-regular fa-trash-can tooltips" data-toggle="tooltip" data-placement="top" title="Delete License"></i>
                                                </a>
                                            </td>
                                            <td>
                                                <a href="#" onclick="showLicenseImage(<?php echo $employee_id; ?>, <?php echo $row['license_id'] ?>, '<?php echo $row['license_image'] ?>')">
                                                    <i class="signature-color fa-regular fa-image tooltips" data-toggle="tooltip" data-placement="top" title="View License"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php }
                                } else { ?>
                                    <tr>
                                        <td colspan='6' class="text-center">No licenses found for this employee.</td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================================================================== -->

    <!-- License Data Table -->
    <div class="container mb-5">
        <div class="mb-3 mt-5">
            <div class="p-4 bg-light rounded-3 shadow-lg">
                <div class="d-flex justify-content-between mb-3">
                    <div class="d-flex align-items-center">
                        <h4 class="my-0"><strong> All Licenses </strong></h4>
                    </div>
                    <a type="button" class="btn btn-dark manageBtn" href="licenses.php">
                        Manage License
                    </a>
                </div>
                <table class="table table-striped table-hover border">
                    <thead class="align-middle">
                        <tr>
                            <th>License Name</th>
                        </tr>
                    </thead>
                    <tbody class="align-middle">
                        <?php
                        if ($allLicenseResult->num_rows > 0) {
                            // Output data of each row
                            while ($row = $allLicenseResult->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . $row["license_name"] . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='3'>No data found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ================================================================================== -->

    <!-- Add License Form Modal -->
    <div class="modal fade" id="addLicenseModal" tabindex="-1" aria-labelledby="addLicenseModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addLicenseModalLabel">Add License</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="license_name" class="form-label">License Name</label>
                            <select class="form-select" id="license_name" name="license_name" required>
                                <option value="" selected disabled>Select a License Name</option>
                                <?php foreach ($licenseNames as $licenseName) : ?>
                                    <option value="<?php echo $licenseName; ?>"> <?php echo $licenseName; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="license_number" class="form-label">License Number </label>
                            <input type="text" class="form-control" id="license_number" name="license_number" required>
                        </div>
                        <div class="mb-3">
                            <label for="issue_date" class="form-label">Issue Date</label>
                            <input type="date" class="form-control" id="issue_date" name="issue_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="expiration_date" class="form-label">Expiration Date</label>
                            <input type="date" class="form-control" id="expiration_date" name="expiration_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="license_image" class="form-label">License Image</label>
                            <input type="file" class="form-control" id="license_image" name="license_image" required>
                        </div>
                        <input type="hidden" name="employee_id" value="<?php echo $employee_id; ?>">
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn signature-btn">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================================================================== -->

    <!-- Successful insertion of license Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" role="dialog" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="successModalLabel">Successful</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    License has successfully added.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================================================================== -->

    <!-- Successful update of license Modal -->
    <div class="modal fade" id="updateSuccessModal" tabindex="-1" role="dialog" aria-labelledby="updateSuccessModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateSuccessModal">Successful</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    License has successfully updated.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================================================================== -->

    <!-- Modal if the user want to input existing license -->
    <div class="modal fade" id="notificationModal" tabindex="-1" role="dialog" aria-labelledby="notificationModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="notificationModalLabel">Error</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    License already exists for this employee.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================================================================== -->

    <!-- Delete confirmation modal -->
    <div class="modal fade" id="deleteConfirmationModal" tabindex="-1" role="dialog" aria-labelledby="confirmationModalLabel" aria-hidden="true" style="z-index: 1070;">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmationModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this license?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================================================================== -->

    <!-- Edit license modal -->
    <div class="modal fade" id="editLicenseModal" tabindex="-1" role="dialog" aria-labelledby="editLicenseModalLabel" aria-hidden="true" style="z-index: 1080;">
        <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 600px;">
            <div class=" modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editLicenseModalLabel">Edit License</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editLicenseForm" method="post" enctype="multipart/form-data">
                        <div class="mb-4">
                            <label for="license_number" class="form-label">License Number</label>
                            <input type="text" class="form-control" id="edit_license_number" name="license_number" required>
                        </div>
                        <div class="mb-4">
                            <label for="edit_issue_date" class="form-label">Issue Date</label>
                            <input type="date" class="form-control" id="edit_issue_date" name="issue_date" required>
                        </div>
                        <div class="mb-4">
                            <label for="edit_expiration_date" class="form-label">Expiration Date</label>
                            <input type="date" class="form-control" id="edit_expiration_date" name="expiration_date" required>
                        </div>
                        <div class="mb-4">
                            <label for="edit_license_image" class="form-label">License Image</label>
                            <input type="file" class="form-control" id="edit_license_image" name="license_image">
                            <!-- Check if $row is set before accessing its elements -->
                            <input type="hidden" id="existing_license_image_path" name="existing_license_image_path" value="">

                        </div>
                        <input type="hidden" id="edit_license_id" name="license_id">
                        <input type="hidden" name="employee_id" value="<?php echo $employee_id; ?>">
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn signature-btn">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================================================================== -->

    <!-- Modal for displaying license image -->
    <div class="modal fade" id="licenseImageModal" tabindex="-1" role="dialog" aria-labelledby="licenseImageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="licenseImageModalLabel">License Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex justify-content-center">
                        <img id="modalLicenseImage" src="" alt="License Image" class="img-fluid">
                    </div>
                </div>
            </div>
        </div>
    </div>


    <?php require_once("footer_logout.php") ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- ================================================================================== -->
    <!-- Modal for successful insertion -->
    <script>
        // Modal for successful insertion of license
        $(document).ready(function() {
            // Check if the session variable is set
            <?php
            if (isset($_SESSION['insert_success']) && $_SESSION['insert_success'] === true) {
                // Unset the session variable
                unset($_SESSION['insert_success']);
            ?>
                // Show the success modal
                $("#successModal").modal("show");
            <?php
            }
            ?>
        });
    </script>

    <!-- ==================================================================================  -->
    <!-- Error for inserting existing license -->
    <script>
        $(document).ready(function() {
            // Check if the session variable is set
            <?php
            if (isset($_SESSION['licenseExist']) && $_SESSION['licenseExist'] === true) {
                // Unset the session variable
                unset($_SESSION['licenseExist']);
            ?>
                // Show the success modal
                $("#notificationModal").modal("show");
            <?php
            }
            ?>
        });
    </script>

    <script>
        // Show the update success modal
        $(document).ready(function() {
            <?php
            if (isset($_SESSION['update_success']) && $_SESSION['update_success'] === true) {
                // Unset the session variable
                unset($_SESSION['update_success']);
            ?>
                // Show the update success modal
                $("#updateSuccessModal").modal("show");
            <?php
            }
            ?>
        });
    </script>

    <!-- ================================================================================== -->
    <!-- JS for the tooltip -->
    <script>
        // Enabling the tooltip
        const tooltips = document.querySelectorAll('.tooltips');
        tooltips.forEach(t => {
            new bootstrap.Tooltip(t)
        })
    </script>

    <!-- ==================================================================================  -->

    <!-- JS for showing deleting license modal -->
    <script>
        // Function to show the confirmation modal
        function showDeleteConfirmation(employeeId, licenseId) {
            $('#deleteConfirmationModal').modal('show');

            // Add event listener to the confirm button
            $('#confirmDelete').off('click').on('click', function() {
                // Redirect to the delete script with proper parameters
                window.location.href = `manage-license.php?employee_id=${employeeId}&license_id=${licenseId}&delete=true`;
            });
        }
    </script>

    <!-- ================================================================================== -->

    <!-- Modal to update the license details -->
    <script>
        // Function to show the edit modal and populate fields
        function showEditModal(employeeId, licenseId, issueDate, expirationDate, licenseNumber, existingImagePath) {
            // Set the form fields in the edit modal
            $('#edit_issue_date').val(issueDate);
            $('#edit_expiration_date').val(expirationDate);
            $('#edit_license_id').val(licenseId);
            $('#edit_license_number').val(licenseNumber);

            // Set the existing image path
            $('#existing_license_image_path').val(existingImagePath);

            // Show the edit modal
            $('#editLicenseModal').modal('show');
        }
    </script>

    <script>
        function showLicenseImage(employeeId, licenseId, licenseImage) {
            // Set the src attribute of the modalLicenseImage element
            document.getElementById('modalLicenseImage').src = licenseImage;

            // Show the modal
            $('#licenseImageModal').modal('show');
        }
    </script>

    <!-- ==================================================================================  -->
    <!-- Modal to show the license details -->
    <!-- <script>
        let licenseImageModal = document.getElementById('licenseImageModal');
        licenseImageModal.addEventListener('show.bs.modal', function(event) {
            let button = event.relatedTarget;

            // Check if the clicked element has the 'data-bs-toggle' attribute with a value of 'modal'
            if (button && button.getAttribute('data-bs-toggle') === 'modal') {
                // If the clicked element is the edit button, prevent the default behavior (opening the modal)
                event.preventDefault();
            } else {
                let row = button.closest('tr');

                let licenseImageSrc = row.cells[0].querySelector('img').getAttribute('src');
                let licenseId = row.cells[1].textContent;

                let resultDetails = document.getElementById('resultDetails');
                resultDetails.innerHTML = `
                <p><strong>License ID:</strong> ${licenseId}</p>
                <div class="d-flex justify-content-center">
                    <img src="${licenseImageSrc}" alt="License Image" class="img-fluid" style="max-height: 400px; max-width: 100%;">
                </div>
            `;
            }
        });
    </script> -->


</body>

</html>