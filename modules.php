<?php
session_start();
require_once('db_connect.php');
require_once("inactivity_check.php");

$role = $_SESSION['userRole'];

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    session_destroy();
    header("Location: login.php");
    exit();
}

if (isset($_GET['module_id']) && isset($_GET['archive'])) {
    $moduleId = $_GET['module_id'];
    date_default_timezone_set('Australia/Sydney');
    $archivedDate = date('Y-m-d H:i:s');

    $updateModulesSql = "UPDATE modules SET is_archived = true, archived_date = '$archivedDate' WHERE module_id = $moduleId";
    $updateModulesResult = mysqli_query($conn, $updateModulesSql);

    $updateQuestionsSql = "UPDATE module_questions SET is_archived = true WHERE module_id = $moduleId";
    $updateQuestionsResult = mysqli_query($conn, $updateQuestionsSql);

    if ($updateModulesResult && $updateQuestionsResult) {
        header("Location: modules.php");
        exit();
    } else {
        echo "Error archiving the module.";
    }
}

$username = $_SESSION['username'] ?? '';
$currentDate = date('F j, Y');

$emp_id_query = "SELECT employee_id, role, full_name FROM users WHERE username = '$username'";
$result = $conn->query($emp_id_query);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $employee_id = $row['employee_id'];
    $role = $row['role'];
    $full_name = $row['full_name'];
    $_SESSION['employeeId'] = $employee_id;
    $_SESSION['userRole'] = $role;
} else {
    $employee_id = 'N/A';
}
$result->free();
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <!-- Linking external CSSS stylesheet to apply styles to the HTML document -->
    <link rel="stylesheet" type="text/css" href="style.css">

    <!-- Title of the page -->
    <title> Modules </title>
</head>

<style>
    @media (max-width: 576px) {

        label,
        .createBtn,
        .searchBtn {
            font-size: 12px;
        }

        .searchBtn {
            padding-top: 10px;
            padding-bottom: 10px;
        }

        input {
            height: 20px;
        }

        .no-wrap {
            white-space: nowrap;
        }

        .form-select {
            width: 50px !important;
            padding: 5px;
            font-size: 12px;
        }

        .createBtn {
            margin-top: 20px !important;
        }
    }

    @media (max-width: 1200px) {
        .createBtn {
            font-size: 12px;
        }
    }
</style>

<!-- ================================================================================== -->

