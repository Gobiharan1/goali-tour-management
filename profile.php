<?php

require_once __DIR__ . '/config/config.php';

require_login();


/*
|--------------------------------------------------------------------------
| Get Admin
|--------------------------------------------------------------------------
*/

$stmt = db()->prepare(
    'SELECT * FROM users WHERE id=?'
);

$stmt->execute([
    $_SESSION['admin_id']
]);

$user = $stmt->fetch();


/*
|--------------------------------------------------------------------------
| Messages
|--------------------------------------------------------------------------
*/

$message = '';

$error = '';


/*
|--------------------------------------------------------------------------
| Company Settings
|--------------------------------------------------------------------------
*/

$settingsStmt = db()->query(
    "SELECT *
     FROM company_settings
     WHERE id = 1
     LIMIT 1"
);

$settings = $settingsStmt->fetch();


if (!$settings) {

    db()->exec(
        "INSERT INTO company_settings
         (id, company_name, logo_path)
         VALUES
         (1, 'Goali Tours', NULL)"
    );

    $settings = [
        'id' => 1,
        'company_name' => 'Goali Tours',
        'logo_path' => null
    ];
}


/*
|--------------------------------------------------------------------------
| PROFILE UPDATE
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_profile'])
) {

    $name =
        trim($_POST['name'] ?? '');

    $email =
        trim($_POST['email'] ?? '');

    $password =
        $_POST['password'] ?? '';


    if ($name === '') {

        $error = 'Name is required.';

    } elseif ($email === '') {

        $error = 'Email is required.';

    } else {

        if ($password !== '') {

            $hash =
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

            db()->prepare(
                'UPDATE users
                 SET name=?,
                     email=?,
                     password_hash=?
                 WHERE id=?'
            )->execute([
                $name,
                $email,
                $hash,
                $user['id']
            ]);

        } else {

            db()->prepare(
                'UPDATE users
                 SET name=?,
                     email=?
                 WHERE id=?'
            )->execute([
                $name,
                $email,
                $user['id']
            ]);
        }


        $_SESSION['admin_name'] = $name;

        $message =
            'Profile updated successfully.';

        $user['name'] = $name;

        $user['email'] = $email;
    }
}


/*
|--------------------------------------------------------------------------
| COMPANY LOGO UPLOAD
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['upload_logo'])
) {

    if (
        !isset($_FILES['company_logo']) ||
        $_FILES['company_logo']['error'] !== UPLOAD_ERR_OK
    ) {

        $error =
            'Please select a valid logo image.';

    } else {

        $file =
            $_FILES['company_logo'];


        /*
        |--------------------------------------------------------------------------
        | Maximum file size
        |--------------------------------------------------------------------------
        */

        $maxSize = 5 * 1024 * 1024;


        if ($file['size'] > $maxSize) {

            $error =
                'Logo must be smaller than 5 MB.';

        } else {

            /*
            |--------------------------------------------------------------------------
            | Check MIME type
            |--------------------------------------------------------------------------
            */

            $finfo =
                new finfo(FILEINFO_MIME_TYPE);

            $mime =
                $finfo->file(
                    $file['tmp_name']
                );


            $allowedTypes = [

                'image/png' =>
                    'png',

                'image/jpeg' =>
                    'jpg',

                'image/webp' =>
                    'webp'

            ];


            if (
                !isset(
                    $allowedTypes[$mime]
                )
            ) {

                $error =
                    'Only PNG, JPG/JPEG, and WebP images are allowed.';

            } else {

                /*
                |--------------------------------------------------------------------------
                | Upload directory
                |--------------------------------------------------------------------------
                */

                $uploadDir =
                    __DIR__ .
                    '/assets/uploads/';


                if (
                    !is_dir($uploadDir)
                ) {

                    mkdir(
                        $uploadDir,
                        0755,
                        true
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | Delete old logo
                |--------------------------------------------------------------------------
                */

                if (
                    !empty(
                        $settings['logo_path']
                    )
                ) {

                    $oldFile =
                        __DIR__ .
                        '/' .
                        ltrim(
                            $settings['logo_path'],
                            '/'
                        );


                    if (
                        is_file($oldFile)
                    ) {

                        @unlink(
                            $oldFile
                        );
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | New filename
                |--------------------------------------------------------------------------
                */

                $extension =
                    $allowedTypes[$mime];


                $filename =
                    'company_logo_' .
                    time() .
                    '.' .
                    $extension;


                $destination =
                    $uploadDir .
                    $filename;


                /*
                |--------------------------------------------------------------------------
                | Move file
                |--------------------------------------------------------------------------
                */

                if (
                    move_uploaded_file(
                        $file['tmp_name'],
                        $destination
                    )
                ) {

                    $logoPath =
                        'assets/uploads/' .
                        $filename;


                    db()->prepare(
                        "UPDATE company_settings
                         SET logo_path=?
                         WHERE id=1"
                    )->execute([
                        $logoPath
                    ]);


                    $settings['logo_path'] =
                        $logoPath;


                    $message =
                        'Company logo uploaded successfully.';

                } else {

                    $error =
                        'Unable to save the logo. Check folder permissions.';
                }
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| REMOVE LOGO
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['remove_logo'])
) {

    if (
        !empty(
            $settings['logo_path']
        )
    ) {

        $oldFile =
            __DIR__ .
            '/' .
            ltrim(
                $settings['logo_path'],
                '/'
            );


        if (
            is_file($oldFile)
        ) {

            @unlink(
                $oldFile
            );
        }
    }


    db()->prepare(
        "UPDATE company_settings
         SET logo_path=NULL
         WHERE id=1"
    )->execute();


    $settings['logo_path'] =
        null;


    $message =
        'Company logo removed.';
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
Admin Profile — Goali Tours
</title>

<link
    rel="stylesheet"
    href="assets/css/style.css"
>

<style>

.logo-section {

    margin-top: 30px;

}

.logo-preview {

    width: 260px;

    min-height: 120px;

    padding: 20px;

    border: 1px solid #ddd;

    border-radius: 10px;

    background: #fafafa;

    display: flex;

    align-items: center;

    justify-content: center;

    margin-bottom: 20px;

}

.logo-preview img {

    max-width: 220px;

    max-height: 100px;

    object-fit: contain;

}

.no-logo {

    color: #777;

    font-size: 14px;

}

.logo-actions {

    display: flex;

    gap: 10px;

    align-items: center;

    flex-wrap: wrap;

}

.file-input {

    padding: 10px;

    border: 1px solid #ddd;

    border-radius: 6px;

    background: white;

}

.logo-help {

    margin-top: 10px;

    color: #777;

    font-size: 13px;

}

</style>

</head>


<body>


<div class="form-shell">


    <!-- HEADER -->

    <div class="form-header">

        <div>

            <a
                href="dashboard.php"
                class="back"
            >
                ← Dashboard
            </a>

            <h1>
                Admin Profile
            </h1>

        </div>

    </div>


    <!-- MESSAGE -->

    <?php if ($message): ?>

        <div class="alert success">

            <?= e($message) ?>

        </div>

    <?php endif; ?>


    <!-- ERROR -->

    <?php if ($error): ?>

        <div
            class="alert"
            style="background:#ffe5e5;color:#a00;"
        >

            <?= e($error) ?>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         ADMIN PROFILE
    ====================================================== -->

    <form
        method="post"
        class="form-section"
    >

        <input
            type="hidden"
            name="update_profile"
            value="1"
        >

        <h2>
            Admin Account
        </h2>


        <div class="form-grid">


            <div class="field">

                <label>
                    Name
                </label>

                <input
                    name="name"
                    value="<?= e($user['name']) ?>"
                    required
                >

            </div>


            <div class="field">

                <label>
                    Email
                </label>

                <input
                    type="email"
                    name="email"
                    value="<?= e($user['email']) ?>"
                    required
                >

            </div>


            <div class="field full">

                <label>
                    New Password
                </label>

                <input
                    type="password"
                    name="password"
                    placeholder="Leave blank to keep current password"
                >

            </div>


        </div>


        <button
            class="btn primary"
            type="submit"
        >
            Update Profile
        </button>

    </form>


    <!-- =====================================================
         COMPANY LOGO
    ====================================================== -->

    <section class="form-section logo-section">

        <h2>
            Company Logo
        </h2>


        <p class="muted">

            Upload the Goali Tours company logo.
            This logo will automatically appear
            on the dashboard and generated PDF packages.

        </p>


        <!-- LOGO PREVIEW -->

        <div class="logo-preview">

            <?php if (
                !empty(
                    $settings['logo_path']
                )
            ): ?>

                <img
                    src="<?= e($settings['logo_path']) ?>"
                    alt="Company Logo"
                >

            <?php else: ?>

                <div class="no-logo">

                    No company logo uploaded.

                </div>

            <?php endif; ?>

        </div>


        <!-- UPLOAD -->

        <form
            method="post"
            enctype="multipart/form-data"
        >

            <input
                type="hidden"
                name="upload_logo"
                value="1"
            >


            <div class="logo-actions">

                <input
                    type="file"
                    name="company_logo"
                    class="file-input"
                    accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp"
                    required
                >


                <button
                    class="btn primary"
                    type="submit"
                >
                    Upload Logo
                </button>

            </div>


            <div class="logo-help">

                Recommended: PNG with transparent background.
                Maximum file size: 5 MB.

            </div>

        </form>


        <!-- REMOVE -->

        <?php if (
            !empty(
                $settings['logo_path']
            )
        ): ?>

            <form
                method="post"
                style="margin-top:15px;"
                onsubmit="return confirm('Remove the company logo?');"
            >

                <input
                    type="hidden"
                    name="remove_logo"
                    value="1"
                >


                <button
                    class="btn danger-outline"
                    type="submit"
                >
                    Remove Logo
                </button>

            </form>

        <?php endif; ?>


    </section>


</div>

</body>

</html>