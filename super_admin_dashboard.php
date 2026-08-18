<?php

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';


/*
|--------------------------------------------------------------------------
| SUPER ADMIN ACCESS
|--------------------------------------------------------------------------
|
| Only an active Super Admin can access this page.
|
*/

require_super_admin();


/*
|--------------------------------------------------------------------------
| GET PENDING ADMIN COUNT
|--------------------------------------------------------------------------
*/

$stmt = db()->query(
    "SELECT COUNT(*)
     FROM users
     WHERE role = 'admin'
     AND status = 'pending'"
);

$pendingAdminCount =
    (int)$stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| GET ACTIVE ADMIN COUNT
|--------------------------------------------------------------------------
*/

$stmt = db()->query(
    "SELECT COUNT(*)
     FROM users
     WHERE role = 'admin'
     AND status = 'active'"
);

$activeAdminCount =
    (int)$stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| GET TOTAL TOUR COUNT
|--------------------------------------------------------------------------
*/

$stmt = db()->query(
    "SELECT COUNT(*)
     FROM tours
     WHERE status = 'active'"
);

$totalTourCount =
    (int)$stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| GET CATEGORIES
|--------------------------------------------------------------------------
*/

$categories = db()->query(
    'SELECT * FROM categories ORDER BY name'
)->fetchAll();


?>

<!doctype html>

<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1"
>

<title>
    Goali Tours — Super Admin Dashboard
</title>

<link
    rel="stylesheet"
    href="assets/css/style.css"
>


<style>

/*
|--------------------------------------------------------------------------
| SUPER ADMIN PAGE
|--------------------------------------------------------------------------
*/

.super-admin-page {

    min-height: 100vh;

    background: #f4f6f9;

}


/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

.super-header {

    background: #111827;

    color: white;

    padding: 18px 30px;

    display: flex;

    align-items: center;

    justify-content: space-between;
}


.super-header-left {

    display: flex;

    flex-direction: column;

    gap: 4px;
}


.super-header-title {

    font-size: 21px;

    font-weight: 700;
}


.super-header-subtitle {

    font-size: 13px;

    color: #d1d5db;
}


.super-header-right {

    display: flex;

    align-items: center;

    gap: 18px;
}


.super-admin-name {

    font-size: 14px;

    color: #e5e7eb;
}


.logout-button {

    text-decoration: none;

    color: white;

    background: #dc2626;

    padding: 9px 15px;

    border-radius: 6px;

    font-size: 14px;
}


.logout-button:hover {

    background: #b91c1c;
}


/*
|--------------------------------------------------------------------------
| MAIN
|--------------------------------------------------------------------------
*/

.super-container {

    width: 92%;

    max-width: 1250px;

    margin: 35px auto;
}


.welcome-section {

    margin-bottom: 25px;
}


.welcome-section h1 {

    margin: 0 0 7px;

    color: #111827;

    font-size: 28px;
}


.welcome-section p {

    margin: 0;

    color: #6b7280;

    font-size: 15px;
}


/*
|--------------------------------------------------------------------------
| STAT CARDS
|--------------------------------------------------------------------------
*/

.stats-grid {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 20px;

    margin-bottom: 30px;
}


.stat-card {

    background: white;

    border-radius: 10px;

    padding: 22px;

    box-shadow:
        0 2px 10px
        rgba(0, 0, 0, 0.07);
}


.stat-label {

    color: #6b7280;

    font-size: 14px;

    margin-bottom: 10px;
}


.stat-number {

    color: #111827;

    font-size: 30px;

    font-weight: 700;

}


.stat-link {

    display: inline-block;

    margin-top: 12px;

    text-decoration: none;

    font-size: 13px;

    color: #2563eb;
}


.stat-link:hover {

    text-decoration: underline;
}


/*
|--------------------------------------------------------------------------
| PENDING CARD
|--------------------------------------------------------------------------
*/

.pending-card {

    border-left:
        5px solid #f59e0b;
}