<body class="bg-gradient d-flex flex-column min-vh-100 signature-bg-color">

    <?php require_once("nav-bar.php"); ?>

    <!-- ================================================================================== -->

    <div class="container">
        <div class="d-flex justify-content-start mt-5">
            <a class="btn btn-secondary btn-sm rounded-5 back-btn" href="javascript:history.go(-1)"> <i class="fa-solid fa-arrow-left"></i> Back </a>
        </div>
    </div>

    <?php
    $availableRecordsPerPage = array(5, 15, 20);
    $recordsPerPage = isset($_GET['recordsPerPage']) && in_array($_GET['recordsPerPage'], $availableRecordsPerPage) ? intval($_GET['recordsPerPage']) : 5;
    $pageNumber = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
    $offset = ($pageNumber - 1) * $recordsPerPage;

    $searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

    $searchSql = "SELECT module_id, module_name, module_description, module_image FROM modules WHERE is_archived = false AND module_name LIKE '%$searchTerm%' LIMIT $offset, $recordsPerPage";
    $modulesQuery = "
        SELECT 
            m.module_id, 
            m.module_name, 
            m.module_description, 
            m.module_image
        FROM modules m
        LEFT JOIN module_allocation ma ON m.module_id = ma.module_id AND ma.employee_id = '$employee_id'
        WHERE m.is_archived = '0' AND ma.module_id IS NOT NULL AND m.module_name LIKE '%$searchTerm%'
        GROUP BY m.module_id, m.module_name, m.module_description, m.module_image
        ORDER BY m.module_id, m.module_name ASC
        LIMIT $offset, $recordsPerPage;
        ";

    $searchResult = mysqli_query($conn, $searchSql);
    $moduleResult = mysqli_query($conn, $modulesQuery);

    // Total records queries
    if ($role === 'admin' || $role === 'supervisor') {
        // For admin role
        $totalRecordsQuery = "SELECT COUNT(*) as total FROM modules WHERE is_archived = false AND module_name LIKE '%$searchTerm%'";
    } else {
        // For non-admin roles
        $totalRecordsQuery = "
            SELECT COUNT(DISTINCT m.module_id) as total
            FROM modules m
            LEFT JOIN module_allocation ma ON m.module_id = ma.module_id AND ma.employee_id = '$employee_id'
            WHERE m.is_archived = '0' AND ma.module_id IS NOT NULL AND m.module_name LIKE '%$searchTerm%'
        ";
    }

    $totalRecordsResult = mysqli_query($conn, $totalRecordsQuery);
    $totalRecords = mysqli_fetch_assoc($totalRecordsResult)['total'];

    // Recalculate $startPage and $endPage
    $startPage = max(1, $pageNumber - 1);
    $endPage = min(ceil($totalRecords / $recordsPerPage), $pageNumber + 1);

    ?>

    <div class="container mb-3">
        <form class="row form-inline mt-4 justify-content-center align-items-center" method="GET" action="modules.php">
            <?php if ($role === 'admin' || $role === 'supervisor') { ?>
                <div class="col-md-10">
                <?php } else { ?>
                    <div class="col-md-8 text-center">
                    <?php } ?>
                    <div class="d-flex align-items-center">
                        <input class="form-control mr-2 mr-sm-2 searchBtn" type="search" name="search" placeholder="Search Module" aria-label="Search" style="height: 38px;">
                        <button class="btn btn-outline-light mx-2 my-2 my-sm-0 searchBtn" type="submit">Search</button>

                        <!-- Show Entries -->
                        <div class="d-flex align-items-center justify-content-center" style="margin-left: 40px;">
                            <label class="my-auto me-2 text-light"><strong>Show</strong></label>
                            <select id="recordsPerPage" name="recordsPerPage" class="form-select me-2" style="width: 70px" onchange="onRecordsPerPageChange()">
                                <option value="5" <?php echo $recordsPerPage == 5 ? 'selected' : ''; ?>>5</option>
                                <option value="15" <?php echo $recordsPerPage == 15 ? 'selected' : ''; ?>>15</option>
                                <option value="20" <?php echo $recordsPerPage == 20 ? 'selected' : ''; ?>>20</option>
                            </select>
                            <label class="text-light"><strong>entries</strong></label>
                        </div>
                    </div>

                    </div>
                    <?php if ($role === 'admin' ||  $role === 'supervisor') { ?>
                        <div class="col-md-2 text-md-end d-flex justify-content-center">
                            <a href="create-module.php" class="btn mt-md-0 mt-2 text-white bg-dark CTA-btn createBtn">+ Create New Module</a>
                        </div>
                    <?php }
                    ?>
                </div>

                <!-- ================================================================================== -->

                <?php

                // ====================================== USER ====================================== 

                if ($role === 'user') {
                    if (mysqli_num_rows($moduleResult) > 0) {
                        while ($row = mysqli_fetch_assoc($moduleResult)) {
                            $moduleId = $row['module_id'];
                            $moduleName = $row['module_name'];
                            $moduleDescription = $row['module_description'];
                            $module_image = $row['module_image'];

                            $maxCharacters = 150;
                            $charactersArray = str_split($moduleDescription);
                            $limitedDescription = implode('', array_slice($charactersArray, 0, $maxCharacters));
                ?>
                            <div class="container mb-2 mt-2">
                                <div class="card shadow-lg">
                                    <div class="row g-0 p-3 ">
                                        <div class="col-12 col-md-2 align-self-center text-center">
                                            <img src="<?php echo $module_image; ?>" alt="<?php echo $moduleName; ?>" class="img-fluid" style="max-height: 150px; max-width: 100%;">
                                        </div>
                                        <div class="col-md-6 d-flex align-items-center">
                                            <div class="card-body">
                                                <h3><?php echo $moduleName; ?></h3>
                                                <p style="text-align: justify;" class="text-wrap">
                                                    <?php
                                                    if (count($charactersArray) > $maxCharacters) {
                                                        echo $limitedDescription . ' ...';
                                                    } else {
                                                        echo $limitedDescription;
                                                    }
                                                    ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-md-4 d-flex align-items-center justify-content-center mx-auto m-3">
                                            <div class="text-center">
                                                <button type="button" class="btn m-1 signature-btn shadow" onclick="window.location.href='module-video.php?module_id=<?php echo $moduleId; ?>';" style="text-decoration: none;">Start Module</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php
                        }
                    } else {
                        echo "<div class='container'>";
                        echo "<div class='d-flex justify-content-center align-items-center text-white'>";
                        echo "<h2> No modules found. </h2>";
                        echo "</div>";
                        echo "</div>";
                    }

                    // ====================================== ADMIN ====================================== 

                } elseif ($role === "admin" || "supervisor") {
                    if (mysqli_num_rows($searchResult) > 0) {
                        while ($row = mysqli_fetch_assoc($searchResult)) {
                            $moduleId = $row['module_id'];
                            $moduleName = $row['module_name'];
                            $moduleDescription = $row['module_description'];
                            $module_image = $row['module_image'];

                            $maxCharacters = 150;
                            $charactersArray = str_split($moduleDescription);
                            $limitedDescription = implode('', array_slice($charactersArray, 0, $maxCharacters));
                        ?>
                            <div class="container mb-2 mt-2">
                                <div class="card shadow-lg">
                                    <div class="row g-0 p-3 ">
                                        <div class="col-12 col-md-2 align-self-center text-center">
                                            <img src="<?php echo $module_image; ?>" alt="<?php echo $moduleName; ?>" class="img-fluid" style="max-height: 150px; max-width: 100%;">
                                        </div>
                                        <div class="col-md-6 d-flex align-items-center">
                                            <div class="card-body">
                                                <h3><?php echo $moduleName; ?></h3>
                                                <p style="text-align: justify;" class="text-wrap">
                                                    <?php
                                                    if (count($charactersArray) > $maxCharacters) {
                                                        echo $limitedDescription . ' ...';
                                                    } else {
                                                        echo $limitedDescription;
                                                    }
                                                    ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-md-4 d-flex align-items-center justify-content-center mx-auto m-3">
                                            <button type="button" class="btn signature-btn m-1 shadow" onclick="window.location.href='module-video.php?module_id=<?php echo $moduleId; ?>';" style="text-decoration: none;">Start</button>
                                            <div class="dropdown">
                                                <button class="btn btn-dark dropdown-toggle m-1 shadow" type="button" id="editDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                                    Edit
                                                </button>
                                                <ul class="dropdown-menu" aria-labelledby="editDropdown">
                                                    <li><a class="dropdown-item" href="edit-module.php?module_id=<?php echo $moduleId; ?>">Edit Module</a></li>
                                                    <li><a class="dropdown-item" href="edit-questions.php?module_id=<?php echo $moduleId; ?>">Edit MCQ Questions</a></li>
                                                    <li><a class="dropdown-item" href="all-written-questions-list.php?module_id=<?php echo $moduleId; ?>">Edit Short Answer Questions</a></li>
                                                </ul>
                                            </div>

                                            <?php if ($role === 'admin') { ?>
                                                <!-- Archive Module Button -->
                                                <button type="button" class="btn text-white btn-info m-1 shadow" onclick="archiveModule(<?php echo $row['module_id']; ?>);">Archive</button>
                                            <?php } ?>

                                        </div>
                                    </div>
                                </div>
                            </div>
                <?php
                        }
                    } else {
                        echo "<div class='container'>";
                        echo "<div class='d-flex justify-content-center align-items-center text-white'>";
                        echo "<h2> No modules found. </h2>";
                        echo "</div>";
                        echo "</div>";
                    }
                }

                // Close the database connection
                mysqli_close($conn);

                ?>

                <!-- Pagination controls -->
                <div class="container">
                    <div class="d-flex justify-content-center align-items-center mt-5">
                        <nav aria-label="Page navigation example">
                            <ul class="pagination">

                                <?php if ($pageNumber > 1) : ?>
                                    <li class="page-item">
                                        <a class="page-link signature-color" href="?search=<?= urlencode($searchTerm) ?>&recordsPerPage=<?= $recordsPerPage ?>&page=<?= $pageNumber - 1 ?>"><i class="fas fa-angle-double-left"></i></a>
                                    </li>
                                <?php else : ?>
                                    <li class="page-item disabled">
                                        <a class="page-link" href="#" tabindex="-1"><i class="fas fa-angle-double-left"></i></a>
                                    </li>
                                <?php endif; ?>

                                <?php
                                for ($i = $startPage; $i <= $endPage; $i++) {
                                    echo '<li class="page-item' . ($i === $pageNumber ? ' active' : '') . '">';
                                    echo '<a class="page-link signature-color' . ($i === $pageNumber ? ' signature-bg-color' : '') . '" href="?search=' . urlencode($searchTerm) . '&recordsPerPage=' . $recordsPerPage . '&page=' . $i . '">' . $i . '</a>';
                                    echo '</li>';
                                }
                                ?>

                                <?php if ($pageNumber < ceil($totalRecords / $recordsPerPage)) : ?>
                                    <li class="page-item">
                                        <a class="page-link signature-color" href="?search=<?= urlencode($searchTerm) ?>&recordsPerPage=<?= $recordsPerPage ?>&page=<?= $pageNumber + 1 ?>"><i class="fas fa-angle-double-right"></i></a>
                                    </li>
                                <?php else : ?>
                                    <li class="page-item disabled">
                                        <a class="page-link" href="#" tabindex="-1"><i class="fas fa-angle-double-right"></i></a>
                                    </li>
                                <?php endif; ?>

                            </ul>
                        </nav>
                    </div>
                </div>


                <!-- Modal HTML -->
                <div id="archiveConfirmationModal" class="modal fade text-black" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Confirmation</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p>Do you want to archive the module?</p>
                                <p class="text-secondary"><small>If you archive the module, it will be marked as archived and won't be visible to users.</small></p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn signature-btn" id="archiveButton">Archive</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-5"></div>

                <!-- ================================================================================== -->

                <?php require_once("footer_logout.php") ?>
                <!-- ================================================================================== -->

                <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

                <script>
                    // Restore scroll position after page reload
                    window.addEventListener('load', function() {
                        const scrollPosition = localStorage.getItem('scrollPosition');
                        if (scrollPosition) {
                            window.scrollTo(0, scrollPosition);
                            localStorage.removeItem('scrollPosition');
                        }
                    });

                    // Save scroll position before page unload
                    window.addEventListener('beforeunload', function() {
                        const scrollPosition = window.scrollY;
                        localStorage.setItem('scrollPosition', scrollPosition);
                    });
                </script>

                <script>
                    // JavaScript function to handle the "Archive" button click from the modal
                    function archiveModule(moduleId) {
                        // Set the archive URL with the module_id parameter
                        const archiveUrl = "modules.php?module_id=" + moduleId + "&archive=true";

                        // Set the "Archive" button's click event to navigate to the archive URL
                        document.getElementById("archiveButton").onclick = function() {
                            window.location.href = archiveUrl;
                        };

                        // Show the confirmation modal
                        const archiveConfirmationModal = new bootstrap.Modal(document.getElementById('archiveConfirmationModal'));
                        archiveConfirmationModal.show();
                    }
                </script>

                <script>
                    // Function to handle the onchange event of the recordsPerPage drop-down
                    function onRecordsPerPageChange() {
                        // Get the selected value of recordsPerPage
                        var selectedValue = document.getElementById("recordsPerPage").value;

                        // Set the "page" to 1
                        var currentUrl = new URL(window.location.href);
                        currentUrl.searchParams.set("page", 1);

                        // Update the URL to include the selected recordsPerPage value
                        currentUrl.searchParams.set("recordsPerPage", selectedValue);

                        // Redirect to the updated URL
                        window.location.href = currentUrl.toString();
                    }

                    // Add an event listener to the recordsPerPage drop-down
                    document.getElementById("recordsPerPage").addEventListener("change", onRecordsPerPageChange);
                </script>
</body>

</html>