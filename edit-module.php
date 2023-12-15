<?php
// Start the session, to load all the Session Variables
session_start();

// Connect to the database
require_once("db_connect.php");

// Checking the inactivity 
require_once("inactivity_check.php");

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

$role = $_SESSION['userRole'];

// Check if the user's role for access permission
if ($role !== 'admin' && $role !== 'supervisor') {
    session_destroy();
    $error_message = "Access Denied.";
    header("Location: login.php?error=" . urlencode($error_message));
    exit();
}

/* ================================================================================== */

// Retrieve the module_id from the URL query parameter
if (isset($_GET['module_id'])) {
    $module_id = $_GET['module_id'];

    // Query the modules table with the moduleID
    $sql = "SELECT module_id, module_name, module_description, module_image, module_video FROM modules WHERE module_id = '$module_id'";
    $result = $conn->query($sql);

    // Display the module data
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $moduleName = $row['module_name'];
        $moduleDescription = $row['module_description'];
        $moduleImage = $row['module_image'];
        $moduleVideo = $row['module_video'];
    } else {
        $moduleName = '';
        $moduleDescription = '';
        $moduleImage = '';
        $moduleVideo = '';
    }
} else {
    echo "No module id";
}

/* ================================================================================== */

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the updated values from the form
    $updatedModuleName = mysqli_real_escape_string($conn, $_POST['moduleName']);
    $updatedModuleDescription = mysqli_real_escape_string($conn, $_POST['moduleDescription']);

    // Retrieve file information
    $moduleImage = $_FILES["moduleImage"];
    $moduleVideo = $_FILES["moduleVideo"];

    // Check if a new image file is uploaded
    if ($moduleImage["size"] > 0) {
        // New image file uploaded
        $imagePath = "./Images/" . basename($moduleImage["name"]);
        move_uploaded_file($moduleImage["tmp_name"], $imagePath);
    } else {
        // Use the existing image path
        $imagePath = $_POST['existingModuleImage'];
    }

    // Check if a new video file is uploaded
    if ($moduleVideo["size"] > 0) {
        // New video file uploaded
        $videoPath = "./Videos/" . basename($moduleVideo["name"]);
        move_uploaded_file($moduleVideo["tmp_name"], $videoPath);
    } else {
        // Use the existing video path
        $videoPath = $_POST['existingModuleVideo'];
    }

    // Update the module data in the database
    $updateSql = "UPDATE modules SET module_name = '$updatedModuleName', module_description = '$updatedModuleDescription', module_image = '$imagePath', module_video = '$videoPath' WHERE module_id = '$module_id'";
    if ($conn->query($updateSql) === TRUE) {
        // echo "Module updated successfully";
        session_start();
        $_SESSION["moduleUpdated"] = true;
    } else {
        echo "Error updating module: " . $conn->error;
    }
}

// Close the database connection
$conn->close();
?>

<!-- ==================================================================================  -->

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
    <title>Edit Module</title>
</head>

