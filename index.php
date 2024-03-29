<?php

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

/* ================================================================================== */

// Set the default time zone to Sydney
date_default_timezone_set('Australia/Sydney');

// Retrieve the username from the session
$username = $_SESSION['username'] ?? '';
// Assign and set format of the date
$currentDate = date('F j, Y');

// (Retrieve the employee ID and role from the users table)
$emp_id_query = "
      SELECT u.employee_id, u.role, u.full_name, d.department_name
      FROM users u
      LEFT JOIN department d ON u.department_id = d.department_id
      WHERE u.username = '$username';
  ";
// Execute the SQL query and store the result in the $result variable
$result = $conn->query($emp_id_query);

// Check if the query result is not empty and contains one or more rows of data
if ($result && $result->num_rows > 0) {
  // Fetch the next row of data from the result set and store it in the $row variable.
  $row = $result->fetch_assoc();

  // Assigning employee ID and role from the row data
  $employee_id = $row['employee_id'];
  $role = $row['role'];
  $full_name = $row['full_name'];
  $department_name = $row['department_name'];

  // Set the employee_id value in session
  $_SESSION['employeeId'] = $employee_id;

  // Set the role value in session
  $_SESSION['userRole'] = $role;
} else {
  // Set a default value if the employee_id is not found
  $employee_id = 'N/A';
}
// Free up the memory used by the database query result
$result->free();

/* ================================================================================== */

// Retrieve modules from the 'modules' table that is not archived
$modulesQuery = "
  SELECT 
    m.module_id, 
    m.module_name, 
    m.module_description, 
    m.module_image, 
    IFNULL(r.max_score, 0) AS score,
    IFNULL(MAX(wr.is_correct), 0) AS is_correct
  FROM modules m
  LEFT JOIN (
    SELECT module_id, MAX(score) AS max_score
    FROM results
    WHERE employee_id = '$employee_id'
    GROUP BY module_id
  ) r ON m.module_id = r.module_id
  LEFT JOIN module_allocation ma ON m.module_id = ma.module_id AND ma.employee_id = '$employee_id'
  LEFT JOIN written_results wr ON m.module_id = wr.module_id AND wr.employee_id = '$employee_id'
  WHERE m.is_archived = '0' AND ma.module_id IS NOT NULL
  GROUP BY m.module_id, m.module_name, m.module_description, m.module_image
  ORDER BY m.module_id;
";


// Execute the SQL query and store the result set in $moduleResult
$modulesResult = $conn->query($modulesQuery);

function getCountForModule($conn, $moduleId, $employeeId)
{
  // Check if there is any data in written_results for the current module
  $checkDataQuery = "SELECT COUNT(*) AS data_count FROM written_results WHERE module_id = $moduleId AND employee_id = $employeeId AND is_correct != NULL OR is_correct = 0";
  $checkDataResult = $conn->query($checkDataQuery);

  if ($checkDataResult) {
    $checkDataRow = $checkDataResult->fetch_assoc();
    $dataCount = $checkDataRow['data_count'];

    // If there is no data, return NULL
    if ($dataCount == 0) {
      return null;
    } else {
      // Query to count the number of '0' values in 'is_correct' for the current module
      $countQuery = "SELECT COUNT(*) AS zero_count FROM written_results WHERE module_id = $moduleId AND is_correct = 0 AND employee_id = $employeeId";
      $countResult = $conn->query($countQuery);

      if ($countResult) {
        $countRow = $countResult->fetch_assoc();
        $zeroCount = $countRow['zero_count'];

        // Free up the memory used by the count result set
        $countResult->free();

        // Return the count value (even if it's 0)
        return $zeroCount;
      } else {
        // Display an error message if the count query fails
        echo "Error executing the count query: " . $conn->error;
      }
    }

    // Free up the memory used by the check data result set
    $checkDataResult->free();
  } else {
    // Display an error message if the check data query fails
    echo "Error executing the check data query: " . $conn->error;
  }

  return null;
}

