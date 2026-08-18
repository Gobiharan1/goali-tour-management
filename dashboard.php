<?php

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

require_login();


/*
|--------------------------------------------------------------------------
| CHECK USER ROLE
|--------------------------------------------------------------------------
|
| Only Super Admin will see the Control Panel option.
|
*/

$isSuperAdmin =
    ($_SESSION['admin_role'] ?? '') === 'super_admin';


/*
|--------------------------------------------------------------------------
| GET CATEGORIES
|--------------------------------------------------------------------------
*/

$categories = db()->query(
    'SELECT * FROM categories ORDER BY name'
)->fetchAll();


$category_id = (int)(
    $_GET['category_id']
    ?? ($categories[0]['id'] ?? 0)
);

$duration_days = (int)($_GET['duration_days'] ?? 0);
if ($duration_days < 1 || $duration_days > 30) {
    $duration_days = 0;
}


/*
|--------------------------------------------------------------------------
| GET TOURS
|--------------------------------------------------------------------------
*/

$sql = "SELECT *
        FROM tours
        WHERE category_id = ?
        AND status = 'active'";

$params = [$category_id];

if ($duration_days > 0) {
    $sql .= " AND duration_days = ?";
    $params[] = $duration_days;
}

$sql .= " ORDER BY updated_at DESC";

$stmt = db()->prepare($sql);
$stmt->execute($params);

$tours = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| COMPANY LOGO
|--------------------------------------------------------------------------
*/

$logoPath = company_logo();
$hasLogo = $logoPath !== '';

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
    Goali Tours — Dashboard
</title>

<link
    rel="stylesheet"
    href="assets/css/style.css"
>


<style>

/*
|--------------------------------------------------------------------------
| DASHBOARD LOGO
|--------------------------------------------------------------------------
*/

.dashboard-logo {

    max-width: 180px;

    max-height: 70px;

    object-fit: contain;

    display: block;

    margin-bottom: 20px;

}


/*
|--------------------------------------------------------------------------
| SIDEBAR BRAND
|--------------------------------------------------------------------------
*/

.sidebar-brand {

    display: flex;

    flex-direction: column;

    align-items: flex-start;

    margin-bottom: 25px;

}


.sidebar-brand .brand-mark {

    margin-bottom: 8px;

}


/*
|--------------------------------------------------------------------------
| SUPER ADMIN CONTROL PANEL
|--------------------------------------------------------------------------
*/

.control-panel-link {

    margin-top: 5px;

    font-weight: 700;

}


/*
|--------------------------------------------------------------------------
| TOUR THUMB
|--------------------------------------------------------------------------
*/

.tour-thumb {

    width: 100%;

    height: 170px;

    background: #f3f3f3;

    border-radius: 10px 10px 0 0;

    display: flex;

    align-items: center;

    justify-content: center;

    overflow: hidden;

    color: #777;

    font-weight: 700;

    font-size: 20px;

}


/*
|--------------------------------------------------------------------------
| TOUR THUMB IMAGE
|--------------------------------------------------------------------------
*/

.tour-thumb img {

    width: 100%;

    height: 100%;

    object-fit: cover;

    display: block;

}


/*
|--------------------------------------------------------------------------
| LOGO STATUS
|--------------------------------------------------------------------------
*/

.logo-status {

    font-size: 12px;

    color: #777;

    margin-top: 5px;

}


/*
|--------------------------------------------------------------------------
| RESPONSIVE
|--------------------------------------------------------------------------
*/

@media (max-width: 800px) {

    .dashboard-logo {

        max-width: 150px;

        max-height: 60px;

    }

}

</style>

</head>


<body>


<div class="app-shell">


<!-- =========================================================
     SIDEBAR
========================================================== -->

