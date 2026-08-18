<?php

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';


/*
|--------------------------------------------------------------------------
| Redirect already logged-in users
|--------------------------------------------------------------------------
*/

if (!empty($_SESSION['admin_id'])) {

    header('Location: dashboard.php');

    exit;
}


$error = '';
$success = '';

$name = '';
$email = '';


/*
|--------------------------------------------------------------------------
| Registration
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name =
        trim($_POST['name'] ?? '');

    $email =
        trim($_POST['email'] ?? '');

    $password =
        $_POST['password'] ?? '';

    $confirm_password =
        $_POST['confirm_password'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if ($name === '') {

        $error =
            'Please enter your full name.';

    } elseif ($email === '') {

        $error =
            'Please enter your email address.';

    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error =
            'Please enter a valid email address.';

    } elseif ($password === '') {

        $error =
            'Please enter a password.';

    } elseif (strlen($password) < 8) {

        $error =
            'Password must contain at least 8 characters.';

    } elseif (
        $password !== $confirm_password
    ) {

        $error =
            'Passwords do not match.';

    } else {


        /*
        |--------------------------------------------------------------------------
        | Check Existing Email
        |--------------------------------------------------------------------------
        */

        $stmt = db()->prepare(
            'SELECT id
             FROM users
             WHERE email = ?
             LIMIT 1'
        );

        $stmt->execute([
            $email
        ]);

        $existingUser =
            $stmt->fetch();


        if ($existingUser) {

            $error =
                'An account with this email already exists.';

        } else {


            /*
            |--------------------------------------------------------------------------
            | Generate Admin ID
            |--------------------------------------------------------------------------
            |
            | Example:
            |
            | GT-ADM-0001
            | GT-ADM-0002
            | GT-ADM-0003
            |
            |--------------------------------------------------------------------------
            */

            $stmt = db()->query(
                "SELECT MAX(id) AS max_id
                 FROM users"
            );

            $result =
                $stmt->fetch();


            $nextId =
                ((int)($result['max_id'] ?? 0)) + 1;


            $adminCode =
                'GT-ADM-' .
                str_pad(
                    (string)$nextId,
                    4,
                    '0',
                    STR_PAD_LEFT
                );


            /*
            |--------------------------------------------------------------------------
            | Password Hash
            |--------------------------------------------------------------------------
            */

            $passwordHash =
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


            /*
            |--------------------------------------------------------------------------
            | Create Pending Admin
            |--------------------------------------------------------------------------
            */

            $stmt = db()->prepare(
                'INSERT INTO users
                (
                    admin_code,
                    name,
                    email,
                    password_hash,
                    role,
                    status
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )'
            );


            $stmt->execute([

                $adminCode,

                $name,

                $email,

                $passwordHash,

                'admin',

                'pending'

            ]);


            /*
            |--------------------------------------------------------------------------
            | Success
            |--------------------------------------------------------------------------
            */

            $success =
                'Registration successful. ' .
                'Your Admin ID is ' .
                $adminCode .
                '. ' .
                'Please wait for approval from a Super Administrator.';


            $name = '';

            $email = '';
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
    content="width=device-width,initial-scale=1"
>

<title>
    Goali Tours — Admin Registration
</title>

<link
    rel="stylesheet"
    href="assets/css/style.css"
>

<style>

/*
|--------------------------------------------------------------------------
| Password Field
|--------------------------------------------------------------------------
*/

.password-wrapper {

    position: relative;

    width: 100%;

}


.password-wrapper input {

    width: 100%;

    padding-right: 45px;

    box-sizing: border-box;

}


.password-toggle {

    position: absolute;

    right: 12px;

    top: 50%;

    transform: translateY(-50%);

    border: none;

    background: transparent;

    cursor: pointer;

    font-size: 18px;

    padding: 4px;

}


/*
|--------------------------------------------------------------------------
| Success Admin ID
|--------------------------------------------------------------------------
*/

.admin-id-box {

    margin-top: 15px;

    padding: 15px;

    background: #f5f5f5;

    border-radius: 8px;

    text-align: center;

}


.admin-id-box strong {

    display: block;

    font-size: 20px;

    margin-top: 5px;

}

</style>

</head>


<body class="login-page">


<div class="login-card">


    <div class="brand-mark">
        GOALI TOURS
    </div>


    <h1>
        Admin Registration
    </h1>


    <p class="muted">
        Create an administrator account
    </p>


    <?php if ($error): ?>

        <div class="alert error">

            <?= e($error) ?>

        </div>

    <?php endif; ?>


    <?php if ($success): ?>

        <div class="alert success">

            <?= e($success) ?>

        </div>


        <div class="admin-id-box">

            Your Admin ID

            <strong>
                <?= e(
                    preg_match(
                        '/GT-ADM-\d{4}/',
                        $success,
                        $matches
                    )
                    ? $matches[0]
                    : ''
                ) ?>
            </strong>

        </div>


        <p
            style="
                text-align:center;
                margin-top:20px;
            "
        >

            <a href="login.php">
                Return to Login
            </a>

        </p>


    <?php else: ?>


        <form
            method="post"
            autocomplete="off"
        >


            <label>
                Full Name
            </label>


            <input
                type="text"
                name="name"
                value="<?= e($name) ?>"
                required
                autocomplete="name"
            >


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


            <div class="password-wrapper">

                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    minlength="8"
                    autocomplete="new-password"
                >

                <button
                    type="button"
                    class="password-toggle"
                    onclick="togglePassword('password', this)"
                    aria-label="Show password"
                >
                    👁
                </button>

            </div>


            <label>
                Confirm Password
            </label>


            <div class="password-wrapper">

                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    required
                    minlength="8"
                    autocomplete="new-password"
                >

                <button
                    type="button"
                    class="password-toggle"
                    onclick="togglePassword('confirm_password', this)"
                    aria-label="Show password"
                >
                    👁
                </button>

            </div>


            <button
                class="btn primary full"
                type="submit"
            >
                Create Admin Account
            </button>


        </form>


        <p
            style="
                text-align:center;
                margin-top:20px;
            "
        >

            Already have an account?

            <a href="login.php">
                Sign In
            </a>

        </p>


    <?php endif; ?>

</div>


<script>

/*
|--------------------------------------------------------------------------
| Show / Hide Password
|--------------------------------------------------------------------------
*/

function togglePassword(
    inputId,
    button
) {

    const input =
        document.getElementById(
            inputId
        );


    if (
        input.type === 'password'
    ) {

        input.type =
            'text';

        button.textContent =
            '🙈';

        button.setAttribute(
            'aria-label',
            'Hide password'
        );

    } else {

        input.type =
            'password';

        button.textContent =
            '👁';

        button.setAttribute(
            'aria-label',
            'Show password'
        );
    }
}

</script>


</body>

</html>