<?php
// Start a session to manage user login state
session_start();

// Include the database connection file
require_once("db_connect.php");

// Checking the inactivity 
require_once("inactivity_check.php");

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

// Logout script
if (!isset($_GET['logout']) && $_GET['logout'] === 'true') {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    // echo "Connected"
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Retrieve form data
    $moduleName = $_POST["moduleName"];
    $moduleDescription = $_POST["moduleDescription"];

    // Check if the module name already exists
    $checkSql = "SELECT * FROM modules WHERE module_name = ?";
    $checkStmt =  $conn->prepare($checkSql);
    $checkStmt->bind_param("s", $moduleName);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows > 0) {
        // Module name already exists, set an error session variable
        echo '<script>alert("Module already exists.");</script>';
    } else {

        // Retrieve file information
        $moduleImage = $_FILES["moduleImage"];
        $moduleVideo = $_FILES["moduleVideo"];

        // File paths
        $imagePath = "./Images/" . basename($moduleImage["name"]);
        $videoPath = "./Videos/" . basename($moduleVideo["name"]);

        // Move uploaded files to the specified directories
        move_uploaded_file($moduleImage["tmp_name"], $imagePath);
        move_uploaded_file($moduleVideo["tmp_name"], $videoPath);

        // Prepare the SQL statement
        $sql = "INSERT INTO modules (module_name, module_description, module_image, module_video) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        // Bind the parameters
        $stmt->bind_param("ssss", $moduleName, $moduleDescription, $imagePath, $videoPath);

        // Execute the statement
        if ($stmt->execute()) {
            // echo "Module created successfully";

            // Get the generated module ID
            $moduleId = $stmt->insert_id;

            // Store the module ID in the session
            $_SESSION["moduleId"] = $moduleId;
            $_SESSION["moduleName"] = $moduleName;
            $_SESSION["moduleCreated"] = true;
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!-- ================================================================================== -->

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale-1.0">
    <meta http-equiv="X-UA-Compatible" content="ie-edge">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="shortcut icon" type="image/x-icon" href="Images/FE-logo-icon.ico" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>Create Module</title>
</head>

<!-- ================================================================================== -->

<body class="d-flex flex-column min-vh-100">

    <?php require_once("nav-bar.php"); ?>

    <!-- ================================================================================== -->

    <div class="container">
        <div class="d-flex justify-content-start mt-5">
            <a class="btn btn-secondary btn-sm rounded-5 back-btn" href="javascript:history.go(-1)"> <i class="fa-solid fa-arrow-left"></i> Back </a>
        </div>

    </div>

    <div class="container wrapper d-flex flex-column align-items-center mb-5">
        <h1 class="text-center mb-3"><strong> Create Module </strong></h1>

        <?php
        if (isset($_SESSION["moduleCreated"]) && $_SESSION["moduleCreated"]) {
            // Show the success message and continue button
            echo '<div class="container text-center">
                    <div class="d-flex justify-content-center mt-5 mb-5">
                        <div class="alert alert-success" role="alert" style="width: 75%">
                            Module created successfully!
                        </div>
                    </div>
                    <a href="modules.php"><button type="button" class="btn btn-secondary m-1">Back to Modules</button></a>
                    <a class="btn signature-btn text-center m-1" href="create-question.php?moduleId=' . $moduleId . '&moduleName=' . $moduleName . '" role="button">Continue Create Questions</a>
                </div>';
            // Unset the session variable to remove the success message on page refresh
            unset($_SESSION["moduleCreated"]);
        } else {
            // Display the form
        ?>
            <div class="container d-flex justify-content-center align-items-center">
                <div class="col">
                    <form method="POST" enctype="multipart/form-data" id="moduleForm" class="p-5 text-white rounded-3 bg-gradient signature-bg-color shadow-lg" novalidate>
                        <div class="mb-3">
                            <label for="moduleName" class="form-label" style="font-weight: bold;">Module Name</label>
                            <input type="text" class="form-control" id="moduleName" name="moduleName" required>
                            <div class="invalid-feedback text-info">
                                Please provide a module name.
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="moduleDescription" style="font-weight: bold;">Module Description</label>
                            <textarea class="form-control" rows="3" name="moduleDescription" required></textarea>
                            <div class="invalid-feedback text-info">
                                Please provide a module description.
                            </div>
                        </div>
                        <div class="mb-3 mt-3">
                            <label for="moduleImage" class="form-label" style="font-weight: bold;">Module Image / Icon</label>
                            <input class="form-control" type="file" name="moduleImage" required>
                            <div class="invalid-feedback text-info">
                                Please provide a module image.
                            </div>
                        </div>
                        <div class="mb-3 mt-3">
                            <label for="moduleVideo" class="form-label" style="font-weight: bold;">Module Video</label>
                            <input class="form-control" type="file" name="moduleVideo" required>
                            <div class="invalid-feedback text-info">
                                Please provide a module video.
                            </div>
                        </div>

                        <!-- Button HTML (to Trigger Modal) -->
                        <div class="d-flex justify-content-center">
                            <a href="#myModal" role="button" class="btn btn-dark m-1" id="createModuleBtn">Create Module</a>
                        </div>

                        <!-- ================================================================================== -->

                        <!-- Modal HTML -->
                        <div id="myModal" class="modal fade text-black" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Confirmation</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p>Do you want to create the module?</p>
                                        <p class="text-secondary"><small>If you don't create, your changes will be lost.</small></p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn signature-btn">Create Module</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php
        }
        ?>
    </div>

    <!-- ================================================================================== -->

    <?php require_once("footer_logout.php") ?>

    <!-- ================================================================================== -->

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add a click event listener to the "createModuleBtn" element
        document.getElementById("createModuleBtn").addEventListener("click", function(event) {
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