function getUnmarkedQuestionCount($conn, $moduleId, $employeeId)
{
  // SQL query to count unmarked questions
  $sql = "SELECT COUNT(*) as unmarked_count
          FROM written_answers wa
          LEFT JOIN written_results wr ON wa.written_answer_id = wr.written_answer_id
          WHERE (wr.written_answer_id IS NULL OR is_correct IS NULL)
                AND wa.module_id = $moduleId
                AND wa.employee_id = $employeeId";

  // Execute the query
  $result = $conn->query($sql);

  // Check if the query was successful
  if ($result === false) {
    die("Error executing query: " . $conn->error);
  }

  // Fetch the result
  $row = $result->fetch_assoc();

  // Return the count of unmarked questions
  return $row['unmarked_count'];
}


function getCountForEssayModule($conn, $moduleId, $employeeId)
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


/* ================================================================================== */

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
/* ================================================================================== */

// Retrieve modules from the 'modules' table that have NOT been attempted by the user 
// Query to retrieve NOT attempted modules for a specific employee
$unattemptedModulesQuery = "
    SELECT ma.module_id, m.module_name, m.module_description, m.module_image
    FROM module_allocation ma
    JOIN modules m ON ma.module_id = m.module_id
    WHERE ma.employee_id = '$employee_id'
      AND ma.module_id NOT IN (
        SELECT r.module_id FROM results r WHERE r.employee_id = '$employee_id'
      ) 
      AND m.is_archived = '0'
      AND ma.module_id NOT IN ( 
        SELECT wa.module_id FROM written_answers wa WHERE wa.employee_id = '$employee_id' AND ma.module_id = wa.module_id
      )
    ORDER BY ma.module_id
  ";
$unattemptedModulesResult = $conn->query($unattemptedModulesQuery);

?>

<!-- ==================================================================================  -->

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
  <!-- Internal CSS for the HTML -->
  <style>
    .scroll-icon {
      display: none;
    }

    @media (max-width: 800px) {

      /* Change the max-width to the desired breakpoint for small screens */
      .scroll-icon {
        display: inline;
      }

      /* Show the icon only on small screens */
      #menu-icon {
        display: inline;
      }
    }

    /* Custom CSS to make Offcanvas width smaller */
    #offcanvasRight {
      max-width: 40vh;
    }

    .accordion-button::after {
      /* Set filter values for white color */
      filter: invert(100%) sepia(0%) saturate(0%) hue-rotate(0deg) brightness(100%) contrast(100%);
    }

    .fixed-center-right {
      position: fixed;
      top: 50%;
      right: -0.5%;
      transform: translateY(-50%);
    }

    #offCanvasBtn,
    .status-badge {
      transition: all 0.1s ease-in-out;
    }

    #offCanvasBtn:hover {
      transform: translateX(-8%);
    }

    @media (max-width: 576px) {
      .accordion-body p {
        font-size: 10px
      }
    }

    .tooltip-inner {
      white-space: pre-wrap;
    }

    .status-badge:hover {
      transform: translateY(-8%);
    }
  </style>

  <!-- Title of the page -->
  <title> FUJI Training Module </title>
</head>

<!-- ==================================================================================  -->