<aside class="sidebar">


    <!-- =====================================================
         COMPANY BRAND
    ====================================================== -->

    <div class="sidebar-brand">


        <?php if ($hasLogo): ?>

            <img
                src="<?= e($logoPath) ?>"
                alt="Goali Tours Logo"
                class="dashboard-logo"
            >

        <?php endif; ?>


        <div class="brand-mark">

            GOALI TOURS

        </div>


    </div>



    <!-- =====================================================
         CURRENT ADMIN
    ====================================================== -->

    <div class="profile-mini">

        <?= e(
            $_SESSION['admin_name']
            ?? 'Administrator'
        ) ?>

    </div>



    <!-- =====================================================
         TOUR CATEGORY
    ====================================================== -->

    <label class="side-label">

        Tour Category

    </label>


    <select
        id="categorySelect"
        class="category-select"
    >

        <?php foreach ($categories as $cat): ?>

            <option
                value="<?= (int)$cat['id'] ?>"
                <?= $category_id === (int)$cat['id']
                    ? 'selected'
                    : ''
                ?>
            >

                <?= e($cat['name']) ?>

            </option>

        <?php endforeach; ?>

    </select>



    <!-- =====================================================
         NAVIGATION
    ====================================================== -->

    <nav class="side-nav">


        <!-- TOUR PACKAGES -->

        <a
            class="active"
            href="dashboard.php?category_id=<?= $category_id ?>&duration_days=<?= $duration_days ?>"
        >

            Tour Packages

        </a>



        <!-- RECYCLE BIN -->

        <a href="recycle_bin.php">

            Recycle Bin

        </a>



        <!-- ADMIN PROFILE -->

        <a href="profile.php">

            Admin Profile

        </a>



       <?php if ($isSuperAdmin): ?>

    <!-- CONTROL PANEL — SUPER ADMIN ONLY -->

    <a
        href="super_admin_dashboard.php"
        class="control-panel-link"
    >

        Control Panel

    </a>

<?php endif; ?>


<!-- LOG OUT -->

<a href="logout.php">

    Log Out

</a>


    </nav>



    <!-- =====================================================
         LOGO STATUS
    ====================================================== -->

    <?php if ($hasLogo): ?>

        <div class="logo-status">

            Company logo loaded.

        </div>

    <?php else: ?>

        <div class="logo-status">

            No company logo uploaded.

        </div>

    <?php endif; ?>


</aside>



<!-- =========================================================
     MAIN PANEL
========================================================== -->

