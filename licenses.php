<?php

session_start();

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

$select_license =  "SELECT * FROM licenses";
$select_license_result = $conn->query($select_license);

/* ================================================================================== */

// Editing license
if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST["license_name"]) && isset($_POST["license_id"])) {
    $licenseName = $_POST["license_name"];
    $licenseId = $_POST["license_id"];

    $updateLicenseSql = "UPDATE licenses SET license_name = ? WHERE license_id = ?";
    $stmt = $conn->prepare($updateLicenseSql);
    $stmt->bind_param("si", $licenseName, $licenseId);

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

// Adding license query
if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST["license_name"])) {
    $licenseName = $_POST["license_name"];

    $addLicenseSql = "INSERT INTO licenses (license_name) VALUES (?)";
    $stmt = $conn->prepare($addLicenseSql);
    $stmt->bind_param("s", $licenseName);

    if ($stmt->execute()) {
        // Redirect to the same page after inserting
        header("Location: licenses.php");
        exit();
    } else {
        echo "Error inserting data: " . $stmt->error;
    }
    $stmt->close();
}

if (isset($_GET['delete_license'])) {
    $licenseIdToDelete = $_GET['delete_license'];

    // Perform the license deletion operation in the database
    $deleteLicenseSql = "DELETE FROM licenses WHERE license_id = ?";
    $stmt = $conn->prepare($deleteLicenseSql);
    $stmt->bind_param("i", $licenseIdToDelete);

    if ($stmt->execute()) {
        // Redirect back to the same page after successful deletion
        header("Location: licenses.php");
        exit();
    } else {
        echo "Error deleting license: " . $stmt->error;
    }
    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="shortcut icon" type="image/x-icon" href="Images/FE-logo-icon.ico" />
    <!-- Linking external CSSS stylesheet to apply styles to the HTML document -->
    <link rel="stylesheet" type="text/css" href="style.css">

    <style>
        @media (max-width: 576px) {

            /* Adjust table styles for small screens */
            table {
                font-size: 12px;
            }

            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
        }

        .table thead th {
            background-color: #043f9d;
            color: white;
            border: 1px solid #043f9d !important;
        }
    </style>

    <!-- Title of the page -->
    <title> Manage Licenses</title>
</head>

<body class="d-flex flex-column min-vh-100">

    <?php require_once("nav-bar.php"); ?>

    <!-- License Data Table -->
    <div class="container">
        <div class="d-flex justify-content-start mt-5">
            <a class="btn btn-secondary btn-sm rounded-5 back-btn" href="javascript:history.go(-1)"> <i class="fa-solid fa-arrow-left"></i> Back </a>
        </div>
        <div class="text-center mb-4">
            <h1><strong>All Licenses</strong></h1>
        </div>
        <div class="d-flex justify-content-center mb-3">
            <a type="button" class="btn btn-dark" href="#" data-bs-toggle="modal" data-bs-target="#addLicenseModal" role="button">
                + Add License
            </a>
        </div>
        <div class="p-4 bg-light rounded-3 shadow-lg">
            <table class="table table-striped table-hover border">
                <thead class="text-center">
                    <tr>
                        <th>License Id</th>
                        <th>License Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody class="text-center">
                    <?php
                    if ($select_license_result->num_rows > 0) {
                        // Output data of each row
                        while ($row = $select_license_result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td style='width: 16vw'>" . $row["license_id"] . "</td>";
                            echo "<td>" . $row["license_name"] . "</td>";
                            echo '<td> 
                                        <a data-license-name="' . $row["license_name"] . '" data-license-id="' . $row["license_id"] . '"> 
                                            <i class="signature-color fa-regular fa-pen-to-square tooltips m-1" data-bs-toggle="modal" data-bs-target="#editLicenseModal" data-toggle="tooltip" data-placement="top" title="Edit License"></i>
                                        </a> 
                                        <a href="#" class="delete-license" data-license-id="' . $row["license_id"] . '">
                                        <i class="text-danger fa-regular fa-trash-can tooltips m-1" data-toggle="tooltip" data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal" data-placement="top" title="Delete License"></i>
                                    </a>
                                    </td>';
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

    <!-- Add License Modal -->
    <div class="modal fade" id="addLicenseModal" tabindex="-1" aria-labelledby="addLicenseModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addLicenseModalLabel">Add License</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="addForm">
                        <div class="mb-3">
                            <label for="licenseName" class="form-label">License Name</label>
                            <input type="text" class="form-control" id="licenseName" name="license_name">
                        </div>
                    </form>
                </div>
                <div class="modal-footer justify-content-end">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" form="addForm" class="btn signature-btn">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit License Modal -->
    <div class="modal fade" id="editLicenseModal" tabindex="-1" aria-labelledby="editLicenseModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editLicenseModalLabel">Edit License</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="editForm">
                        <div class="mb-3">
                            <label for="editLicenseName" class="form-label">License Name</label>
                            <input type="text" class="form-control" id="editLicenseName" name="license_name">
                            <input type="hidden" id="editLicenseId" name="license_id">
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
                    Are you sure you want to delete this license?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <?php require_once("footer_logout.php"); ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            // Handle the click event of the edit button
            $('.fa-pen-to-square').click(function() {
                // Get the license name and ID from the data attributes
                var licenseName = $(this).closest('a').data('license-name');
                var licenseId = $(this).closest('a').data('license-id');
                // Set the values of the input fields in the modal
                $('#editLicenseName').val(licenseName);
                $('#editLicenseId').val(licenseId);
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            // Handle the click event of the delete link
            $('.delete-license').click(function() {
                // Get the license ID from the data attribute
                var licenseIdToDelete = $(this).data('license-id');

                // Set the license ID in the delete confirmation modal
                $('#confirmDelete').data('license-id', licenseIdToDelete);
            });

            // Handle the click event of the "Delete" button in the delete confirmation modal
            $('#confirmDelete').click(function() {
                // Get the license ID from the data attribute
                var licenseIdToDelete = $(this).data('license-id');

                // Redirect to the delete URL with the license ID as a parameter
                window.location.href = '?delete_license=' + licenseIdToDelete;
            });
        });
    </script>

    <!-- JS for the tooltip -->

    <script>
        // Enabling the tooltip
        const tooltips = document.querySelectorAll('.tooltips');
        tooltips.forEach(t => {
            new bootstrap.Tooltip(t)
        })
    </script>
</body>

</html>