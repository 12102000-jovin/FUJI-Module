<?php

// Start the session, to load all the Session Variables
session_start();
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

$allDepartmentSql = "SELECT * FROM department";
$allDepartmentResult = $conn->query($allDepartmentSql);

/* ================================================================================== */
// Edit Department
if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST["department_name"]) && isset($_POST["department_id"])) {
    $departmentName = $_POST["department_name"];
    $departmentId = $_POST["department_id"];

    $updateDepartmentSql = "UPDATE department SET department_name = ? WHERE department_id = ?";
    $stmt = $conn->prepare($updateDepartmentSql);
    $stmt->bind_param("si", $departmentName, $departmentId);

    if ($stmt->execute()) {
        // Redirect to the same page after updating
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    } else {
        echo "Error updating data: " . $stmt->error;
    }
    $stmt->close();
}

/* ================================================================================== */

// Custom exception class for FK errors
class ForeignKeyException extends Exception
{
    public function __construct($msg = "Foreign key constraint violation", $code = 0)
    {
        parent::__construct($msg, $code);
    }
}

$showModal = false; // Initialize the variable

if (isset($_POST['delete_department_id'])) {
    $deleteDepartmentId = $_POST['delete_department_id'];

    // Delete department from the database
    $deleteDepartmentSql = "DELETE FROM department WHERE department_id = ?";
    $stmtDelete = $conn->prepare($deleteDepartmentSql);
    $stmtDelete->bind_param("i", $deleteDepartmentId);

    try {
        if ($stmtDelete->execute()) {
            // Success, show a success message or perform redirection if needed
            echo "Department deleted successfully!";
            header("Location: department.php");
        }
    } catch (mysqli_sql_exception $e) {
        // Check if the error message contains specific keywords indicating a foreign key error
        if (strpos($e->getMessage(), "a foreign key constraint fails") !== false) {
            // Deletion failed due to dependencies, set the error message
            $errorMessage = "Error deleting department: This department has dependencies in other tables.";
            $showModal = true; // Set the flag to show the modal
        } else {
            // Handle other exceptions
            $errorMessage = "An error occurred: " . $e->getMessage();
        }
    }

    $stmtDelete->close();
}