<body class="d-flex flex-column min-vh-100">

    <?php require_once("nav-bar.php"); ?>

    <!-- ==================================================================================  -->

    <div class="wrapper d-flex flex-column justify-content-center align-items-center mb-5" style="min-height: calc(100vh - 170px)">
        <div class="container">
            <div class="d-flex justify-content-start mt-5">
                <a class="btn btn-secondary btn-sm rounded-5 back-btn" href="javascript:history.go(-1)"> <i class="fa-solid fa-arrow-left"></i> Back </a>
            </div>
            <div class="text-center">
                <h1 class="mb-3"><strong>Edit Module</strong></h1>
            </div>

            <?php
            if (isset($_SESSION["moduleUpdated"]) && $_SESSION["moduleUpdated"]) {
                echo '<div class="container text-center">
                        <div class="d-flex justify-content-center mt-5 mb-5">
                            <div class="alert alert-success" role="alert" style="width: 75%">
                                Module Updated successfully!
                            </div>
                        </div>
                        <a href="modules.php"><button type="button" class="btn signature-btn">Back to Modules</button></a>
                    </div>';
                // Unset the session variable to remove the success message on page refresh
                unset($_SESSION["moduleUpdated"]);
            } else {
                // Display the form
            ?>
                <form method="post" enctype="multipart/form-data" id="moduleForm" class="p-5 text-white rounded-3 shadow-lg bg-gradient signature-bg-color bg-opacity-50" novalidate>
                    <div class="mb-3">
                        <label for="moduleName" class="form-label" style="font-weight: bold;">Module Name</label>
                        <input type="text" class="form-control" id="moduleName" name="moduleName" value="<?php echo $moduleName; ?>" required>
                        <div class="invalid-feedback text-info">
                            Please provide a module name.
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="moduleDescription" style="font-weight: bold;">Module Description</label>
                        <textarea class="form-control" rows="3" name="moduleDescription" required><?php echo $moduleDescription; ?></textarea>
                        <div class="invalid-feedback text-info">
                            Please provide a module description.
                        </div>
                    </div>

                    <!-- Module Image input field -->
                    <div class="mb-3 mt-3">
                        <label for="moduleImage" class="form-label" style="font-weight: bold;">Module Image / Icon</label>
                        <input class="form-control" type="file" name="moduleImage">
                        <?php if (!empty($moduleImage)) : ?>
                            <p class="text-white">Current Image: <a href="<?php echo $moduleImage; ?>" style="color:#62c0fb" target="_blank"><?php echo $moduleImage; ?></a></p>
                            <input type="hidden" name="existingModuleImage" value="<?php echo $moduleImage; ?>">
                        <?php endif; ?>
                        <div class="invalid-feedback text-info">
                            Please provide a module image.
                        </div>
                    </div>

                    <!-- Module Video input field -->
                    <div class="mb-3 mt-3">
                        <label for="moduleVideo" class="form-label" style="font-weight: bold;">Module Video</label>
                        <input class="form-control" type="file" name="moduleVideo">
                        <?php if (!empty($moduleVideo)) : ?>
                            <p class="text-white">Current Video: <a href="<?php echo $moduleVideo; ?>" style="color:#62c0fb" target="_blank"><?php echo $moduleVideo; ?></a></p>
                            <input type="hidden" name="existingModuleVideo" value="<?php echo $moduleVideo; ?>">
                        <?php endif; ?>
                        <div class="invalid-feedback text-info">
                            Please provide a module video.
                        </div>
                    </div>

                    <!-- Confirmation popup message -->
                    <!-- Button HTML (to Trigger Modal) -->
                    <div class="text-center">
                        <a href="#myModal" role="button" class="btn btn-dark" id="editModuleBtn">Update Module</a>
                    </div>

                    <!-- Modal HTML -->
                    <div id="myModal" class="modal fade text-black" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Confirmation</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Do you want to update the module?</p>
                                    <p class="text-secondary"><small>If you don't update, your changes will be lost.</small></p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn signature-btn" id="moduleForm">Update Module</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            <?php } ?>
        </div>
    </div>

    <!-- ================================================================================== -->

    <?php require_once("footer_logout.php") ?>

    <!-- ================================================================================== -->

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add a click event listener to the "createModuleBtn" element
        document.getElementById("editModuleBtn").addEventListener("click", function(event) {
            // Check if all required fields are filled
            var form = document.getElementById("moduleForm");
            if (!form.checkValidity()) {
                // Prevent form submission if any required fields are empty or invalid
                event.preventDefault();
                event.stopPropagation();
                // Add the "was-validated" class to the form to show validation feedback
                form.classList.add("was-validated");
                return;
            }
            // If all fields are filled and valid, show the modal with the id "myModal"
            $("#myModal").modal("show");
        });
    </script>
</body>

</html>