/*
|--------------------------------------------------------------------------
| MAIN GRID
|--------------------------------------------------------------------------
*/

.admin-grid {

    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 20px;
}


/*
|--------------------------------------------------------------------------
| ACTION CARD
|--------------------------------------------------------------------------
*/

.action-card {

    background: white;

    border-radius: 10px;

    padding: 25px;

    box-shadow:
        0 2px 10px
        rgba(0, 0, 0, 0.07);
}


.action-card h2 {

    margin: 0 0 8px;

    font-size: 19px;

    color: #111827;
}


.action-card p {

    margin: 0 0 20px;

    color: #6b7280;

    font-size: 14px;

    line-height: 1.6;
}


.action-buttons {

    display: flex;

    flex-wrap: wrap;

    gap: 10px;
}


.action-button {

    display: inline-block;

    text-decoration: none;

    padding: 10px 15px;

    border-radius: 6px;

    font-size: 13px;

    font-weight: 600;
}


.action-button.primary {

    background: #2563eb;

    color: white;
}


.action-button.primary:hover {

    background: #1d4ed8;
}


.action-button.secondary {

    background: #374151;

    color: white;
}


.action-button.secondary:hover {

    background: #1f2937;
}


.action-button.warning {

    background: #f59e0b;

    color: white;
}


.action-button.warning:hover {

    background: #d97706;
}


.action-button.success {

    background: #16a34a;

    color: white;
}


.action-button.success:hover {

    background: #15803d;
}


/*
|--------------------------------------------------------------------------
| CATEGORY SECTION
|--------------------------------------------------------------------------
*/

.category-card {

    background: white;

    border-radius: 10px;

    padding: 25px;

    margin-top: 20px;

    box-shadow:
        0 2px 10px
        rgba(0, 0, 0, 0.07);
}


.category-card h2 {

    margin: 0 0 18px;

    font-size: 19px;

    color: #111827;
}


.category-list {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 12px;
}


.category-item {

    background: #f9fafb;

    border: 1px solid #e5e7eb;

    padding: 14px;

    border-radius: 7px;

    color: #374151;

    font-size: 14px;
}


/*
|--------------------------------------------------------------------------
| FOOTER
|--------------------------------------------------------------------------
*/

.super-footer {

    text-align: center;

    color: #9ca3af;

    font-size: 12px;

    margin-top: 35px;

    padding-bottom: 25px;
}


/*
|--------------------------------------------------------------------------
| RESPONSIVE
|--------------------------------------------------------------------------
*/

@media (max-width: 900px) {

    .stats-grid {

        grid-template-columns:
            1fr;
    }


    .admin-grid {

        grid-template-columns:
            1fr;
    }


    .category-list {

        grid-template-columns:
            1fr;
    }

}


@media (max-width: 600px) {

    .super-header {

        padding: 15px;

        flex-direction: column;

        align-items: flex-start;

        gap: 15px;
    }


    .super-header-right {

        width: 100%;

        justify-content:
            space-between;
    }


    .super-container {

        width: 94%;

        margin: 25px auto;
    }


    .welcome-section h1 {

        font-size: 24px;
    }

}

</style>

</head>


<body class="super-admin-page">


<!-- =========================================================
     HEADER
========================================================== -->

<header class="super-header">


    <div class="super-header-left">

        <div class="super-header-title">

            GOALI TOURS

        </div>


        <div class="super-header-subtitle">

            Super Administrator Control Panel

        </div>

    </div>


    <div class="super-header-right">


        <span class="super-admin-name">

            <?= e(
                $_SESSION['admin_name']
                ?? 'Super Admin'
            ) ?>

        </span>


        <a
            href="logout.php"
            class="logout-button"
        >

            Logout

        </a>


    </div>


</header>



<!-- =========================================================
     MAIN CONTENT
========================================================== -->

