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
| VARIABLES
|--------------------------------------------------------------------------
*/

$message = '';
$message_type = '';


/*
|--------------------------------------------------------------------------
| HANDLE ADMIN ACTIONS
|--------------------------------------------------------------------------
|
| Actions:
|
| activate
| deactivate
| delete
|
| IMPORTANT:
| Only users whose role is "admin" can be affected.
| Super Admin accounts can NEVER be affected here.
|
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    |--------------------------------------------------------------------------
    | VERIFY CSRF TOKEN
    |--------------------------------------------------------------------------
    */

    verify_csrf();


    /*
    |--------------------------------------------------------------------------
    | GET POST DATA
    |--------------------------------------------------------------------------
    */

    $admin_id =
        (int)($_POST['admin_id'] ?? 0);

    $action =
        $_POST['action'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | VALIDATE ADMIN ID
    |--------------------------------------------------------------------------
    */

    if ($admin_id <= 0) {

        $message =
            'Invalid administrator account.';

        $message_type =
            'error';

    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATE ACTION
    |--------------------------------------------------------------------------
    */

    elseif (
        !in_array(
            $action,
            [
                'activate',
                'deactivate',
                'delete'
            ],
            true
        )
    ) {

        $message =
            'Invalid administrator action.';

        $message_type =
            'error';

    }

    else {

        /*
        |--------------------------------------------------------------------------
        | GET ADMIN ACCOUNT
        |--------------------------------------------------------------------------
        |
        | VERY IMPORTANT:
        |
        | We only search for role = 'admin'.
        |
        | Therefore a Super Admin account can never be modified
        | from this page.
        |
        */

        $stmt = db()->prepare(
            "SELECT
                id,
                name,
                email,
                role,
                status
             FROM users
             WHERE id = ?
             AND role = 'admin'
             LIMIT 1"
        );

        $stmt->execute([
            $admin_id
        ]);

        $admin =
            $stmt->fetch();


        /*
        |--------------------------------------------------------------------------
        | ADMIN NOT FOUND
        |--------------------------------------------------------------------------
        */

        if (!$admin) {

            $message =
                'Administrator account not found or protected.';

            $message_type =
                'error';

        }

        else {

            /*
            |--------------------------------------------------------------------------
            | ACTIVATE ADMIN
            |--------------------------------------------------------------------------
            */

            if ($action === 'activate') {

                $stmt = db()->prepare(
                    "UPDATE users
                     SET status = 'active'
                     WHERE id = ?
                     AND role = 'admin'"
                );

                $stmt->execute([
                    $admin_id
                ]);


                if ($stmt->rowCount() > 0) {

                    $message =
                        'Administrator account activated successfully.';

                    $message_type =
                        'success';

                }

                else {

                    $message =
                        'This administrator is already active.';

                    $message_type =
                        'error';
                }
            }


            /*
            |--------------------------------------------------------------------------
            | DEACTIVATE ADMIN
            |--------------------------------------------------------------------------
            */

            elseif ($action === 'deactivate') {

                /*
                |--------------------------------------------------------------------------
                | EXTRA SECURITY
                |--------------------------------------------------------------------------
                |
                | We again make sure only an Admin can be deactivated.
                |
                */

                $stmt = db()->prepare(
                    "UPDATE users
                     SET status = 'inactive'
                     WHERE id = ?
                     AND role = 'admin'
                     AND status = 'active'"
                );

                $stmt->execute([
                    $admin_id
                ]);


                if ($stmt->rowCount() > 0) {

                    $message =
                        'Administrator account deactivated successfully.';

                    $message_type =
                        'success';

                }

                else {

                    $message =
                        'This administrator is already inactive or cannot be deactivated.';

                    $message_type =
                        'error';
                }
            }


            /*
            |--------------------------------------------------------------------------
            | DELETE ADMIN
            |--------------------------------------------------------------------------
            */

            elseif ($action === 'delete') {

                /*
                |--------------------------------------------------------------------------
                | IMPORTANT
                |--------------------------------------------------------------------------
                |
                | DELETE ONLY USERS WITH ROLE = ADMIN.
                |
                | A Super Admin can NEVER be deleted from this page.
                |
                */

                $stmt = db()->prepare(
                    "DELETE FROM users
                     WHERE id = ?
                     AND role = 'admin'"
                );

                $stmt->execute([
                    $admin_id
                ]);


                if ($stmt->rowCount() > 0) {

                    $message =
                        'Administrator account permanently deleted.';

                    $message_type =
                        'success';

                }

                else {

                    $message =
                        'Unable to delete this administrator account.';

                    $message_type =
                        'error';
                }
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| GET ALL ADMINISTRATORS
|--------------------------------------------------------------------------
|
| IMPORTANT:
|
| Only role = 'admin' is selected.
|
| Super Admin accounts are completely excluded.
|
*/

$stmt = db()->query(
    "SELECT
        id,
        name,
        email,
        status,
        created_at
     FROM users
     WHERE role = 'admin'
     ORDER BY created_at DESC"
);

$admins =
    $stmt->fetchAll();


?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Manage Administrators - Goali Tours
    </title>


    <style>

        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background: #f4f6f9;

            color: #333;
        }


        /*
        |--------------------------------------------------------------------------
        | HEADER
        |--------------------------------------------------------------------------
        */

        .header {

            min-height: 70px;

            background: #1f2937;

            color: white;

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 15px 30px;

            gap: 20px;
        }


        .header h2 {

            margin: 0;

            font-size: 21px;
        }


        .header-right {

            display: flex;

            align-items: center;

            gap: 20px;
        }


        .admin-name {

            font-size: 14px;

            color: #e5e7eb;
        }


        .logout-btn {

            text-decoration: none;

            color: white;

            background: #dc2626;

            padding: 9px 15px;

            border-radius: 6px;

            font-size: 14px;
        }


        .logout-btn:hover {

            background: #b91c1c;
        }


        /*
        |--------------------------------------------------------------------------
        | MAIN CONTAINER
        |--------------------------------------------------------------------------
        */

        .container {

            width: 90%;

            max-width: 1250px;

            margin: 40px auto;
        }


        /*
        |--------------------------------------------------------------------------
        | PAGE TITLE
        |--------------------------------------------------------------------------
        */

        .page-title {

            margin-bottom: 25px;
        }


        .page-title h1 {

            margin: 0 0 7px;

            font-size: 28px;

            color: #111827;
        }


        .page-title p {

            margin: 0;

            color: #6b7280;

            font-size: 15px;
        }


        /*
        |--------------------------------------------------------------------------
        | MESSAGE
        |--------------------------------------------------------------------------
        */

        .message {

            padding: 14px 18px;

            border-radius: 7px;

            margin-bottom: 20px;

            font-size: 14px;
        }


        .message.success {

            background: #dcfce7;

            color: #166534;

            border: 1px solid #86efac;
        }


        .message.error {

            background: #fee2e2;

            color: #991b1b;

            border: 1px solid #fca5a5;
        }


        /*
        |--------------------------------------------------------------------------
        | CARD
        |--------------------------------------------------------------------------
        */

        .card {

            background: white;

            border-radius: 10px;

            box-shadow:
                0 2px 10px
                rgba(0, 0, 0, 0.08);

            overflow: hidden;
        }


        /*
        |--------------------------------------------------------------------------
        | CARD HEADER
        |--------------------------------------------------------------------------
        */

        .card-header {

            padding: 20px 25px;

            border-bottom:
                1px solid #e5e7eb;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;
        }


        .card-header h3 {

            margin: 0;

            font-size: 18px;

            color: #111827;
        }


        .admin-count {

            background: #dbeafe;

            color: #1e40af;

            padding: 6px 12px;

            border-radius: 20px;

            font-size: 13px;

            font-weight: bold;
        }


        /*
        |--------------------------------------------------------------------------
        | TABLE
        |--------------------------------------------------------------------------
        */

        .table-wrapper {

            overflow-x: auto;
        }


        table {

            width: 100%;

            border-collapse: collapse;
        }


        th {

            background: #f9fafb;

            color: #374151;

            font-size: 13px;

            text-align: left;

            padding: 15px 20px;

            border-bottom:
                1px solid #e5e7eb;

            white-space: nowrap;
        }


        td {

            padding: 17px 20px;

            border-bottom:
                1px solid #e5e7eb;

            font-size: 14px;

            color: #4b5563;

            vertical-align: middle;
        }


        tr:hover {

            background: #f9fafb;
        }


        /*
        |--------------------------------------------------------------------------
        | STATUS BADGES
        |--------------------------------------------------------------------------
        */

        .status {

            display: inline-block;

            padding: 5px 10px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: bold;
        }


        .status.active {

            background: #dcfce7;

            color: #166534;
        }


        .status.inactive {

            background: #fee2e2;

            color: #991b1b;
        }


        .status.pending {

            background: #fef3c7;

            color: #92400e;
        }


        .status.rejected {

            background: #f3f4f6;

            color: #4b5563;
        }


        .status.unknown {

            background: #e5e7eb;

            color: #374151;
        }


        /*
        |--------------------------------------------------------------------------
        | ACTIONS
        |--------------------------------------------------------------------------
        */

        .actions {

            display: flex;

            align-items: center;

            flex-wrap: wrap;

            gap: 8px;
        }


        .action-form {

            margin: 0;
        }


        .action-btn {

            border: none;

            padding: 8px 12px;

            border-radius: 5px;

            font-size: 12px;

            cursor: pointer;

            font-weight: bold;

            white-space: nowrap;
        }


        /*
        |--------------------------------------------------------------------------
        | ACTIVATE
        |--------------------------------------------------------------------------
        */

        .activate-btn {

            background: #16a34a;

            color: white;
        }


        .activate-btn:hover {

            background: #15803d;
        }


        /*
        |--------------------------------------------------------------------------
        | DEACTIVATE
        |--------------------------------------------------------------------------
        */

        .deactivate-btn {

            background: #f59e0b;

            color: white;
        }


        .deactivate-btn:hover {

            background: #d97706;
        }


        /*
        |--------------------------------------------------------------------------
        | DELETE
        |--------------------------------------------------------------------------
        */

        .delete-btn {

            background: #dc2626;

            color: white;
        }


        .delete-btn:hover {

            background: #b91c1c;
        }


        /*
        |--------------------------------------------------------------------------
        | EMPTY STATE
        |--------------------------------------------------------------------------
        */

        .empty {

            padding: 60px 20px;

            text-align: center;
        }


        .empty-icon {

            font-size: 45px;

            margin-bottom: 15px;
        }


        .empty h3 {

            margin: 0 0 8px;

            color: #374151;
        }


        .empty p {

            margin: 0;

            color: #6b7280;

            font-size: 14px;
        }


        /*
        |--------------------------------------------------------------------------
        | BACK BUTTON
        |--------------------------------------------------------------------------
        */

        .back-area {

            margin-top: 25px;

            display: flex;

            gap: 10px;

            flex-wrap: wrap;
        }


        .back-btn {

            display: inline-block;

            text-decoration: none;

            background: #374151;

            color: white;

            padding: 10px 17px;

            border-radius: 6px;

            font-size: 14px;
        }


        .back-btn:hover {

            background: #1f2937;
        }


        /*
        |--------------------------------------------------------------------------
        | MOBILE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 768px) {

            .header {

                padding: 15px;

                flex-direction: column;

                align-items: flex-start;
            }


            .header-right {

                width: 100%;

                justify-content:
                    space-between;
            }


            .admin-name {

                display: none;
            }


            .container {

                width: 95%;

                margin: 25px auto;
            }


            .page-title h1 {

                font-size: 24px;
            }


            th,
            td {

                padding:
                    12px 10px;
            }


            .actions {

                flex-direction: column;

                align-items: stretch;
            }


            .action-form {

                width: 100%;
            }


            .action-btn {

                width: 100%;
            }

        }

    </style>

</head>


<body>


<!--
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
-->

<header class="header">


    <h2>

        Goali Tours Management System

    </h2>


    <div class="header-right">


        <span class="admin-name">

            <?= e(
                $_SESSION['admin_name']
                ?? 'Super Admin'
            ) ?>

            (Super Admin)

        </span>


        <a
            href="logout.php"
            class="logout-btn"
        >

            Logout

        </a>


    </div>


</header>



<!--
|--------------------------------------------------------------------------
| MAIN CONTENT
|--------------------------------------------------------------------------
-->

<main class="container">


    <!-- PAGE TITLE -->

    <div class="page-title">


        <h1>

            Manage Administrators

        </h1>


        <p>

            View, activate, deactivate, and permanently remove
            administrator accounts.

        </p>


    </div>



    <!-- MESSAGE -->

    <?php if ($message !== ''): ?>


        <div
            class="message <?= e($message_type) ?>"
        >

            <?= e($message) ?>

        </div>


    <?php endif; ?>



    <!-- ADMIN CARD -->

    <div class="card">


        <div class="card-header">


            <h3>

                Administrator Accounts

            </h3>


            <span class="admin-count">

                <?= count($admins) ?>

                Administrators

            </span>


        </div>



        <?php if (!empty($admins)): ?>


            <div class="table-wrapper">


                <table>


                    <thead>

                        <tr>

                            <th>
                                #
                            </th>

                            <th>
                                Name
                            </th>

                            <th>
                                Email
                            </th>

                            <th>
                                Registered Date
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        <?php foreach (
                            $admins
                            as $index => $admin
                        ): ?>


                            <tr>


                                <!-- NUMBER -->

                                <td>

                                    <?= $index + 1 ?>

                                </td>



                                <!-- NAME -->

                                <td>

                                    <strong>

                                        <?= e(
                                            $admin['name']
                                        ) ?>

                                    </strong>

                                </td>



                                <!-- EMAIL -->

                                <td>

                                    <?= e(
                                        $admin['email']
                                    ) ?>

                                </td>



                                <!-- REGISTERED DATE -->

                                <td>

                                    <?= e(
                                        date(
                                            'd M Y, h:i A',
                                            strtotime(
                                                $admin['created_at']
                                            )
                                        )
                                    ) ?>

                                </td>



                                <!-- STATUS -->

                                <td>


                                    <?php

                                    $status =
                                        strtolower(
                                            (string)$admin['status']
                                        );

                                    if (
                                        $status === 'active'
                                    ) {

                                        $statusClass =
                                            'active';

                                        $statusText =
                                            'Active';

                                    } elseif (
                                        $status === 'inactive'
                                    ) {

                                        $statusClass =
                                            'inactive';

                                        $statusText =
                                            'Inactive';

                                    } elseif (
                                        $status === 'pending'
                                    ) {

                                        $statusClass =
                                            'pending';

                                        $statusText =
                                            'Pending';

                                    } elseif (
                                        $status === 'rejected'
                                    ) {

                                        $statusClass =
                                            'rejected';

                                        $statusText =
                                            'Rejected';

                                    } else {

                                        $statusClass =
                                            'unknown';

                                        $statusText =
                                            ucfirst(
                                                $status
                                            );
                                    }

                                    ?>


                                    <span
                                        class="status
                                        <?= e($statusClass) ?>"
                                    >

                                        <?= e(
                                            $statusText
                                        ) ?>

                                    </span>


                                </td>



                                <!-- ACTIONS -->

                                <td>


                                    <div class="actions">


                                        <?php if (
                                            $status !== 'active'
                                        ): ?>


                                            <!--
                                            ------------------------------------------------
                                            ACTIVATE
                                            ------------------------------------------------
                                            -->

                                            <form
                                                method="POST"
                                                class="action-form"
                                                onsubmit="
                                                    return confirm(
                                                        'Are you sure you want to activate this administrator?'
                                                    );
                                                "
                                            >


                                                <input
                                                    type="hidden"
                                                    name="admin_id"
                                                    value="<?= (int)$admin['id'] ?>"
                                                >


                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="activate"
                                                >


                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= e(
                                                        csrf_token()
                                                    ) ?>"
                                                >


                                                <button
                                                    type="submit"
                                                    class="action-btn activate-btn"
                                                >

                                                    ACTIVATE

                                                </button>


                                            </form>


                                        <?php endif; ?>



                                        <?php if (
                                            $status === 'active'
                                        ): ?>


                                            <!--
                                            ------------------------------------------------
                                            DEACTIVATE
                                            ------------------------------------------------
                                            -->

                                            <form
                                                method="POST"
                                                class="action-form"
                                                onsubmit="
                                                    return confirm(
                                                        'Are you sure you want to deactivate this administrator?'
                                                    );
                                                "
                                            >


                                                <input
                                                    type="hidden"
                                                    name="admin_id"
                                                    value="<?= (int)$admin['id'] ?>"
                                                >


                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="deactivate"
                                                >


                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= e(
                                                        csrf_token()
                                                    ) ?>"
                                                >


                                                <button
                                                    type="submit"
                                                    class="action-btn deactivate-btn"
                                                >

                                                    DEACTIVATE

                                                </button>


                                            </form>


                                        <?php endif; ?>



                                        <!--
                                        ------------------------------------------------
                                        DELETE
                                        ------------------------------------------------
                                        -->

                                        <form
                                            method="POST"
                                            class="action-form"
                                            onsubmit="
                                                return confirm(
                                                    'WARNING: This will permanently delete this administrator account. Are you sure?'
                                                );
                                            "
                                        >


                                            <input
                                                type="hidden"
                                                name="admin_id"
                                                value="<?= (int)$admin['id'] ?>"
                                            >


                                            <input
                                                type="hidden"
                                                name="action"
                                                value="delete"
                                            >


                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= e(
                                                    csrf_token()
                                                ) ?>"
                                            >


                                            <button
                                                type="submit"
                                                class="action-btn delete-btn"
                                            >

                                                DELETE

                                            </button>


                                        </form>


                                    </div>


                                </td>


                            </tr>


                        <?php endforeach; ?>


                    </tbody>


                </table>


            </div>


        <?php else: ?>


            <!-- EMPTY -->

            <div class="empty">


                <div class="empty-icon">

                    👤

                </div>


                <h3>

                    No Administrators Found

                </h3>


                <p>

                    There are currently no administrator accounts
                    in the system.

                </p>


            </div>


        <?php endif; ?>


    </div>



    <!-- BACK BUTTON -->

    <div class="back-area">


        <a
            href="dashboard_super_admin.php"
            class="back-btn"
        >

            ← Back to Super Admin Dashboard

        </a>


        <a
            href="admin_requests.php"
            class="back-btn"
        >

            Admin Requests

        </a>


    </div>


</main>


</body>

</html>