/* ================================================================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST["department_name"])) {
    $departmentName = $_POST["department_name"];

    $addDepartmentSql = "INSERT INTO department (department_name) VALUES (?)";
    $stmt = $conn->prepare($addDepartmentSql);
    $stmt->bind_param("s", $departmentName);

    if ($stmt->execute()) {
        // Redirect to the same page after inserting
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    } else {
        echo "Error inserting data: " . $stmt->error;
    }
    $stmt->close();
}

// ==================================================================================

// Close the database connection
$conn->close();
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
        .pagination .page-item {
            color: #043f9d;
        }

        .pagination .page-item.active .page-link {
            background-color: #043f9d;
            border-color: #043f9d;
        }
    </style>

    <!-- Title of the page -->
    <title>Department</title>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Your JavaScript code here
        $(document).ready(function() {
            const deleteButtons = document.querySelectorAll('[data-bs-target="#deleteConfirmationModal"]');
            const departmentIdInput = document.getElementById('departmentIdToDelete');

            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const departmentId = this.getAttribute('data-department-id');
                    departmentIdInput.value = departmentId;
                });
            });
        });
    </script>
</head>

<!-- ==================================================================================  -->

<body class="d-flex flex-column min-vh-100">

    <?php require_once("nav-bar.php"); ?>

    <div class="container">
        <div class="d-flex justify-content-start mt-5">
            <a class="btn btn-secondary btn-sm rounded-5 back-btn" href="javascript:history.go(-1)"> <i class="fa-solid fa-arrow-left"></i> Back </a>
        </div>
        <div class="mb-3">
            <div class="container text-center mb-3">
                <h1><strong> All Departments </strong></h1>
                <a class="btn btn-dark mt-3" href="#" data-bs-toggle="modal" data-bs-target="#addDepartmentModal" role="button"> + Add Department </a>
            </div>
            <div class="p-4 bg-light rounded-3 shadow-lg">
                <table class="table table-striped table-hover border text-center mt-3">
                    <thead class="align-middle">
                        <tr>
                            <th style="width: 15vw">Department ID</th>
                            <th>Department Name</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($allDepartmentResult->num_rows > 0) {
                            // Output data of each row
                            while ($row = $allDepartmentResult->fetch_assoc()) { ?>
                                <tr>
                                    <td> <?php echo $row['department_id'] ?> </td>
                                    <td> <?php echo $row['department_name'] ?> </td>
                                    <td>
                                        <a data-department-id="<?php echo $row['department_id'] ?>" data-department-name="<?php echo $row['department_name'] ?>">
                                            <i class="signature-color fa-regular fa-pen-to-square tooltips m-1" role="button" data-bs-toggle="modal" data-bs-target="#editDepartmentModal" title="Edit Department"></i>
                                        </a>
                                        <a>
                                            <i class="fa-regular fa-trash-can text-danger tooltips m-1" role="button" data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal" title="Delete Department" data-department-id="<?php echo $row['department_id']; ?>"></i>
                                        </a>
                                    </td>
                                <tr>
                                <?php }
                        } else { ?>
                                <tr>
                                    <td colspan='3'>No data found</td>
                                </tr>
                            <?php  } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Deparment Modal -->
    <div class="modal fade" id="addDepartmentModal" tabindex="-1" aria-labelledby="addDepartmentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addDepartmentModalLabel">Add Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Your modal content goes here -->
                    <!-- For example, you can include form fields or other content -->
                    <form method="POST" id="deleteForm">
                        <div class="mb-3">
                            <label for="departmentName" class="form-label">Department Name</label>
                            <input type="text" class="form-control" id="departmentName" name="department_name">
                        </div>
                        <!-- Add more form fields or content as needed -->
                    </form>
                </div>
                <div class="modal-footer justify-content-end"> <!-- Add 'justify-content-end' class here -->
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" form="deleteForm" class="btn signature-btn">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Department Modal -->
    <div class="modal fade" id="editDepartmentModal" tabindex="-1" aria-labelledby="editDepartmentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editDepartmentModalLabel">Edit Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="editForm">
                        <div class="mb-3">
                            <label for="editDepartmentName" class="form-label">Department Name</label>
                            <input type="text" class="form-control" id="editDepartmentName" name="department_name">
                            <input type="hidden" id="editDepartmentId" name="department_id">
                        </div>
                    </form>
                </div>
                <div class="modal-footer justify-content-end">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" form="editForm" class="btn signature-btn">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete confirmation modal -->
    <div class="modal fade" id="deleteConfirmationModal" tabindex="-1" role="dialog" aria-labelledby="confirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmationModalLabel">Confirm Deletion</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this department?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="post">
                        <input type="hidden" id="departmentIdToDelete" name="delete_department_id" value="">
                        <button type="submit" class="btn btn-danger" name="delete_department">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Condition to trigger the error deletion modal -->
    <?php if ($showModal) : ?>
        <script>
            $(document).ready(function() {
                $('#deleteErrorModal').modal('show');
            });
        </script>
    <?php endif; ?>

    <!-- Bootstrap modal for displaying deletion error messages -->
    <div class="modal fade" id="deleteErrorModal" tabindex="-1" role="dialog" aria-labelledby="deleteErrorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-top">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteErrorModalLabel">Error</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php echo $errorMessage; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <?php require_once("footer_logout.php") ?>

    <!-- JS for the tooltip -->
    <script>
        // Enabling the tooltip
        const tooltips = document.querySelectorAll('.tooltips');
        tooltips.forEach(t => {
            new bootstrap.Tooltip(t)
        });
    </script>

    <script>
        $(document).ready(function() {
            // Handle the click event of the edit button
            $('.fa-pen-to-square').click(function() {
                // Get the department name and ID from the data attributes
                var departmentName = $(this).closest('a').data('department-name');
                var departmentId = $(this).closest('a').data('department-id');
                // Set the values of the input fields in the modal
                $('#editDepartmentName').val(departmentName);
                $('#editDepartmentId').val(departmentId);
            });
        });
    </script>
</body>

</html>