<body class="d-flex flex-column min-vh-100">

  <?php require_once("nav-bar.php"); ?>

  <!-- ==================================================================================  -->

  <div class="text-white mt-5 p-3 bg-gradient signature-bg-color shadow-lg">
    <div class="row justify-content-center">
      <div class="col-12 col-md-6 text-center">
        <p class="fs-6"><?php echo $currentDate ?></p>
        <h1>Welcome, <?php echo $full_name; ?></h1>
        <p class="fs-6 mt-3"><strong>Employee ID: <?php echo $employee_id; ?></strong></p>
        <p class="fs-6 mt-3"><strong>Department: <?php echo $department_name; ?></strong></p>
        <div class="d-flex justify-content-center align-items-center">
          <a href="modules.php" class="btn mt-3 text-white bg-dark CTA-btn">Go to Modules</a>
        </div>
      </div>
    </div>
  </div>

  <!-- ==================================================================================  -->
  <div>
    <?php
    // Checking if there are any unattempted modules in the result set.
    if ($unattemptedModulesResult && $unattemptedModulesResult->num_rows > 0) {
      // Count the number of unattempted modules
      $numUnattemptedModules = $unattemptedModulesResult->num_rows;

      // echo 'number of modules: ' .  $numUnattemptedModules;
    ?>
      <div class="container mt-5">
        <div class="row mb-3">
          <div class="col-12">
            <hr>
            <div class="d-flex justify-content-between align-items-center">
              <h3 style="font-size: 2.5vh; margin: 0;" class="fw-bold"> Not Attempted Modules</h3>
              <?php if ($numUnattemptedModules > 3) : ?>
                <i class="fa-solid fa-arrows-left-right-to-line fa-fade fa-xl " id="menu-icon"></i>
              <?php elseif ($numUnattemptedModules != 1) : ?>
                <i class="fa-solid fa-arrows-left-right-to-line fa-fade fa-xl d-md-none scroll-icon" id="menu-icon"></i>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- ==================================================================================  -->

      <div class="container">
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4" style="flex-wrap: nowrap; overflow-x: auto;">
          <?php
          while ($unattemptedModuleRow = $unattemptedModulesResult->fetch_assoc()) {
            $moduleId = $unattemptedModuleRow['module_id'];
            $moduleName = $unattemptedModuleRow['module_name'];
            $moduleDescription = $unattemptedModuleRow['module_description'];
            $moduleImage = $unattemptedModuleRow['module_image'];

            // Limit the description to a maximum of 100 characters
            $maxCharacters = 100;
            $charactersArray = str_split($moduleDescription);
            $limitedDescription = implode('', array_slice($charactersArray, 0, $maxCharacters));
          ?>
            <div class="col">
              <a onclick="window.location.href='module-video.php?module_id=<?php echo $moduleId; ?>';" style="text-decoration: none;">
                <div class="card h-100 shadow" style="cursor: pointer">
                  <img src="<?php echo $moduleImage; ?>" class="card-img-top p-4" alt="Icon failed to load" style="max-height: 300px; object-fit: contain;">
                  <div class="card-body d-flex flex-column">
                    <h5 class="card-title mt-auto"><?php echo $moduleName; ?></h5>
                    <p class="card-text" style="text-align: justify">
                      <?php
                      // Check if the description is longer than the limited one
                      if (count($charactersArray) > $maxCharacters) {
                        echo $limitedDescription . ' ...';
                      } else {
                        echo $limitedDescription;
                      }
                      ?>
                    </p>
                    <?php
                    // Show "To-Do" badge
                    echo '<span class="position-absolute top-0 start-100 translate-middle badge badge-pill rounded-pill bg-danger text-white d-flex align-items-center">
                                <span style="font-size: 8px">To-Do</span>
                              </span>';

                    ?>
                  </div>
                </div>
              </a>
            </div>
          <?php
          }
          ?>

        </div>
      </div>
    <?php
    } else {
      // Show nothing when there is no unattempted modules
    }
    $unattemptedModulesResult->free();
    ?>

    <!-- ==================================================================================  -->

    <?php
    // Checking if there are any attempted modules in the result set.
    if ($attemptedModulesResult && $attemptedModulesResult->num_rows > 0) {
      // Count the number of attempted modules
      $numAttemptedModules = $attemptedModulesResult->num_rows;

      // echo 'number of modules: ' .  $numAttemptedModules;
    ?>
      <div class="container mt-5">
        <div class="row mb-3">
          <div class="col-12">
            <hr>
            <div class="d-flex justify-content-between align-items-center">
              <h3 style="font-size: 2.5vh; margin: 0;" class="fw-bold">Attempted Modules</h3>
              <?php if ($numAttemptedModules > 3) : ?>
                <i class="fa-solid fa-arrows-left-right-to-line fa-fade fa-xl " id="menu-icon"></i>
              <?php elseif ($numAttemptedModules != 1) : ?>
                <i class="fa-solid fa-arrows-left-right-to-line fa-fade fa-xl d-md-none scroll-icon" id="menu-icon"></i>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <div class="container">
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4" style="flex-wrap: nowrap; overflow-x: auto;">
          <?php
          // Looping through the attempted modules and fetching each row's data.
          while ($attemptedModulesRow = $attemptedModulesResult->fetch_assoc()) {
            $moduleId = $attemptedModulesRow['module_id'];
            $moduleName = $attemptedModulesRow['module_name'];
            $moduleDescription = $attemptedModulesRow['module_description'];
            $moduleImage = $attemptedModulesRow['module_image'];
            $moduleScore = $attemptedModulesRow['score'];

            // Get the count for the current module
            $countForModule = getCountForModule($conn, $moduleId, $employee_id);

            // Output the count for the current module
            // echo "Number of False answer:  $countForModule <br><br>";


            // Example usage
            $unmarkedCount = getUnmarkedQuestionCount($conn, $moduleId, $employee_id);
            // echo "Number of unmarked questions: $unmarkedCount";

            // Check if the module has already been added with a higher score
            if (isset($highestScores[$moduleId]) && $highestScores[$moduleId] >= $moduleScore) {
              continue; // Skip this module
            }

            $numAttemptedQuestion = getCountForEssayModule($conn, $moduleId, $employee_id);

            // Store the highest score for the module
            $highestScores[$moduleId] = $moduleScore;

            // Limit the description to a maximum of 100 charac
            $maxCharacters = 100;
            $charactersArray = str_split($moduleDescription);
            $limitedDescription = implode('', array_slice($charactersArray, 0, $maxCharacters));

          ?>
            <!-- Display the module -->
            <div class="col">
              <a onclick="window.location.href='module-video.php?module_id=<?php echo $moduleId; ?>';" style="text-decoration: none;">
                <div class="card h-100 shadow" style="cursor: pointer;">
                  <img src="<?php echo $moduleImage; ?>" class="card-img-top p-4" alt="Icon failed to load" style="max-height: 300px; object-fit: contain;">
                  <div class="card-body d-flex flex-column">
                    <h5 class="card-title mt-auto"><?php echo $moduleName; ?></h5>
                    <p class="card-text" style="text-align: justify">
                      <?php
                      // Check if the description is longer than the limited one
                      if (count($charactersArray) > $maxCharacters) {
                        echo $limitedDescription . ' ...';
                      } else {
                        echo $limitedDescription;
                      }
                      ?>
                    </p>
                  </div>
                  <?php
                  // Check if the module is uncompleted or has less than 100% score
                  if ($moduleScore < 100 && $countForModule === null && $unmarkedCount == 0 && $numAttemptedQuestion == 0 || $moduleScore < 100 && $countForModule == 0 && $unmarkedCount == 0  && $numAttemptedQuestion == 0 || $moduleScore < 100 && $countForModule > 0 && $unmarkedCount == 0) {
                    echo '<span class="position-absolute top-0 start-100 translate-middle badge badge-pill rounded-pill bg-danger text-white d-flex align-items-center">
                           <span style="font-size: 8px">To-Do</span>
                       </span>';
                  } else if ($moduleScore == 100 && $countForModule == 0 && $unmarkedCount == 0 && $numAttemptedQuestion > 0) {
                    echo '<span class="position-absolute top-0 start-100 translate-middle badge badge-pill rounded-pill bg-success text-white d-flex align-items-center" style="z-index: 998;">
                         <span style="font-size: 8px">Done</span>
                     </span>';
                  } else if (($moduleScore == 100 && $countForModule > 0 && $unmarkedCount == 0)  || ($moduleScore == 100 && $countForModule === null && $unmarkedCount == 0 && $numAttemptedQuestion == 0)) {
                    echo '<span class="position-absolute top-0 start-100 translate-middle badge badge-pill rounded-pill bg-info text-white d-flex align-items-center" style="z-index: 998;">
                           <span style="font-size: 8px">Short Answer!</span>
                       </span>';
                  } else if ($moduleScore == 100 && $unmarkedCount > 0 || $moduleScore < 100 && $unmarkedCount > 0) {
                    echo '<span class="position-absolute top-0 start-100 translate-middle badge badge-pill rounded-pill bg-info text-white d-flex align-items-center" style="z-index: 998;">
                         <span style="font-size: 8px">Unmarked!</span>
                     </span>';
                  } else if ($moduleScore < 100 && $countForModule == 0) {
                    echo '<span class="position-absolute top-0 start-100 translate-middle badge badge-pill rounded-pill bg-info text-white d-flex align-items-center" style="z-index: 998;">
                         <span style="font-size: 8px">MCQ!</span>
                     </span>';
                  } else if ($moduleScore == 100 && $countForModule == 0 && $unmarkedCount > 0) {
                    echo '<span class="position-absolute top-0 start-100 translate-middle badge badge-pill rounded-pill bg-danger text-white d-flex align-items-center" style="z-index: 998;">
                         <span style="font-size: 8px">Unmarked!</span>
                     </span>';
                  }
                  // echo (
                  //   "MCQ:" . $moduleScore . "<br> " . "Incorrect: " . $countForModule . "<br>Unmarked:" . $unmarkedCount . "<br>Attempted: " . $numAttemptedQuestion);
                  ?>
              </a>
              <div class="container mt-auto">
                <hr style="border: 1px solid black;" class="m-0 mb-1">
                <div class="d-flex justify-content-center mt-auto mb-1">
                  <strong>Current Status</strong>
                </div>
                <div class="d-flex justify-content-between mt-auto m-3 mb-3">
                  <?php
                  // var_dump($countForModule);
                  // var_dump($unmarkedCount);

                  if (($countForModule === 0 && $unmarkedCount === 0 && $numAttemptedQuestion > 0) ||
                    ($countForModule == 0 && $unmarkedCount == "0" && $numAttemptedQuestion > 0)
                  ) {
                    echo '<button class="badge badge-pill tooltips rounded-3 p-2 status-badge signature-bg-color bg-gradient" style="border:none; width: 120px; data-bs-toggle="tooltip" data-html="true" data-bs-placement="top" title="Completed">Short Answer</button>';
                  } else {
                    echo '<button class="badge badge-pill tooltips rounded-3 p-2 status-badge signature-bg-color bg-gradient" style="border:none; width: 120px;" data-bs-toggle="tooltip" data-html="true" data-bs-placement="top" title="' .
                      (($countForModule !== null || $unmarkedCount > 0) ?
                        ($countForModule !== null ? "False Answer: $countForModule" . "\n" : "No False Answer" . "\n") .
                        "Unmarked Questions: $unmarkedCount" :
                        "No Attempt") . '">
                            Short Answer
                        </button>';
                  }

                  ?>

                  <button class="badge badge-pill tooltips rounded-3 p-2 status-badge signature-bg-color bg-gradient" style="border:none; width: 120px;" data-bs-toggle="tooltip" data-html="true" data-bs-placement="top" title="<?php echo ($moduleScore !== null && $moduleScore !== "0" ? "Highest MCQ Score: $moduleScore" . "%" : "No Attempt") ?>">
                    MCQ
                  </button>
                </div>
                <!-- <div class="progress" style="height: 5px">
                      <div class="progress-bar signature-bg-color" role="progressbar" style="width: <?php echo $highestScores[$moduleId]; ?>%; aria-valuenow=" <?php echo $highestScores[$moduleId]; ?> aria-valuemin="0" aria-valuemax="100"></div>
                    </div> -->
              </div>
            </div>

        </div>
    <?php
          }
        } else {
          // Show nothing when there is no attempted modules
        }
        $attemptedModulesResult->free();
    ?>
      </div>
  </div>


  <!-- ==================================================================================  -->

  <?php
  if ($modulesResult && $modulesResult->num_rows > 0) {
    // Count the number of modules
    $numModulesResult = $modulesResult->num_rows;
  ?>
    <div class="container mt-5">
      <div class="row mb-3">
        <div class="col-12">
          <hr>
          <div class="d-flex justify-content-between align-items-center">
            <h3 style="font-size: 2.5vh; margin: 0;" class="fw-bold">All Modules</h3>
            <?php if ($numModulesResult > 3) : ?>
              <i class="fa-solid fa-arrows-left-right-to-line fa-fade fa-xl " id="menu-icon"></i>
            <?php elseif ($numModulesResult != 1) : ?>
              <i class="fa-solid fa-arrows-left-right-to-line fa-fade fa-xl d-md-none scroll-icon" id="menu-icon"></i>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="container">
      <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4" style="flex-wrap: nowrap; overflow-x: auto;">
        <?php
        while ($moduleRow = $modulesResult->fetch_assoc()) {
          $moduleId = $moduleRow['module_id'];
          $moduleName = $moduleRow['module_name'];
          $moduleDescription = $moduleRow['module_description'];
          $moduleImage = $moduleRow['module_image'];
          $moduleScore = $moduleRow['score'];

          // Get the count for the current module
          $countForModule = getCountForModule($conn, $moduleId, $employee_id);

          // Output the count for the current module
          // echo "Number of False answer:  $countForModule <br><br>";

          // Example usage
          $unmarkedCount = getUnmarkedQuestionCount($conn, $moduleId, $employee_id);
          // echo "Number of unmarked questions: $unmarkedCount";

          $numAttemptedQuestion = getCountForEssayModule($conn, $moduleId, $employee_id);

          // Limit the description to a maximum of 100 character
          $maxCharacters = 100;
          $charactersArray = str_split($moduleDescription);
          $limitedDescription = implode('', array_slice($charactersArray, 0, $maxCharacters));
        ?>
          <div class="col">
            <a onclick="window.location.href='module-video.php?module_id=<?php echo $moduleId; ?>';" style="text-decoration: none;">
              <div class="card h-100 shadow" style="cursor: pointer;">
                <img src="<?php echo $moduleImage; ?>" class="card-img-top p-4" alt="Icon failed to load" style="max-height: 300px; object-fit: contain;">
                <div class="card-body d-flex flex-column">
                  <h5 class="card-title mt-auto"><?php echo $moduleName; ?></h5>
                  <p class="card-text" style="text-align: justify">
                    <?php
                    // Check if the description is longer than the limited one
                    if (count($charactersArray) > $maxCharacters) {
                      echo $limitedDescription . ' ...';
                    } else {
                      echo $limitedDescription;
                    }
                    ?>
                  </p>
                </div>
                <?php
                // Check if the module is uncompleted or has less than 100% score
                if ($moduleScore < 100 && $countForModule === null && $unmarkedCount == 0 && $numAttemptedQuestion == 0 || $moduleScore < 100 && $countForModule == 0 && $unmarkedCount == 0  && $numAttemptedQuestion == 0 || $moduleScore < 100 && $countForModule > 0 && $unmarkedCount == 0) {
                  echo '<span class="position-absolute top-0 start-100 translate-middle badge badge-pill rounded-pill bg-danger text-white d-flex align-items-center">
                           <span style="font-size: 8px">To-Do</span>
                       </span>';
                } else if ($moduleScore == 100 && $countForModule == 0 && $unmarkedCount == 0 && $numAttemptedQuestion > 0) {
                  echo '<span class="position-absolute top-0 start-100 translate-middle badge badge-pill rounded-pill bg-success text-white d-flex align-items-center" style="z-index: 998;">
                         <span style="font-size: 8px">Done</span>
                     </span>';
                } else if (($moduleScore == 100 && $countForModule > 0 && $unmarkedCount == 0)  || ($moduleScore == 100 && $countForModule === null && $unmarkedCount == 0 && $numAttemptedQuestion == 0)) {
                  echo '<span class="position-absolute top-0 start-100 translate-middle badge badge-pill rounded-pill bg-info text-white d-flex align-items-center" style="z-index: 998;">
                           <span style="font-size: 8px">Short Answer!</span>
                       </span>';
                } else if ($moduleScore == 100 && $unmarkedCount > 0 || $moduleScore < 100 && $unmarkedCount > 0) {
                  echo '<span class="position-absolute top-0 start-100 translate-middle badge badge-pill rounded-pill bg-info text-white d-flex align-items-center" style="z-index: 998;">
                         <span style="font-size: 8px">Unmarked!</span>
                     </span>';
                } else if ($moduleScore < 100 && $countForModule == 0) {
                  echo '<span class="position-absolute top-0 start-100 translate-middle badge badge-pill rounded-pill bg-info text-white d-flex align-items-center" style="z-index: 998;">
                         <span style="font-size: 8px">MCQ!</span>
                     </span>';
                } else if ($moduleScore == 100 && $countForModule == 0 && $unmarkedCount > 0) {
                  echo '<span class="position-absolute top-0 start-100 translate-middle badge badge-pill rounded-pill bg-danger text-white d-flex align-items-center" style="z-index: 998;">
                         <span style="font-size: 8px">Unmarked!</span>
                     </span>';
                }
                // echo (
                //   "MCQ:" . $moduleScore . "<br> " . "Incorrect: " . $countForModule . "<br>Unmarked:" . $unmarkedCount . "<br>Attempted: " . $numAttemptedQuestion);
                ?>
            </a>
            <div class="container mt-auto">
              <hr style="border: 1px solid black;" class="m-0 mb-1">
              <div class="d-flex justify-content-center mt-auto mb-1">
                <strong>Current Status</strong>
              </div>
              <div class="d-flex justify-content-between mt-auto m-3 mb-3">
                <?php
                // var_dump($countForModule);
                // var_dump($unmarkedCount);

                if (($countForModule === 0 && $unmarkedCount === 0 && $numAttemptedQuestion > 0) ||
                  ($countForModule == 0 && $unmarkedCount == "0" && $numAttemptedQuestion > 0)
                ) {
                  echo '<button class="badge badge-pill tooltips rounded-3 p-2 status-badge signature-bg-color bg-gradient" style="border:none; width: 120px; data-bs-toggle="tooltip" data-html="true" data-bs-placement="top" title="Completed">Short Answer</button>';
                } else {
                  echo '<button class="badge badge-pill tooltips rounded-3 p-2 status-badge signature-bg-color bg-gradient" style="border:none; width: 120px;" data-bs-toggle="tooltip" data-html="true" data-bs-placement="top" title="' .
                    (($countForModule !== null || $unmarkedCount > 0) ?
                      ($countForModule !== null ? "False Answer: $countForModule" . "\n" : "No False Answer" . "\n") .
                      "Unmarked Questions: $unmarkedCount" :
                      "No Attempt") . '">
                        Short Answer
                    </button>';
                }
                ?>

                <button class="badge badge-pill tooltips rounded-3 p-2 status-badge signature-bg-color bg-gradient" style="border:none; width: 120px;" data-bs-toggle="tooltip" data-html="true" data-bs-placement="top" title="<?php echo ($moduleScore !== null && $moduleScore !== "0" ? "Highest MCQ Score: $moduleScore" . "%" : "No Attempt") ?>">
                  MCQ
                </button>
              </div>
            </div>
          </div>
      </div>
  <?php
        }
      } else {
        echo "<div class='container mt-5'>";
        echo "<div class='d-flex justify-content-center align-items-center'>";
        echo "<div>";
        echo "<h2> There are no modules allocated. </h2>";
        echo "</div>";
        echo "</div>";
        echo "</div>";
      }
  ?>
    </div>
    </div>
    </div>

    <!-- ==================================================================================  -->
    <div class="mt-5"></div>

    <div class="d-flex justify-content-center align-items-center fixed-center-right" style="z-index: 999;">
      <button class="btn btn-dark btn-sm" type="button" data-bs-toggle="offcanvas" id="offCanvasBtn" data-bs-target="#offcanvasRight" aria-controls="offcanvasRight" style="font-size: 10px; "><i class="fa-solid fa-caret-left fa-fade"></i> <strong> Badge Status </strong></button>
    </div>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasRight" aria-labelledby="offcanvasRightLabel" style="width: 50%;">
      <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="offcanvasRightLabel">Badge Status</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body">
        <!-- ======================== B A D G E S ======================== -->
        <div class="row d-flex justify-content-center">
          <div class="col-md-10">
            <div class="accordion" id="accordionExample">
              <!-- ======================== T o - D o ======================== -->
              <div class="accordion-item">
                <h2 class="accordion-header">
                  <button class="accordion-button collapsed text-light signature-bg-color bg-gradient" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                    <span class=" badge badge-pill rounded-pill bg-danger text-white d-flex align-items-center">
                      <span>To-Do</span>
                    </span>
                  </button>
                </h2>
                <div id="collapseOne" class="accordion-collapse collapse show" data-bs-parent="#accordionExample">
                  <div class="accordion-body">
                    <p style="text-align: justify">The 'To-Do' badge indicates that the module is unattempted or has less than a 100% score in the Multiple Choice Question (MCQ) section, and the short answer section has not been attempted or has false questions in the short answer section.</p>
                  </div>
                </div>
              </div>
              <!-- ======================== E S S A Y ! ======================== -->
              <div class="accordion-item">
                <h2 class="accordion-header">
                  <button class="accordion-button collapsed text-light signature-bg-color bg-gradient" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                    <span class=" badge badge-lg badge-pill rounded-pill bg-info text-white d-flex align-items-center">
                      <span>Short Answer!</span>
                    </span>
                  </button>
                </h2>
                <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#accordionExample">
                  <div class="accordion-body">
                    <p style="text-align: justify">The 'Short Answer!' badge indicates that the module has a 100% score on the Multiple Choice Question (MCQ) section, but there has been no attempt in the short answer section or there are incorrect questions. You can check it in the <a href="written-progress.php">Short Answer Progress</a> page</p>
                  </div>
                </div>
              </div>
              <!-- ======================== M C Q ! ======================== -->
              <div class="accordion-item">
                <h2 class="accordion-header">
                  <button class="accordion-button collapsed text-light signature-bg-color bg-gradient" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                    <span class=" badge badge-pill rounded-pill bg-info text-white d-flex align-items-center">
                      <span>MCQ!</span>
                    </span>
                  </button>
                </h2>
                <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#accordionExample">
                  <div class="accordion-body">
                    <p style="text-align: justify">The 'MCQ!' badge indicates that the module has less than a 100% score on the Multiple Choice Question (MCQ) section, but the short answer section for the module is completed. You can check it in the <a href="progress.php">MCQ Progress</a> page </p>
                  </div>
                </div>
              </div>
              <!-- ======================== U n m a r k e d ======================== -->
              <div class="accordion-item">
                <h2 class="accordion-header">
                  <button class="accordion-button collapsed text-light signature-bg-color bg-gradient" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseThree">
                    <span class=" badge badge-pill rounded-pill bg-info text-white d-flex align-items-center">
                      <span>Unmarked</span>
                    </span>
                  </button>
                </h2>
                <div id="collapseFour" class="accordion-collapse collapse" data-bs-parent="#accordionExample">
                  <div class="accordion-body">
                    <p style="text-align: justify">The 'Unmarked' badge indicates that the module has a 100% score on the Multiple Choice Question (MCQ) section, but there are unmarked short answer questions.
                      You can check it in the <a href="written-progress.php">Short Answer Progress</a> page </p>
                  </div>
                </div>
              </div>
              <!-- ======================== D O N E ======================== -->
              <div class="accordion-item">
                <h2 class="accordion-header">
                  <button class="accordion-button collapsed text-light signature-bg-color bg-gradient" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseThree">
                    <span class=" badge badge-pill rounded-pill bg-success text-white d-flex align-items-center">
                      <span>Done</span>
                    </span>
                  </button>
                </h2>
                <div id="collapseFive" class="accordion-collapse collapse" data-bs-parent="#accordionExample">
                  <div class="accordion-body">
                    <p style="text-align: justify">The 'Done' badge indicates that the module has already been completed, and no further actions required for the module.</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="mt-5"></div>

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

<?php
// Close the database connection
$conn->close();

?>