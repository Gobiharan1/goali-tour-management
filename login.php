<?php

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';


/*
|--------------------------------------------------------------------------
| Already logged in
|--------------------------------------------------------------------------
*/

if (!empty($_SESSION['admin_id'])) {

    header('Location: dashboard.php');

    exit;
}


$error = '';

$email = '';


/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | Find User
    |--------------------------------------------------------------------------
    */

    $stmt = db()->prepare(
        'SELECT *
         FROM users
         WHERE email = ?
         LIMIT 1'
    );

    $stmt->execute([$email]);

    $user = $stmt->fetch();


    /*
    |--------------------------------------------------------------------------
    | Check credentials
    |--------------------------------------------------------------------------
    */

    if (
        !$user ||
        !password_verify(
            $password,
            $user['password_hash']
        )
    ) {

        $error = 'Invalid email or password.';

    } else {

        /*
        |--------------------------------------------------------------------------
        | Check Account Status
        |--------------------------------------------------------------------------
        */

        if ($user['status'] === 'pending') {

            $error =
                'Your account is waiting for approval ' .
                'from a Super Administrator.';

        } elseif ($user['status'] === 'rejected') {

            $error =
                'Your administrator registration has been rejected.';

        } elseif ($user['status'] === 'inactive') {

            $error =
                'Your administrator account is currently inactive.';

        } elseif ($user['status'] !== 'active') {

            $error =
                'Your account cannot be used at this time.';

        } else {

            /*
            |--------------------------------------------------------------------------
            | Successful Login
            |--------------------------------------------------------------------------
            */

            session_regenerate_id(true);

            $_SESSION['admin_id'] = $user['id'];

            $_SESSION['admin_name'] = $user['name'];

            $_SESSION['admin_email'] = $user['email'];

            $_SESSION['admin_role'] = $user['role'];


            /*
            |--------------------------------------------------------------------------
            | Update Last Login
            |--------------------------------------------------------------------------
            */

            $update = db()->prepare(
                'UPDATE users
                 SET last_login = NOW()
                 WHERE id = ?'
            );

            $update->execute([
                $user['id']
            ]);


            header('Location: dashboard.php');

            exit;
        }
    }
}

?>

<!doctype html>

<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1"
>

<title>Goali Tours — Admin Login</title>

<link
    rel="stylesheet"
    href="assets/css/style.css"
>


<style>

/*
|--------------------------------------------------------------------------
| Password Box
|--------------------------------------------------------------------------
*/

.password-box {
    position: relative;
    width: 100%;
}

.password-box input {
    width: 100% !important;
    padding-right: 75px !important;
    box-sizing: border-box !important;
}


/*
|--------------------------------------------------------------------------
| Show / Hide Button
|--------------------------------------------------------------------------
*/

.password-button {
    position: absolute !important;

    right: 8px !important;

    top: 50% !important;

    transform: translateY(-50%) !important;

    width: auto !important;

    min-width: 50px !important;

    height: 30px !important;

    padding: 2px 6px !important;

    margin: 0 !important;

    border: none !important;

    background: transparent !important;

    color: #555 !important;

    font-size: 12px !important;

    font-weight: bold !important;

    cursor: pointer !important;

    box-shadow: none !important;
}

.password-button:hover {
    color: #000 !important;
}

</style>

</head>


<body class="login-page">

<div class="login-card">

    <div class="brand-mark">
        GOALI TOURS
    </div>


    <h1>
        Admin Login
    </h1>


    <p class="muted">
        Tour Management System
    </p>


    <?php if ($error): ?>

        <div class="alert error">
            <?= e($error) ?>
        </div>

    <?php endif; ?>


    <form method="post">

        <label>
            Email
        </label>

        <input
            type="email"
            name="email"
            value="<?= e($email) ?>"
            required
            autocomplete="email"
        >


        <label>
            Password
        </label>


        <div class="password-box">

            <input
                type="password"
                name="password"
                id="loginPassword"
                required
                autocomplete="current-password"
            >


            <button
                type="button"
                id="passwordButton"
                class="password-button"
                onclick="toggleLoginPassword()"
            >
                SHOW
            </button>

        </div>


        <button
            class="btn primary full"
            type="submit"
        >
            Sign In
        </button>

    </form>


    <p style="text-align:center; margin-top:20px;">

        Don't have an admin account?

        <a href="register.php">
            Register Now
        </a>

    </p>

</div>


<script>

/*
|--------------------------------------------------------------------------
| Show / Hide Login Password
|--------------------------------------------------------------------------
*/

function toggleLoginPassword() {

    const passwordInput =
        document.getElementById('loginPassword');

    const passwordButton =
        document.getElementById('passwordButton');


    if (passwordInput.type === 'password') {

        passwordInput.type = 'text';

        passwordButton.textContent = 'HIDE';

    } else {

        passwordInput.type = 'password';

        passwordButton.textContent = 'SHOW';

    }

}

</script>


</body>

</html>