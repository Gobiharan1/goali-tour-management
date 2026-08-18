<?php

declare(strict_types=1);

session_start();


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

const DB_HOST_DEFAULT = '127.0.0.1';
const DB_NAME_DEFAULT = 'goali_tour_management';
const DB_USER_DEFAULT = 'root';
const DB_PASS_DEFAULT = '';


/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
|--------------------------------------------------------------------------
*/

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {

        $pdo = new PDO(
            'mysql:host=' . (getenv('GOALI_DB_HOST') ?: DB_HOST_DEFAULT) .
            ';dbname=' . (getenv('GOALI_DB_NAME') ?: DB_NAME_DEFAULT) .
            ';charset=utf8mb4',

            getenv('GOALI_DB_USER') ?: DB_USER_DEFAULT,
            getenv('GOALI_DB_PASS') !== false ? getenv('GOALI_DB_PASS') : DB_PASS_DEFAULT,

            [
                PDO::ATTR_ERRMODE =>
                    PDO::ERRMODE_EXCEPTION,

                PDO::ATTR_DEFAULT_FETCH_MODE =>
                    PDO::FETCH_ASSOC,

                PDO::ATTR_EMULATE_PREPARES =>
                    false,
            ]
        );
    }

    return $pdo;
}


/*
|--------------------------------------------------------------------------
| REQUIRE LOGIN
|--------------------------------------------------------------------------
|
| This function checks whether the user is logged in.
|
*/

function require_login(): void
{
    if (empty($_SESSION['admin_id'])) {

        header('Location: login.php');

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK CURRENT ACCOUNT STATUS
    |--------------------------------------------------------------------------
    |
    | We check the database instead of trusting only the session.
    |
    */

    $adminId = (int)$_SESSION['admin_id'];

    $stmt = db()->prepare(
        "SELECT id, name, email, role, status
         FROM users
         WHERE id = ?
         LIMIT 1"
    );

    $stmt->execute([$adminId]);

    $user = $stmt->fetch();


    /*
    |--------------------------------------------------------------------------
    | USER DOES NOT EXIST
    |--------------------------------------------------------------------------
    */

    if (!$user) {

        session_unset();
        session_destroy();

        header('Location: login.php');

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | USER IS NOT ACTIVE
    |--------------------------------------------------------------------------
    |
    | Only active accounts are allowed to use the system.
    |
    */

    if ($user['status'] !== 'active') {

        session_unset();
        session_destroy();

        header('Location: login.php');

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE SESSION INFORMATION
    |--------------------------------------------------------------------------
    */

    $_SESSION['admin_id'] =
        (int)$user['id'];

    $_SESSION['admin_name'] =
        $user['name'];

    $_SESSION['admin_email'] =
        $user['email'];

    $_SESSION['admin_role'] =
        $user['role'];

    $_SESSION['admin_status'] =
        $user['status'];
}


/*
|--------------------------------------------------------------------------
| REQUIRE SUPER ADMIN
|--------------------------------------------------------------------------
|
| This function allows access only to an active Super Admin.
|
| Use this at the top of pages such as:
|
| admin_requests.php
| manage_admins.php
|
*/

function require_super_admin(): void
{
    require_login();


    $adminId = (int)($_SESSION['admin_id'] ?? 0);


    if ($adminId <= 0) {

        header('Location: login.php');

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK ROLE DIRECTLY FROM DATABASE
    |--------------------------------------------------------------------------
    */

    $stmt = db()->prepare(
        "SELECT id, role, status
         FROM users
         WHERE id = ?
         LIMIT 1"
    );

    $stmt->execute([$adminId]);

    $user = $stmt->fetch();


    /*
    |--------------------------------------------------------------------------
    | CHECK SUPER ADMIN
    |--------------------------------------------------------------------------
    */

    if (
        !$user ||
        $user['role'] !== 'super_admin' ||
        $user['status'] !== 'active'
    ) {

        http_response_code(403);

        exit('Access Denied');
    }
}


/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
|
| Used to protect actions such as:
|
| Approve Admin
| Reject Admin
| Deactivate Admin
| Restore Tour
| Permanently Delete Tour
|
*/

function csrf_token(): string
{
    if (
        empty($_SESSION['csrf_token'])
    ) {

        $_SESSION['csrf_token'] =
            bin2hex(
                random_bytes(32)
            );
    }


    return $_SESSION['csrf_token'];
}


/*
|--------------------------------------------------------------------------
| VERIFY CSRF TOKEN
|--------------------------------------------------------------------------
*/

function verify_csrf(): void
{
    $token =
        $_POST['csrf_token']
        ?? '';


    if (
        empty($token) ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals(
            $_SESSION['csrf_token'],
            $token
        )
    ) {

        http_response_code(419);

        exit(
            'Invalid security token. Please go back and try again.'
        );
    }
}


/*
|--------------------------------------------------------------------------
| HTML ESCAPE
|--------------------------------------------------------------------------
*/

function e(?string $value): string
{
    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES,
        'UTF-8'
    );
}


/*
|--------------------------------------------------------------------------
| PACKAGE ID
|--------------------------------------------------------------------------
*/

function package_id(): string
{
    return 'GT-' .
        date('Ymd') .
        '-' .
        strtoupper(
            bin2hex(
                random_bytes(3)
            )
        );
}


/*
|--------------------------------------------------------------------------
| COMPANY NAME
|--------------------------------------------------------------------------
*/

function company_name(): string
{
    try {

        $stmt = db()->query(
            "SELECT company_name
             FROM company_settings
             WHERE id = 1
             LIMIT 1"
        );

        $result = $stmt->fetch();


        if (
            $result &&
            !empty($result['company_name'])
        ) {

            return $result['company_name'];
        }

    } catch (Throwable $e) {

        /*
        |--------------------------------------------------------------------------
        | KEEP SYSTEM WORKING
        |--------------------------------------------------------------------------
        |
        | If the company_settings table is unavailable,
        | use the default company name.
        |
        */
    }


    return 'Goali Tours';
}


/*
|--------------------------------------------------------------------------
| COMPANY LOGO
|--------------------------------------------------------------------------
|
| Returns the web path to the uploaded company logo.
|
*/

function company_logo(): string
{
    try {

        $stmt = db()->query(
            "SELECT logo_path
             FROM company_settings
             WHERE id = 1
             LIMIT 1"
        );

        $result = $stmt->fetch();


        if (
            $result &&
            !empty($result['logo_path'])
        ) {

            $file =
                __DIR__ .
                '/../' .
                ltrim(
                    $result['logo_path'],
                    '/'
                );


            if (is_file($file)) {

                return $result['logo_path'];
            }
        }

    } catch (Throwable $e) {

        /*
        |--------------------------------------------------------------------------
        | KEEP SYSTEM WORKING
        |--------------------------------------------------------------------------
        */
    }


    return '';
}