<main class="main-panel">


    <!-- =====================================================
         TOP BAR
    ====================================================== -->

    <div class="topbar">


        <div>

            <h1>

                Tour Packages

            </h1>


            <p class="muted">

                Select a package to customize or download.

            </p>

        </div>


        <a
            class="btn primary"
            href="tour_form.php?category_id=<?= $category_id ?>"
        >

            + Add Tour Package

        </a>


    </div>



    <!-- =====================================================
         TOOLBAR
    ====================================================== -->

    <div class="package-toolbar">


        <button
            class="btn danger-outline"
            id="removeBtn"
            type="button"
        >

            Remove Package

        </button>


        <input
            id="searchBox"
            class="search"
            placeholder="Search packages..."
        >

        <select id="durationFilter" class="category-select" style="max-width:180px;">
            <option value="0" <?= $duration_days === 0 ? 'selected' : '' ?>>All Days</option>
            <?php for ($i = 1; $i <= 30; $i++): ?>
                <option value="<?= $i ?>" <?= $duration_days === $i ? 'selected' : '' ?>>
                    <?= $i ?> Day<?= $i > 1 ? 's' : '' ?>
                </option>
            <?php endfor; ?>
        </select>


    </div>



    <!-- =====================================================
         PACKAGE FORM
    ====================================================== -->

    <form
        method="post"
        action="delete_tours.php"
        id="packageForm"
    >


        <input
            type="hidden"
            name="category_id"
            value="<?= $category_id ?>"
        >

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e(csrf_token()) ?>"
        >



        <div
            class="package-grid"
            id="packageGrid"
        >


            <?php foreach ($tours as $tour): ?>


                <?php

                /*
                |--------------------------------------------------------------------------
                | TOUR IMAGE
                |--------------------------------------------------------------------------
                |
                | The images field can contain one or more image paths.
                | We use the first available image for the dashboard card.
                |
                */

                $tourImage = '';


                if (!empty($tour['images'])) {

                    $imageLines =
                        preg_split(
                            '/\R/',
                            $tour['images']
                        );


                    $imageLines =
                        array_values(
                            array_filter(
                                array_map(
                                    'trim',
                                    $imageLines
                                )
                            )
                        );


                    if (!empty($imageLines[0])) {

                        $tourImage =
                            $imageLines[0];

                    }

                }

                ?>


                <!-- =================================================
                     TOUR CARD
                ================================================== -->

                <article class="tour-card">


                    <!-- CHECKBOX -->

                    <label class="check">

                        <input
                            type="checkbox"
                            name="tour_ids[]"
                            value="<?= (int)$tour['id'] ?>"
                            class="package-check"
                        >

                    </label>



                    <!-- TOUR IMAGE -->

                    <div class="tour-thumb">


                        <?php if ($tourImage !== ''): ?>

                            <img
                                src="<?= e($tourImage) ?>"
                                alt="<?= e($tour['tour_name']) ?>"
                                onerror="this.style.display='none';"
                            >

                        <?php else: ?>

                            TOUR

                        <?php endif; ?>


                    </div>



                    <!-- TOUR INFORMATION -->

                    <div class="tour-body">


                        <!-- ACTIVITY LEVEL -->

                        <span class="pill">

                            <?= e(
                                $tour['activity_level']
                            ) ?>

                        </span>


                        <!-- TOUR NAME -->

                        <h3>

                            <?= e(
                                $tour['tour_name']
                            ) ?>

                        </h3>


                        <!-- DURATION -->

                        <p>

                            <?= (int)$tour['duration_days'] ?>

                            Days /

                            <?= (int)$tour['duration_nights'] ?>

                            Nights

                        </p>


                        <!-- PACKAGE ID -->

                        <p class="muted small">

                            Package ID:

                            <?= e(
                                $tour['package_id']
                            ) ?>

                        </p>



                        <!-- ACTIONS -->

                        <div class="card-actions">


                            <!-- OPEN / CUSTOMIZE -->

                            <a
                                class="btn small-btn"
                                href="tour_form.php?id=<?= (int)$tour['id'] ?>"
                            >

                                Open / Customize

                            </a>



                            <!-- PDF -->

                            <a
                                class="btn small-btn secondary"
                                href="generate_pdf.php?id=<?= (int)$tour['id'] ?>"
                                target="_blank"
                            >

                                PDF

                            </a>


                        </div>


                    </div>


                </article>


            <?php endforeach; ?>


        </div>


    </form>



    <!-- =====================================================
         EMPTY STATE
    ====================================================== -->

    <?php if (!$tours): ?>


        <div class="empty-state">

            No tour packages in this category yet.

            Click

            “Add Tour Package”

            to create one.


        </div>


    <?php endif; ?>


</main>


</div>



<!-- =========================================================
     JAVASCRIPT
========================================================== -->

<script>


/*
|--------------------------------------------------------------------------
| CATEGORY SELECT
|--------------------------------------------------------------------------
*/

document
    .getElementById('categorySelect')
    .addEventListener(
        'change',
        function () {

            window.location =
                'dashboard.php?category_id=' +
                this.value +
                '&duration_days=' +
                (new URLSearchParams(window.location.search).get('duration_days') || '0');

        }
    );



/*
|--------------------------------------------------------------------------
| REMOVE PACKAGES
|--------------------------------------------------------------------------
*/

document
    .getElementById('removeBtn')
    .addEventListener(
        'click',
        function () {


            const selected =
                document.querySelectorAll(
                    '.package-check:checked'
                );


            if (!selected.length) {

                alert(
                    'Select at least one package to move to the recycle bin.'
                );

                return;

            }


            if (
                confirm(
                    'Move the selected package(s) to the recycle bin?'
                )
            ) {

                document
                    .getElementById('packageForm')
                    .submit();

            }


        }
    );



/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

document
    .getElementById('searchBox')
    .addEventListener(
        'input',
        function () {


            const q =
                this.value.toLowerCase();


            document
                .querySelectorAll('.tour-card')
                .forEach(
                    card => {


                        card.style.display =
                            card.innerText
                                .toLowerCase()
                                .includes(q)
                                ? ''
                                : 'none';


                    }
                );


        }
    );


document.getElementById('durationFilter').addEventListener('change', function () {
    const params = new URLSearchParams(window.location.search);
    params.set('duration_days', this.value);
    window.location = 'dashboard.php?' + params.toString();
});

</script>


</body>

</html>
