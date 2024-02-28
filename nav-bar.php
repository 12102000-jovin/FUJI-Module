<nav class="navbar navbar-expand-lg shadow-sm bg-light">
    <div class="container-fluid">

        <!-- Hamburger Menu Button (for mobile) -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Image visible on small screens (Hamburger Menu Button is there) -->
        <div class="d-block d-lg-none">
            <div class="d-flex align-items-center hstack gap-3">
                <a href="index.php">
                    <img src="Images/FE-logo.png" alt="Logo" class="img-fluid" style="max-height: 30px;">
                </a>
                <div class="vr signature-color" style="border: 1px solid"></div>
                <div>
                    <h3 class="my-auto signature-color fw-bold">Module Training</h3>
                </div>
            </div>
        </div>

        <!-- Collapsible Navigation Links -->
        <div class="collapse navbar-collapse" id="navbarSupportedContent">

            <!-- Website Logo -->
            <div class="d-none d-lg-block">
                <div class="d-flex align-items-center hstack gap-3">
                    <a href="index.php">
                        <img src="Images/FE-logo.png" alt="Logo" class="img-fluid" style="max-height: 30px;">
                    </a>
                    <div class="vr signature-color" style="border: 1px solid"></div>
                    <div>
                        <h3 class=" my-auto signature-color fw-bold">Module Training</h3>
                    </div>
                </div>
            </div>

            <!-- List of Navigation Links -->
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 text-center"> <!-- Add 'text-center' class here -->

                <!-- Home Link -->
                <li class="nav-item">
                    <a class="nav-link" aria-current="page" href="index.php">Home</a>
                </li>

                <!-- Modules Link -->
                <li class="nav-item">
                    <a class="nav-link" href="modules.php">Modules</a>
                </li>

                <!-- Progress Link -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle signature-color" href="#" id="progressDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Progress
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end " aria-labelledby="progressDropdown">
                        <li><a class="dropdown-item" href="progress.php">MCQ Progress</a></li>
                        <li><a class="dropdown-item" href="written-progress.php"> Short Answer Progress</a></li>
                    </ul>
                </li>

                <!-- Profile Link -->
                <li class="nav-item">
                    <a class="nav-link" href="profile.php">Profile</a>
                </li>

                <!-- Admin-specific Dropdown -->
                <?php if ($role === 'admin') : ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle signature-color" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Admin
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminDropdown">
                            <li><a class="dropdown-item" href="archive.php">Archive Modules</a></li>
                            <li><a class="dropdown-item" href="allocate.php">Manage Users</a></li>
                            <li><a class="dropdown-item" href="allocate-group.php">Allocate by Department</a></li>
                            <li><a class="dropdown-item" href="department.php">Manage Department</a></li>
                            <li><a class="dropdown-item" href="licenses.php">Manage Licenses</a></li>
                            <li><a class="dropdown-item" href="written-question-user.php">Mark Short Answer Question</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <h6 class="dropdown-header" style="color: #043f9d">Report</h6>
                            </li>
                            <li><a class="dropdown-item" href="report.php">MCQ Report</a></li>
                            <li><a class="dropdown-item" href="written-report-user.php">Short Answer Report</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                        </ul>
                    </li>

                <?php endif; ?>

                <!-- Supervisor-specific Dropdown -->
                <?php if ($role === 'supervisor') : ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle signature-color" href="#" id="supervisorDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Supervisor
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="supervisorDropdown">
                            <li><a class="dropdown-item" href="allocate.php">Manage Users</a></li>
                            <li><a class="dropdown-item" href="allocate-group.php">Allocate by Department</a></li>
                            <li><a class="dropdown-item" href="written-question-user.php">Mark Short Answer Question</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <h6 class="dropdown-header" style="color: #043f9d">Report</h6>
                            </li>
                            <li><a class="dropdown-item" href="report.php">MCQ Report</a></li>
                            <li><a class="dropdown-item" href="written-report-user.php">Short Answer Report</a></li>

                        </ul>
                    </li>
                <?php endif; ?>


                <!-- Logout Button -->
                <li class="nav-item">
                    <a class="nav-link text-danger" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>