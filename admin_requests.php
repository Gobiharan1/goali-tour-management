<?php

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';


/*
|--------------------------------------------------------------------------
| SUPER ADMIN ACCESS
|--------------------------------------------------------------------------
| Only an active Super Admin can access this page.
*/

require_super_admin();


/*
|--------------------------------------------------------------------------
| HANDLE APPROVE / REJECT
|--------------------------------------------------------------------------
*/

$message = '';
$message_type = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    |--------------------------------------------------------------------------
    | VERIFY CSRF TOKEN
    |--------------------------------------------------------------------------
    */

    verify_csrf();


    $request_id =
        (int)($_POST['request_id'] ?? 0);

    $action =
        $_POST['action'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | VALIDATE REQUEST
    |--------------------------------------------------------------------------
    */

    if ($request_id <= 0) {

        $message =
            'Invalid administrator request.';

        $message_type =
            'error';

    } elseif (
        !in_array(
            $action,
            ['approve', 'reject'],
            true
        )
    ) {

        $message =
            'Invalid action.';

        $message_type =
            'error';

    } else {


        /*
        |--------------------------------------------------------------------------
        | APPROVE ADMIN
        |--------------------------------------------------------------------------
        */

        if ($action === 'approve') {

            $stmt = db()->prepare(
                "UPDATE users
                 SET status = 'active'
                 WHERE id = ?
                 AND role = 'admin'
                 AND status = 'pending'"
            );

            $stmt->execute([
                $request_id
            ]);


            if ($stmt->rowCount() > 0) {

                $message =
                    'Administrator approved successfully.';

                $message_type =
                    'success';

            } else {

                $message =
                    'Unable to approve this administrator.';

                $message_type =
                    'error';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | REJECT ADMIN
        |--------------------------------------------------------------------------
        */

        if ($action === 'reject') {

            $stmt = db()->prepare(
                "UPDATE users
                 SET status = 'rejected'
                 WHERE id = ?
                 AND role = 'admin'
                 AND status = 'pending'"
            );

            $stmt->execute([
                $request_id
            ]);


            if ($stmt->rowCount() > 0) {

                $message =
                    'Administrator request rejected successfully.';

                $message_type =
                    'success';

            } else {

                $message =
                    'Unable to reject this administrator.';

                $message_type =
                    'error';
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| GET PENDING ADMINISTRATORS
|--------------------------------------------------------------------------
*/

$stmt = db()->prepare(
    "SELECT
        id,
        name,
        email,
        created_at,
        status
     FROM users
     WHERE role = 'admin'
     AND status = 'pending'
     ORDER BY created_at DESC"
);

$stmt->execute();

$pending_admins =
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
        Admin Requests - Goali Tours
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

            height: 70px;

            background: #1f2937;

            color: white;

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 0 30px;
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

            max-width: 1200px;

            margin: 40px auto;
        }


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


        .card-header {

            padding: 20px 25px;

            border-bottom:
                1px solid #e5e7eb;

            display: flex;

            justify-content:
                space-between;

            align-items: center;
        }


        .card-header h3 {

            margin: 0;

            font-size: 18px;

            color: #111827;
        }


        .pending-count {

            background: #fef3c7;

            color: #92400e;

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
        }


        td {

            padding: 17px 20px;

            border-bottom:
                1px solid #e5e7eb;

            font-size: 14px;

            color: #4b5563;
        }


        tr:hover {

            background: #f9fafb;
        }


        /*
        |--------------------------------------------------------------------------
        | STATUS
        |--------------------------------------------------------------------------
        */

        .status {

            display: inline-block;

            padding: 5px 10px;

            border-radius: 20px;

            background: #fef3c7;

            color: #92400e;

            font-size: 12px;

            font-weight: bold;
        }


        /*
        |--------------------------------------------------------------------------
        | ACTIONS
        |--------------------------------------------------------------------------
        */

        .actions {

            display: flex;

            gap: 8px;
        }


        .action-btn {

            border: none;

            padding: 8px 13px;

            border-radius: 5px;

            font-size: 13px;

            cursor: pointer;

            font-weight: bold;
        }


        .approve-btn {

            background: #16a34a;

            color: white;
        }


        .approve-btn:hover {

            background: #15803d;
        }


        .reject-btn {

            background: #dc2626;

            color: white;
        }


        .reject-btn:hover {

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

                padding: 0 15px;
            }


            .header h2 {

                font-size: 17px;
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

            <?= e($_SESSION['admin_name'] ?? 'Super Admin') ?>

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


    <div class="page-title">

        <h1>
            Admin Requests
        </h1>


        <p>
            Review and manage new administrator registration requests.
        </p>

    </div>


    <?php if ($message !== ''): ?>

        <div
            class="message
            <?= e($message_type) ?>"
        >

            <?= e($message) ?>

        </div>

    <?php endif; ?>


    <div class="card">


        <div class="card-header">


            <h3>
                Pending Admin Registrations
            </h3>


            <span class="pending-count">

                <?= count($pending_admins) ?>

                Pending

            </span>


        </div>


        <?php if (!empty($pending_admins)): ?>


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
                            $pending_admins
                            as $index => $admin
                        ): ?>


                            <tr>


                                <td>

                                    <?= $index + 1 ?>

                                </td>


                                <td>

                                    <strong>

                                        <?= e(
                                            $admin['name']
                                        ) ?>

                                    </strong>

                                </td>


                                <td>

                                    <?= e(
                                        $admin['email']
                                    ) ?>

                                </td>


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


                                <td>

                                    <span class="status">

                                        Pending

                                    </span>

                                </td>


                                <td>


                                    <div class="actions">


                                        <!--
                                        ------------------------------------------------
                                        APPROVE
                                        ------------------------------------------------
                                        -->

                                        <form
                                            method="POST"
                                            onsubmit="
                                                return confirm(
                                                    'Are you sure you want to approve this administrator?'
                                                );
                                            "
                                        >


                                            <input
                                                type="hidden"
                                                name="request_id"
                                                value="<?= (int)$admin['id'] ?>"
                                            >


                                            <input
                                                type="hidden"
                                                name="action"
                                                value="approve"
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
                                                class="action-btn approve-btn"
                                            >

                                                APPROVE

                                            </button>


                                        </form>


                                        <!--
                                        ------------------------------------------------
                                        REJECT
                                        ------------------------------------------------
                                        -->

                                        <form
                                            method="POST"
                                            onsubmit="
                                                return confirm(
                                                    'Are you sure you want to reject this administrator?'
                                                );
                                            "
                                        >


                                            <input
                                                type="hidden"
                                                name="request_id"
                                                value="<?= (int)$admin['id'] ?>"
                                            >


                                            <input
                                                type="hidden"
                                                name="action"
                                                value="reject"
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
                                                class="action-btn reject-btn"
                                            >

                                                REJECT

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


            <div class="empty">


                <div class="empty-icon">
                    ✓
                </div>


                <h3>
                    No Pending Requests
                </h3>


                <p>
                    There are currently no new admin registration requests.
                </p>


            </div>


        <?php endif; ?>


    </div>


    <div class="back-area">


        <a
            href="dashboard.php"
            class="back-btn"
        >

            ← Back to Dashboard

        </a>


    </div>


</main>


</body>

</html>