<main class="super-container">


    <!-- WELCOME -->

    <section class="welcome-section">


        <h1>

            Super Admin Dashboard

        </h1>


        <p>

            Manage administrators, tour packages,
            and the complete Goali Tours system.

        </p>


    </section>



    <!-- =====================================================
         STATISTICS
    ====================================================== -->

    <section class="stats-grid">


        <!-- PENDING ADMINS -->

        <div class="stat-card pending-card">


            <div class="stat-label">

                Pending Admin Requests

            </div>


            <div class="stat-number">

                <?= $pendingAdminCount ?>

            </div>


            <a
                href="admin_requests.php"
                class="stat-link"
            >

                Review Requests →

            </a>


        </div>



        <!-- ACTIVE ADMINS -->

        <div class="stat-card">


            <div class="stat-label">

                Active Administrators

            </div>


            <div class="stat-number">

                <?= $activeAdminCount ?>

            </div>


            <a
                href="manage_admins.php"
                class="stat-link"
            >

                Manage Administrators →

            </a>


        </div>



        <!-- TOUR PACKAGES -->

        <div class="stat-card">


            <div class="stat-label">

                Active Tour Packages

            </div>


            <div class="stat-number">

                <?= $totalTourCount ?>

            </div>


            <a
                href="dashboard.php"
                class="stat-link"
            >

                View Packages →

            </a>


        </div>


    </section>



    <!-- =====================================================
         ADMIN MANAGEMENT / TOUR MANAGEMENT
    ====================================================== -->

    <section class="admin-grid">


        <!-- ADMIN MANAGEMENT -->

        <div class="action-card">


            <h2>

                Administrator Management

            </h2>


            <p>

                Review new administrator registrations,
                approve or reject requests, and manage
                existing administrators.

            </p>


            <div class="action-buttons">


                <a
                    href="admin_requests.php"
                    class="action-button warning"
                >

                    Admin Requests

                    <?php if ($pendingAdminCount > 0): ?>

                        (<?= $pendingAdminCount ?>)

                    <?php endif; ?>

                </a>


                <a
                    href="manage_admins.php"
                    class="action-button primary"
                >

                    Manage Administrators

                </a>


            </div>


        </div>



        <!-- TOUR MANAGEMENT -->

        <div class="action-card">


            <h2>

                Tour Package Management

            </h2>


            <p>

                Access tour packages, create new packages,
                edit existing packages, move packages to
                the recycle bin, and generate PDFs.

            </p>


            <div class="action-buttons">


                <a
                    href="dashboard.php"
                    class="action-button primary"
                >

                    Tour Packages

                </a>


                <a
                    href="recycle_bin.php"
                    class="action-button secondary"
                >

                    Recycle Bin

                </a>


            </div>


        </div>



        <!-- COMPANY SETTINGS -->

        <div class="action-card">


            <h2>

                Company Settings

            </h2>


            <p>

                Manage Goali Tours company information,
                logo, and other system-wide settings.

            </p>


            <div class="action-buttons">


                <a
                    href="company_settings.php"
                    class="action-button secondary"
                >

                    Company Settings

                </a>


            </div>


        </div>



        <!-- PROFILE -->

        <div class="action-card">


            <h2>

                Super Admin Profile

            </h2>


            <p>

                View and manage your Super Administrator
                account information.

            </p>


            <div class="action-buttons">


                <a
                    href="profile.php"
                    class="action-button success"
                >

                    My Profile

                </a>


            </div>


        </div>


    </section>



    <!-- =====================================================
         TOUR CATEGORIES
    ====================================================== -->

    <section class="category-card">


        <h2>

            Tour Categories

        </h2>


        <?php if (!empty($categories)): ?>


            <div class="category-list">


                <?php foreach ($categories as $category): ?>


                    <div class="category-item">

                        <?= e(
                            $category['name']
                        ) ?>

                    </div>


                <?php endforeach; ?>


            </div>


        <?php else: ?>


            <p>

                No tour categories have been created yet.

            </p>


        <?php endif; ?>


    </section>



    <!-- FOOTER -->

    <div class="super-footer">

        Goali Tours Management System

        &copy;

        <?= date('Y') ?>

    </div>


</main>


</body>

</html>