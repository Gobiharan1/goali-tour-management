<?php

require_once __DIR__ . '/config/config.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$category_id = (int)($_GET['category_id'] ?? 0);

$categories = db()->query(
    'SELECT * FROM categories ORDER BY name'
)->fetchAll();


/*
|--------------------------------------------------------------------------
| Upload Directory
|--------------------------------------------------------------------------
*/

$uploadDir = __DIR__ . '/assets/uploads/tours/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}


/*
|--------------------------------------------------------------------------
| Helper Functions
|--------------------------------------------------------------------------
*/

function uploadTourImage(string $field, string $uploadDir): ?string
{
    if (
        !isset($_FILES[$field]) ||
        $_FILES[$field]['error'] !== UPLOAD_ERR_OK
    ) {
        return null;
    }

    $allowed = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp'
    ];

    $extension = strtolower(
        pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION)
    );

    if (!isset($allowed[$extension])) {
        return null;
    }

    $imageInfo = @getimagesize($_FILES[$field]['tmp_name']);

    if (!$imageInfo) {
        return null;
    }

    $filename =
        'tour_' .
        date('Ymd_His') .
        '_' .
        bin2hex(random_bytes(5)) .
        '.' .
        $extension;

    $destination = $uploadDir . $filename;

    if (
        move_uploaded_file(
            $_FILES[$field]['tmp_name'],
            $destination
        )
    ) {
        return 'assets/uploads/tours/' . $filename;
    }

    return null;
}


/*
|--------------------------------------------------------------------------
| Daily Package ID Lock Helpers
|--------------------------------------------------------------------------
|
| Package ID format:
| GT-YYYYMMDD-0001
|
| Example:
| GT-20260813-0001
| GT-20260813-0002
| GT-20260814-0001
|
| A MySQL named lock prevents two administrators from receiving the
| same package number when packages are created at the same time.
|
*/
function acquireDailyPackageLock(PDO $db): array
{
    $date = date('Ymd');
    $prefix = 'GT-' . $date . '-';
    $lockName = 'goali_tours_package_' . $date;

    $lockStmt = $db->prepare('SELECT GET_LOCK(?, 10)');
    $lockStmt->execute([$lockName]);

    if ((int)$lockStmt->fetchColumn() !== 1) {
        throw new RuntimeException(
            'Unable to generate the Package ID right now. Please try again.'
        );
    }

    return [
        'prefix' => $prefix,
        'lock' => $lockName
    ];
}

function releaseDailyPackageLock(PDO $db, string $lockName): void
{
    $releaseStmt = $db->prepare('SELECT RELEASE_LOCK(?)');
    $releaseStmt->execute([$lockName]);
}


/*
|--------------------------------------------------------------------------
| Default Tour Data
|--------------------------------------------------------------------------
*/

$tour = [

    'id' => 0,

    'category_id' => $category_id,

    'package_id' => '',

    'tour_name' => '',

    'customer_name' => '',

    'customer_details' => '',

    'duration_days' => 10,

    'duration_nights' => 9,

    'activity_level' => 'Intermediate',

    'locations' => '',

    'day_plans' => '',

    'map_url' => '',

    'highlights' => '',

    'inclusions' => '',

    'exclusions' => '',

    'important_notes' => '',

    'images' => '',

    'gallery' => '',

    'custom_field' => '',

    'source_tour_id' => null
];


/*
|--------------------------------------------------------------------------
| Load Existing Tour
|--------------------------------------------------------------------------
*/

if ($id) {

    $stmt = db()->prepare(
        "SELECT *
         FROM tours
         WHERE id = ?
         AND status = 'active'"
    );

    $stmt->execute([$id]);

    $found = $stmt->fetch();

    if (!$found) {
        die('Tour not found.');
    }

    $tour = $found;
}


/*
|--------------------------------------------------------------------------
| Existing Itinerary JSON
|--------------------------------------------------------------------------
*/

$existingDays = [];

if (!empty($tour['day_plans'])) {

    $decoded = json_decode(
        $tour['day_plans'],
        true
    );

    if (
        is_array($decoded) &&
        isset($decoded[0]) &&
        is_array($decoded[0])
    ) {

        $existingDays = $decoded;

    } else {

        /*
        |--------------------------------------------------------------------------
        | Backward compatibility with old text-based itinerary
        |--------------------------------------------------------------------------
        */

        $oldDays = preg_split(
            '/\R/',
            $tour['day_plans']
        );

        foreach ($oldDays as $index => $oldDay) {

            $oldDay = trim($oldDay);

            if ($oldDay === '') {
                continue;
            }

            $existingDays[] = [

                'day' =>
                    $index + 1,

                'title' =>
                    'Day ' . ($index + 1),

                'details' =>
                    $oldDay,

                'image' =>
                    ''
            ];
        }
    }
}


/*
|--------------------------------------------------------------------------
| Existing Gallery
|--------------------------------------------------------------------------
*/

$existingGallery = [];

if (!empty($tour['gallery'])) {

    $decodedGallery = json_decode(
        $tour['gallery'],
        true
    );

    if (is_array($decodedGallery)) {
        $existingGallery = $decodedGallery;
    }
}


/*
|--------------------------------------------------------------------------
| Process Form Submission
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    /*
    |--------------------------------------------------------------------------
    | MAP
    |--------------------------------------------------------------------------
    */

    $map_input = trim(
        $_POST['map_url'] ?? ''
    );

    $map_url = $map_input;


    if (
        stripos(
            $map_input,
            '<iframe'
        ) !== false
    ) {

        if (
            preg_match(
                '/src=["\']([^"\']+)["\']/i',
                $map_input,
                $matches
            )
        ) {

            $map_url =
                trim($matches[1]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | LOCATIONS
    |--------------------------------------------------------------------------
    */

    $locationsArray = [];

    if (
        isset($_POST['locations']) &&
        is_array($_POST['locations'])
    ) {

        foreach (
            $_POST['locations']
            as $location
        ) {

            $location = trim($location);

            if ($location !== '') {

                $locationsArray[] =
                    $location;
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Save locations as one line per location
    |--------------------------------------------------------------------------
    */

    $locationsText =
        implode(
            PHP_EOL,
            $locationsArray
        );


    /*
    |--------------------------------------------------------------------------
    | ITINERARY
    |--------------------------------------------------------------------------
    */

    $dayPlans = [];

    $submittedDays =
        $_POST['day_plans'] ?? [];

    if (is_array($submittedDays)) {

        foreach (
            $submittedDays
            as $index => $day
        ) {

            $dayNumber =
                $index + 1;

            $title =
                trim(
                    $day['title'] ?? ''
                );

            $details =
                trim(
                    $day['details'] ?? ''
                );

            $image = '';


            /*
            |--------------------------------------------------------------------------
            | Existing image
            |--------------------------------------------------------------------------
            */

            if (
                isset($day['existing_image'])
            ) {

                $image =
                    trim(
                        $day['existing_image']
                    );
            }


            /*
            |--------------------------------------------------------------------------
            | New image upload
            |--------------------------------------------------------------------------
            */

            if (
                isset($_FILES['day_plans']['name'][$index]['image']) &&
                $_FILES['day_plans']['error'][$index]['image']
                    === UPLOAD_ERR_OK
            ) {

                $file = [

                    'name' =>
                        $_FILES['day_plans']['name'][$index]['image'],

                    'type' =>
                        $_FILES['day_plans']['type'][$index]['image'],

                    'tmp_name' =>
                        $_FILES['day_plans']['tmp_name'][$index]['image'],

                    'error' =>
                        $_FILES['day_plans']['error'][$index]['image'],

                    'size' =>
                        $_FILES['day_plans']['size'][$index]['image']
                ];


                $allowed = [
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'webp' => 'image/webp'
                ];


                $extension =
                    strtolower(
                        pathinfo(
                            $file['name'],
                            PATHINFO_EXTENSION
                        )
                    );


                if (
                    isset($allowed[$extension]) &&
                    @getimagesize(
                        $file['tmp_name']
                    )
                ) {

                    $filename =
                        'day_' .
                        $dayNumber .
                        '_' .
                        date('Ymd_His') .
                        '_' .
                        bin2hex(
                            random_bytes(4)
                        ) .
                        '.' .
                        $extension;


                    $destination =
                        $uploadDir .
                        $filename;


                    if (
                        move_uploaded_file(
                            $file['tmp_name'],
                            $destination
                        )
                    ) {

                        $image =
                            'assets/uploads/tours/' .
                            $filename;
                    }
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Save Day
            |--------------------------------------------------------------------------
            */

            if (
                $title !== '' ||
                $details !== '' ||
                $image !== ''
            ) {

                $dayPlans[] = [

                    'day' =>
                        $dayNumber,

                    'title' =>
                        $title !== ''
                        ? $title
                        : 'Day ' . $dayNumber,

                    'details' =>
                        $details,

                    'image' =>
                        $image
                ];
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | GALLERY
    |--------------------------------------------------------------------------
    */

    $gallery =
        $existingGallery;


    /*
    |--------------------------------------------------------------------------
    | Delete Gallery Images Selected By User
    |--------------------------------------------------------------------------
    */

    $deleteGallery =
        $_POST['delete_gallery'] ?? [];

    if (
        is_array($deleteGallery)
    ) {

        $gallery =
            array_values(
                array_filter(
                    $gallery,
                    function ($image) use (
                        $deleteGallery
                    ) {

                        return !in_array(
                            $image,
                            $deleteGallery,
                            true
                        );
                    }
                )
            );
    }


    /*
    |--------------------------------------------------------------------------
    | New Gallery Uploads
    |--------------------------------------------------------------------------
    */

    if (
        isset($_FILES['gallery_files']) &&
        is_array(
            $_FILES['gallery_files']['name']
        )
    ) {

        foreach (
            $_FILES['gallery_files']['name']
            as $index => $name
        ) {

            if (
                $_FILES['gallery_files']['error'][$index]
                !== UPLOAD_ERR_OK
            ) {
                continue;
            }


            $tmpName =
                $_FILES['gallery_files']['tmp_name'][$index];


            $extension =
                strtolower(
                    pathinfo(
                        $name,
                        PATHINFO_EXTENSION
                    )
                );


            $allowed = [
                'jpg',
                'jpeg',
                'png',
                'webp'
            ];


            if (
                !in_array(
                    $extension,
                    $allowed,
                    true
                )
            ) {
                continue;
            }


            if (!@getimagesize($tmpName)) {
                continue;
            }


            $filename =
                'gallery_' .
                date('Ymd_His') .
                '_' .
                bin2hex(
                    random_bytes(5)
                ) .
                '.' .
                $extension;


            $destination =
                $uploadDir .
                $filename;


            if (
                move_uploaded_file(
                    $tmpName,
                    $destination
                )
            ) {

                $gallery[] =
                    'assets/uploads/tours/' .
                    $filename;
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | FORM DATA
    |--------------------------------------------------------------------------
    */

    $data = [

        'category_id' =>
            (int)(
                $_POST['category_id'] ?? 0
            ),

        'tour_name' =>
            trim(
                $_POST['tour_name'] ?? ''
            ),

        'customer_name' =>
            trim(
                $_POST['customer_name'] ?? ''
            ),

        'customer_details' =>
            trim(
                $_POST['customer_details'] ?? ''
            ),

        'duration_days' =>
            (int)(
                $_POST['duration_days'] ?? 0
            ),

        'duration_nights' =>
            max(0, (int)($_POST['duration_days'] ?? 1) - 1),

        'activity_level' =>
            $_POST['activity_level']
            ?? 'Intermediate',

        'locations' =>
            $locationsText,

        'day_plans' =>
            json_encode(
                $dayPlans,
                JSON_UNESCAPED_UNICODE
            ),

        'map_url' =>
            $map_url,

        'highlights' =>
            trim(
                $_POST['highlights'] ?? ''
            ),

        'inclusions' =>
            trim(
                $_POST['inclusions'] ?? ''
            ),

        'exclusions' =>
            trim(
                $_POST['exclusions'] ?? ''
            ),

        'price_currency' =>
            in_array(
                $_POST['price_currency'] ?? 'LKR',
                ['LKR', 'USD', 'GBP', 'EUR'],
                true
            ) ? $_POST['price_currency'] : 'LKR',

        'price_amount' =>
            max(0, (float)($_POST['price_amount'] ?? 0)),

        'important_notes' =>
            trim(
                $_POST['important_notes'] ?? ''
            ),

        'images' =>
            '',

        'gallery' =>
            json_encode(
                $gallery,
                JSON_UNESCAPED_SLASHES
            ),

        'custom_field' =>
            trim(
                $_POST['custom_field'] ?? ''
            )
    ];


    /*
    |--------------------------------------------------------------------------
    | Save as New Package?
    |--------------------------------------------------------------------------
    */

    $save_as_new =
        isset(
            $_POST['save_as_new']
        );


    /*
    |--------------------------------------------------------------------------
    | UPDATE EXISTING TOUR
    |--------------------------------------------------------------------------
    */

    if (
        $id &&
        !$save_as_new
    ) {

        $sql = "

            UPDATE tours SET

                category_id = ?,

                tour_name = ?,

                customer_name = ?,

                customer_details = ?,

                duration_days = ?,

                duration_nights = ?,

                activity_level = ?,

                locations = ?,

                day_plans = ?,

                map_url = ?,

                highlights = ?,

                inclusions = ?,

                exclusions = ?,

                price_currency = ?,

                price_amount = ?,

                important_notes = ?,

                images = ?,

                gallery = ?,

                custom_field = ?

            WHERE id = ?

        ";


        db()->prepare($sql)->execute([

            $data['category_id'],

            $data['tour_name'],

            $data['customer_name'],

            $data['customer_details'],

            $data['duration_days'],

            $data['duration_nights'],

            $data['activity_level'],

            $data['locations'],

            $data['day_plans'],

            $data['map_url'],

            $data['highlights'],

            $data['inclusions'],

            $data['exclusions'],

            $data['price_currency'],

            $data['price_amount'],

            $data['important_notes'],

            $data['images'],

            $data['gallery'],

            $data['custom_field'],

            $id
        ]);


        $savedId = $id;
    }


    /*
    |--------------------------------------------------------------------------
    | CREATE NEW TOUR
    |--------------------------------------------------------------------------
    */

    else {

        /*
        |--------------------------------------------------------------------------
        | CREATE NEW TOUR WITH DAILY PACKAGE ID
        |--------------------------------------------------------------------------
        |
        | Format: GT-YYYYMMDD-0001
        | The lock is held while the number is selected and the row is
        | inserted, preventing duplicate numbers for simultaneous saves.
        |
        */

        $db = db();
        $lockName = null;

        try {
            $lockInfo = acquireDailyPackageLock($db);
            $lockName = $lockInfo['lock'];
            $prefix = $lockInfo['prefix'];

            $numberStmt = $db->prepare(
                "
                SELECT MAX(
                    CAST(
                        SUBSTRING_INDEX(package_id, '-', -1)
                        AS UNSIGNED
                    )
                )
                FROM tours
                WHERE package_id LIKE ?
                "
            );

            $numberStmt->execute([
                $prefix . '%'
            ]);

            $lastNumber = $numberStmt->fetchColumn();

            $nextNumber =
                $lastNumber !== null
                ? ((int)$lastNumber + 1)
                : 1;

            if ($nextNumber > 9999) {
                throw new RuntimeException(
                    'The daily Package ID limit of 9999 packages has been reached.'
                );
            }

            $packageNumber = str_pad(
                (string)$nextNumber,
                4,
                '0',
                STR_PAD_LEFT
            );

            $newPackageId =
                $prefix . $packageNumber;

            $sql = "

                INSERT INTO tours

                (

                    category_id,

                    package_id,

                    tour_name,

                    customer_name,

                    customer_details,

                    duration_days,

                    duration_nights,

                    activity_level,

                    locations,

                    day_plans,

                    map_url,

                    highlights,

                    inclusions,

                    exclusions,

                    price_currency,

                    price_amount,

                    important_notes,

                    images,

                    gallery,

                    custom_field,

                    source_tour_id

                )

                VALUES

                (

                    ?, ?, ?, ?, ?,

                    ?, ?,

                    ?,

                    ?, ?,

                    ?,

                    ?, ?, ?,

                    ?, ?,

                    ?,

                    ?, ?, ?, ?

                )

            ";


            $db->prepare($sql)->execute([

                $data['category_id'],

                $newPackageId,

                $data['tour_name'],

                $data['customer_name'],

                $data['customer_details'],

                $data['duration_days'],

                $data['duration_nights'],

                $data['activity_level'],

                $data['locations'],

                $data['day_plans'],

                $data['map_url'],

                $data['highlights'],

                $data['inclusions'],

                $data['exclusions'],

                $data['price_currency'],

                $data['price_amount'],

                $data['important_notes'],

                $data['images'],

                $data['gallery'],

                $data['custom_field'],

                $id ?: null

            ]);


            $savedId =
                (int)$db->lastInsertId();

            releaseDailyPackageLock($db, $lockName);
            $lockName = null;

        } catch (Throwable $e) {

            if ($lockName !== null) {
                try {
                    releaseDailyPackageLock($db, $lockName);
                } catch (Throwable $ignored) {
                    // Keep the original exception.
                }
            }

            throw $e;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Redirect
    |--------------------------------------------------------------------------
    */

    header(
        'Location: tour_form.php?id=' .
        $savedId .
        '&saved=1'
    );

    exit;
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
<?= $id ? 'Customize Tour' : 'Add Tour Package' ?>
— Goali Tours
</title>

<link
    rel="stylesheet"
    href="assets/css/style.css"
>


<style>

/*
|--------------------------------------------------------------------------
| LOCATION LIST
|--------------------------------------------------------------------------
*/

.location-list {

    margin-top: 15px;

}

.location-row {

    display: flex;

    align-items: center;

    gap: 10px;

    margin-bottom: 10px;

}

.location-number {

    width: 35px;

    font-weight: 700;

    color: #555;

}

.location-row input {

    flex: 1;

}


/*
|--------------------------------------------------------------------------
| ITINERARY
|--------------------------------------------------------------------------
*/

.itinerary-controls {

    display: flex;

    gap: 15px;

    align-items: end;

    margin-bottom: 25px;

}


.itinerary-day {

    background: #f8f8f8;

    border: 1px solid #ddd;

    border-radius: 10px;

    padding: 25px;

    margin-bottom: 25px;

}


.itinerary-day h3 {

    margin-top: 0;

    margin-bottom: 20px;

}


.itinerary-grid {

    display: grid;

    grid-template-columns: 1fr 1fr;

    gap: 20px;

}


.itinerary-details {

    min-height: 180px;

}


.day-image-preview {

    margin-top: 15px;

}


.day-image-preview img {

    max-width: 250px;

    max-height: 180px;

    border-radius: 8px;

    display: block;

}


/*
|--------------------------------------------------------------------------
| GALLERY
|--------------------------------------------------------------------------
*/

.gallery-preview {

    display: grid;

    grid-template-columns:
        repeat(
            auto-fill,
            minmax(160px, 1fr)
        );

    gap: 15px;

    margin-top: 20px;

}


.gallery-item {

    position: relative;

    border: 1px solid #ddd;

    border-radius: 8px;

    overflow: hidden;

    background: #f7f7f7;

}


.gallery-item img {

    width: 100%;

    height: 140px;

    object-fit: cover;

    display: block;

}


.gallery-item label {

    display: block;

    padding: 8px;

    font-size: 12px;

}


.gallery-remove-controls {

    margin-top: 15px;

}


.gallery-remove-btn {

    background: #dc3545;

    color: white;

    border: none;

    padding: 10px 16px;

    border-radius: 6px;

    cursor: pointer;

    font-weight: 600;

}


.gallery-remove-btn:hover {

    background: #bb2d3b;

}


.new-gallery-preview {

    display: grid;

    grid-template-columns:
        repeat(
            auto-fill,
            minmax(160px, 1fr)
        );

    gap: 15px;

    margin-top: 20px;

}


.new-gallery-item {

    border: 1px solid #ddd;

    border-radius: 8px;

    overflow: hidden;

    background: #f7f7f7;

}


.new-gallery-item img {

    width: 100%;

    height: 140px;

    object-fit: cover;

    display: block;

}


.new-gallery-item label {

    display: block;

    padding: 8px;

    font-size: 12px;

}


/*
|--------------------------------------------------------------------------
| MAP
|--------------------------------------------------------------------------
*/

.map-preview-container {

    display: none;

    margin-top: 25px;

    padding: 20px;

    background: #f7f7f7;

    border: 1px solid #ddd;

    border-radius: 10px;

}


.map-preview-container h3 {

    margin-top: 0;

}


.map-preview-frame {

    width: 100%;

    height: 450px;

    border: 0;

    border-radius: 8px;

}


.map-help {

    margin-top: 8px;

    font-size: 13px;

    color: #777;

}


.map-status {

    margin-top: 10px;

    font-size: 13px;

}


.map-status.success {

    color: #198754;

}


.map-status.error {

    color: #dc3545;

}


/*
|--------------------------------------------------------------------------
| RESPONSIVE
|--------------------------------------------------------------------------
*/

@media(max-width:700px) {

    .itinerary-grid {

        grid-template-columns: 1fr;

    }

    .itinerary-controls {

        flex-direction: column;

        align-items: stretch;

    }

}

</style>

</head>


<body>


<div class="form-shell">


<div class="form-header">

    <div>

        <a
            href="dashboard.php?category_id=<?= (int)$tour['category_id'] ?>"
            class="back"
        >
            ← Dashboard
        </a>

        <h1>

            <?= $id
                ? 'Customize Tour Package'
                : 'Add Tour Package'
            ?>

        </h1>

    </div>


    <?php if ($id): ?>

        <div class="header-actions">

            <a
                class="btn secondary"
                href="generate_pdf.php?id=<?= $id ?>"
                target="_blank"
            >
                Preview / PDF
            </a>

        </div>

    <?php endif; ?>

</div>


<?php if (isset($_GET['saved'])): ?>

    <div class="alert success">

        Tour saved successfully.

    </div>

<?php endif; ?>


<!-- =========================================================
     FORM
========================================================== -->

<form
    method="post"
    enctype="multipart/form-data"
>


<input
    type="hidden"
    name="category_id"
    value="<?= (int)$tour['category_id'] ?>"
>


<!-- =========================================================
     1. BASIC INFORMATION
========================================================== -->

<section class="form-section">

<h2>
1. Basic Information
</h2>


<div class="form-grid">


<div class="field full">

<label>
Tour Name
</label>

<input
    name="tour_name"
    value="<?= e($tour['tour_name']) ?>"
    required
>

</div>


<div class="field">

<label>
Category
</label>

<select
    name="category_id"
    required
>

<?php foreach ($categories as $cat): ?>

<option
    value="<?= (int)$cat['id'] ?>"
    <?= (int)$tour['category_id'] ===
        (int)$cat['id']
        ? 'selected'
        : ''
    ?>
>

<?= e($cat['name']) ?>

</option>

<?php endforeach; ?>

</select>

</div>


<div class="field">

<label>
Package ID
</label>

<input
    value="<?= e($tour['package_id']) ?>"
    disabled
>

</div>


<div class="field">

<label>
Days
</label>

<select
    name="duration_days"
    id="days"
    required
>
<?php for ($i = 1; $i <= 30; $i++): ?>
    <option
        value="<?= $i ?>"
        <?= $i === (int)$tour['duration_days'] ? 'selected' : '' ?>
    >
        <?= $i ?> Day<?= $i > 1 ? 's' : '' ?>
    </option>
<?php endfor; ?>
</select>

</div>


<div class="field">

<label>
Nights
</label>

<input
    type="text"
    id="nightsDisplay"
    value="<?= max(0, (int)$tour['duration_days'] - 1) ?> Night<?= max(0, (int)$tour['duration_days'] - 1) !== 1 ? 's' : '' ?>"
    readonly
>
<input
    type="hidden"
    name="duration_nights"
    id="nights"
    value="<?= max(0, (int)$tour['duration_days'] - 1) ?>"
>

</div>


<div class="field">

<label>
Activity Level
</label>

<select name="activity_level">

<?php foreach (
    ['Easy','Intermediate','Hard']
    as $level
): ?>

<option
    <?= $tour['activity_level'] === $level
        ? 'selected'
        : ''
    ?>
>

<?= $level ?>

</option>

<?php endforeach; ?>

</select>

</div>


</div>

</section>


<!-- =========================================================
     2. CUSTOMER DETAILS
========================================================== -->

<section class="form-section">

<h2>
2. Customer Details
</h2>


<div class="form-grid">


<div class="field full">

<label>
Customer Name
</label>

<input
    name="customer_name"
    value="<?= e($tour['customer_name']) ?>"
>

</div>


<div class="field full">

<label>
Customer Details
</label>

<textarea
    name="customer_details"
><?= e($tour['customer_details']) ?></textarea>

</div>


</div>

</section>


<!-- =========================================================
     3. LOCATIONS
========================================================== -->

<section class="form-section">

<h2>
3. Locations
</h2>

<p class="muted">

Enter one location and press Enter to add the next location.

</p>


<div
    id="locationList"
    class="location-list"
></div>


</section>


<!-- =========================================================
     4. ITINERARY
========================================================== -->

<section class="form-section">

<h2>
4. Itinerary
</h2>



<div id="itineraryContainer"></div>


</section>


<!-- =========================================================
     5. TOUR MAP
========================================================== -->

<section class="form-section">

<h2>
5. Tour Map
</h2>


<div class="field full">

<label>
Google Maps Embed URL or iframe code
</label>


<textarea
    name="map_url"
    id="map_url"
    rows="5"
    placeholder="Paste Google Maps embed URL or iframe code..."
><?= e($tour['map_url']) ?></textarea>


<div class="map-help">

<strong>How to get the map:</strong>

Open Google Maps →
Share →
Embed a map →
Copy the iframe code.

</div>


<div
    id="mapStatus"
    class="map-status"
></div>


<div
    id="mapPreviewContainer"
    class="map-preview-container"
>

<h3>
Map Preview
</h3>


<iframe
    id="mapPreview"
    class="map-preview-frame"
    loading="lazy"
    allowfullscreen
></iframe>

</div>

</div>

</section>


<!-- =========================================================
     6. HIGHLIGHTS
========================================================== -->

<section class="form-section">

<h2>
6. Highlights
</h2>


<textarea
    name="highlights"
    placeholder="Enter one highlight per line..."
><?= e($tour['highlights']) ?></textarea>

</section>


<!-- =========================================================
     7. INCLUSION / EXCLUSION
========================================================== -->

<section class="form-section">

<h2>
7. Inclusion & Exclusion
</h2>


<div class="form-grid">


<div class="field">

<label>
Inclusions
</label>

<textarea
    name="inclusions"
    placeholder="Enter one inclusion per line..."
><?= e($tour['inclusions']) ?></textarea>

</div>


<div class="field">

<label>
Exclusions
</label>

<textarea
    name="exclusions"
    placeholder="Enter one exclusion per line..."
><?= e($tour['exclusions']) ?></textarea>

</div>


</div>

</section>


<!-- =========================================================
     8. PRICING
========================================================== -->

<section class="form-section">

<h2>8. Pricing</h2>

<div class="form-grid">

<div class="field">
<label>Currency</label>
<select name="price_currency" required>
    <?php foreach ([
        'LKR' => 'LKR — Sri Lankan Rupee',
'USD' => 'USD — US Dollar',
'GBP' => 'GBP — Pound Sterling',
'EUR' => 'EUR — Euro',
'AUD' => 'AUD — Australian Dollar',
'CAD' => 'CAD — Canadian Dollar',
'CHF' => 'CHF — Swiss Franc',
'JPY' => 'JPY — Japanese Yen',
'CNY' => 'CNY — Chinese Yuan',
'INR' => 'INR — Indian Rupee',
'SGD' => 'SGD — Singapore Dollar',
'NZD' => 'NZD — New Zealand Dollar',
'ZAR' => 'ZAR — South African Rand',
'AED' => 'AED — UAE Dirham',
'SAR' => 'SAR — Saudi Riyal',
'QAR' => 'QAR — Qatari Riyal',
'KWD' => 'KWD — Kuwaiti Dinar',
'BHD' => 'BHD — Bahraini Dinar',
'OMR' => 'OMR — Omani Rial',
'MYR' => 'MYR — Malaysian Ringgit',
'THB' => 'THB — Thai Baht',
'KRW' => 'KRW — South Korean Won',
'SEK' => 'SEK — Swedish Krona',
'NOK' => 'NOK — Norwegian Krone',
'DKK' => 'DKK — Danish Krone',
'PLN' => 'PLN — Polish Zloty',
'RUB' => 'RUB — Russian Ruble',
'TRY' => 'TRY — Turkish Lira',
'BRL' => 'BRL — Brazilian Real',
    ] as $code => $label): ?>
        <option value="<?= e($code) ?>" <?= ($tour['price_currency'] ?? 'LKR') === $code ? 'selected' : '' ?>>
            <?= e($label) ?>
        </option>
    <?php endforeach; ?>
</select>
</div>

<div class="field">
<label>Tour Price</label>
<input type="number" name="price_amount" min="0" step="0.01" value="<?= e((string)($tour['price_amount'] ?? '0.00')) ?>" placeholder="Enter amount" required>
</div>

</div>

</section>


<!-- =========================================================
     9. IMPORTANT NOTES
========================================================== -->

<section class="form-section">

<h2>
9. Important Notes
</h2>


<textarea
    name="important_notes"
><?= e($tour['important_notes']) ?></textarea>

</section>


<!-- =========================================================
     10. TOUR GALLERY
========================================================== -->

<section class="form-section">

<h2>
10. Tour Gallery
</h2>


<label>
Add Images
</label>


<input
    type="file"
    name="gallery_files[]"
    id="galleryFiles"
    multiple
    accept="image/jpeg,image/png,image/webp"
>


<p class="muted">

You can select multiple images at once.

</p>


<!-- =========================================================
     EXISTING GALLERY
========================================================== -->

<?php if (!empty($existingGallery)): ?>

<h3>
Existing Gallery
</h3>


<div class="gallery-preview">

<?php foreach (
    $existingGallery
    as $galleryIndex => $galleryImage
): ?>

<div
    class="gallery-item"
    data-existing-gallery-item
>

<img
    src="<?= e($galleryImage) ?>"
    alt="Gallery image"
>


<label>

<input
    type="checkbox"
    name="delete_gallery[]"
    value="<?= e($galleryImage) ?>"
    class="existing-gallery-checkbox"
>

Select

</label>

</div>

<?php endforeach; ?>

</div>


<div class="gallery-remove-controls">

<button
    type="button"
    class="gallery-remove-btn"
    id="removeExistingGalleryBtn"
>

Remove Selected Images

</button>

</div>

<?php endif; ?>


<!-- =========================================================
     NEW GALLERY PREVIEW
========================================================== -->

<div
    id="newGalleryPreview"
    class="new-gallery-preview"
></div>


<div
    class="gallery-remove-controls"
    id="newGalleryRemoveControls"
    style="display:none;"
>

<button
    type="button"
    class="gallery-remove-btn"
    id="removeNewGalleryBtn"
>

Remove Selected Images

</button>

</div>


</section>


<!-- =========================================================
     11. ADDITIONAL INFORMATION
========================================================== -->

<section class="form-section">

<h2>
11. Additional Information
</h2>


<textarea
    name="custom_field"
    placeholder="Add any other information needed..."
><?= e($tour['custom_field']) ?></textarea>

</section>


<!-- =========================================================
     ACTION BUTTONS
========================================================== -->

<div class="sticky-actions">


<?php if ($id): ?>

<button
    class="btn secondary"
    type="submit"
    name="save_as_new"
    value="1"
>

Save as New Package

</button>


<button
    class="btn primary"
    type="submit"
>

Update Package

</button>

<?php else: ?>

<button
    class="btn primary"
    type="submit"
>

Save Package

</button>

<?php endif; ?>


<a
    class="btn ghost"
    href="dashboard.php?category_id=<?= (int)$tour['category_id'] ?>"
>

Cancel

</a>


</div>


</form>

</div>


<script>

/*
|--------------------------------------------------------------------------
| EXISTING LOCATIONS
|--------------------------------------------------------------------------
*/

const existingLocations = <?= json_encode(
    preg_split(
        '/\R/',
        $tour['locations'] ?? '',
        -1,
        PREG_SPLIT_NO_EMPTY
    )
) ?>;


/*
|--------------------------------------------------------------------------
| LOCATION LIST
|--------------------------------------------------------------------------
*/

const locationList =
    document.getElementById(
        'locationList'
    );


function createLocationInput(
    value = ''
) {

    const row =
        document.createElement('div');

    row.className =
        'location-row';


    const number =
        document.createElement('span');

    number.className =
        'location-number';


    const input =
        document.createElement('input');

    input.type =
        'text';

    input.name =
        'locations[]';

    input.placeholder =
        'Enter location...';

    input.value =
        value;


    row.appendChild(number);

    row.appendChild(input);

    locationList.appendChild(row);


    updateLocationNumbers();


    input.addEventListener(
        'keydown',
        function(event) {

            if (
                event.key === 'Enter'
            ) {

                event.preventDefault();

                createLocationInput();

                const inputs =
                    locationList.querySelectorAll(
                        'input'
                    );

                inputs[
                    inputs.length - 1
                ].focus();
            }
        }
    );
}


function updateLocationNumbers() {

    const rows =
        locationList.querySelectorAll(
            '.location-row'
        );


    rows.forEach(
        (row, index) => {

            row.querySelector(
                '.location-number'
            ).textContent =
                (index + 1) + '.';

        }
    );
}


/*
|--------------------------------------------------------------------------
| Load Existing Locations
|--------------------------------------------------------------------------
*/

if (
    existingLocations.length > 0
) {

    existingLocations.forEach(
        location => {

            createLocationInput(
                location
            );

        }
    );

} else {

    createLocationInput();

}


/*
|--------------------------------------------------------------------------
| ITINERARY DATA
|--------------------------------------------------------------------------
*/

const existingDays =
    <?= json_encode(
        $existingDays,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    ) ?>;


const itineraryContainer =
    document.getElementById(
        'itineraryContainer'
    );


const daysSelect =
    document.getElementById('days');


/*
|--------------------------------------------------------------------------
| Generate Itinerary Boxes
|--------------------------------------------------------------------------
*/

function updateNights() {

    const days = parseInt(daysSelect.value || '1', 10);
    const nights = Math.max(0, days - 1);

    document.getElementById('nights').value = nights;

    document.getElementById('nightsDisplay').value =
        nights + ' Night' + (nights !== 1 ? 's' : '');
}


function generateItinerary() {

    const count =
        parseInt(
            daysSelect.value || '1'
        );


    itineraryContainer.innerHTML = '';


    for (
        let i = 0;
        i < count;
        i++
    ) {

        const existing =
            existingDays[i]
            || {};


        const dayBox =
            document.createElement(
                'div'
            );

        dayBox.className =
            'itinerary-day';


        const title =
            existing.title
            || 'Day ' + (i + 1);


        const details =
            existing.details
            || '';


        const image =
            existing.image
            || '';


        dayBox.innerHTML = `

            <h3>
                Day ${i + 1}
            </h3>

            <input
                type="hidden"
                name="day_plans[${i}][existing_image]"
                value="${escapeHtml(image)}"
            >

            <div class="itinerary-grid">

                <div>

                    <div class="field">

                        <label>
                            Day Title
                        </label>

                        <input
                            type="text"
                            name="day_plans[${i}][title]"
                            value="${escapeHtml(title)}"
                            placeholder="Example: Arrival in Colombo"
                        >

                    </div>


                    <div class="field">

                        <label>
                            Itinerary Details
                        </label>

                        <textarea
                            class="itinerary-details"
                            name="day_plans[${i}][details]"
                            placeholder="Enter the itinerary details for Day ${i + 1}..."
                        >${escapeHtml(details)}</textarea>

                    </div>

                </div>


                <div>

                    <div class="field">

                        <label>
                            Day ${i + 1} Image
                        </label>

                        <input
                            type="file"
                            name="day_plans[${i}][image]"
                            accept="image/jpeg,image/png,image/webp"
                            onchange="previewDayImage(this, ${i})"
                        >

                    </div>


                    <div
                        id="dayPreview${i}"
                        class="day-image-preview"
                    >
                        ${
                            image
                            ? `
                                <img
                                    src="${escapeHtml(image)}"
                                    alt="Day ${i + 1}"
                                >
                              `
                            : ''
                        }
                    </div>

                </div>

            </div>
        `;


        itineraryContainer.appendChild(
            dayBox
        );
    }
}


/*
|--------------------------------------------------------------------------
| Escape HTML
|--------------------------------------------------------------------------
*/

function escapeHtml(value) {

    return String(value)
        .replace(
            /&/g,
            '&amp;'
        )
        .replace(
            /</g,
            '&lt;'
        )
        .replace(
            />/g,
            '&gt;'
        )
        .replace(
            /"/g,
            '&quot;'
        )
        .replace(
            /'/g,
            '&#039;'
        );
}


/*
|--------------------------------------------------------------------------
| Day Image Preview
|--------------------------------------------------------------------------
*/

function previewDayImage(
    input,
    index
) {

    const preview =
        document.getElementById(
            'dayPreview' + index
        );


    preview.innerHTML = '';


    if (
        input.files &&
        input.files[0]
    ) {

        const reader =
            new FileReader();


        reader.onload =
            function(event) {

                const img =
                    document.createElement(
                        'img'
                    );

                img.src =
                    event.target.result;

                img.alt =
                    'Day ' +
                    (index + 1);

                preview.appendChild(
                    img
                );
            };


        reader.readAsDataURL(
            input.files[0]
        );
    }
}


/*
|--------------------------------------------------------------------------
| GALLERY
|--------------------------------------------------------------------------
*/

const galleryFiles =
    document.getElementById(
        'galleryFiles'
    );


const newGalleryPreview =
    document.getElementById(
        'newGalleryPreview'
    );


const newGalleryRemoveControls =
    document.getElementById(
        'newGalleryRemoveControls'
    );


/*
|--------------------------------------------------------------------------
| Store Newly Selected Gallery Files
|--------------------------------------------------------------------------
*/

let selectedGalleryFiles = [];


/*
|--------------------------------------------------------------------------
| Display New Gallery Images
|--------------------------------------------------------------------------
*/

function displayNewGalleryImages() {

    newGalleryPreview.innerHTML = '';


    selectedGalleryFiles.forEach(
        (file, index) => {

            const item =
                document.createElement(
                    'div'
                );

            item.className =
                'new-gallery-item';


            const reader =
                new FileReader();


            reader.onload =
                function(event) {

                    item.innerHTML = `

                        <img
                            src="${event.target.result}"
                            alt="New gallery image"
                        >

                        <label>

                            <input
                                type="checkbox"
                                class="new-gallery-checkbox"
                                data-index="${index}"
                            >

                            Select

                        </label>

                    `;


                    newGalleryPreview.appendChild(
                        item
                    );

                };


            reader.readAsDataURL(
                file
            );

        }
    );


    if (
        selectedGalleryFiles.length > 0
    ) {

        newGalleryRemoveControls.style.display =
            'block';

    } else {

        newGalleryRemoveControls.style.display =
            'none';

    }
}


/*
|--------------------------------------------------------------------------
| New Gallery File Selection
|--------------------------------------------------------------------------
*/

galleryFiles.addEventListener(
    'change',
    function() {

        selectedGalleryFiles =
            Array.from(
                this.files
            );


        displayNewGalleryImages();

    }
);


/*
|--------------------------------------------------------------------------
| Remove Selected NEW Gallery Images
|--------------------------------------------------------------------------
*/

document
    .getElementById(
        'removeNewGalleryBtn'
    )
    ?.addEventListener(
        'click',
        function() {

            const checkboxes =
                document.querySelectorAll(
                    '.new-gallery-checkbox:checked'
                );


            if (
                checkboxes.length === 0
            ) {

                alert(
                    'Please select at least one image to remove.'
                );

                return;
            }


            const indexesToRemove =
                Array.from(
                    checkboxes
                ).map(
                    checkbox =>
                        parseInt(
                            checkbox.dataset.index
                        )
                );


            selectedGalleryFiles =
                selectedGalleryFiles.filter(
                    (file, index) =>
                        !indexesToRemove.includes(
                            index
                        )
                );


            /*
            |--------------------------------------------------------------------------
            | Update actual file input
            |--------------------------------------------------------------------------
            */

            const dataTransfer =
                new DataTransfer();


            selectedGalleryFiles.forEach(
                file => {

                    dataTransfer.items.add(
                        file
                    );

                }
            );


            galleryFiles.files =
                dataTransfer.files;


            displayNewGalleryImages();

        }
    );


/*
|--------------------------------------------------------------------------
| Remove Selected EXISTING Gallery Images
|--------------------------------------------------------------------------
|
| IMPORTANT:
| We do NOT remove the checkbox itself.
| We simply hide the image visually.
| The checkbox remains in the form so PHP receives
| delete_gallery[] when the user saves.
|
*/

document
    .getElementById(
        'removeExistingGalleryBtn'
    )
    ?.addEventListener(
        'click',
        function() {

            const checkboxes =
                document.querySelectorAll(
                    '.existing-gallery-checkbox:checked'
                );


            if (
                checkboxes.length === 0
            ) {

                alert(
                    'Please select at least one image to remove.'
                );

                return;
            }


            if (
                !confirm(
                    'Remove the selected gallery images?'
                )
            ) {

                return;
            }


            checkboxes.forEach(
                checkbox => {

                    const galleryItem =
                        checkbox.closest(
                            '.gallery-item'
                        );


                    if (galleryItem) {

                        /*
                        |--------------------------------------------------------------------------
                        | Keep checkbox in the form
                        |--------------------------------------------------------------------------
                        */

                        galleryItem.style.display =
                            'none';

                    }

                }
            );

        }
    );


/*
|--------------------------------------------------------------------------
| Itinerary Days Event
|--------------------------------------------------------------------------
*/

daysSelect.addEventListener(
    'change',
    function () {
        updateNights();
        generateItinerary();
    }
);


/*
|--------------------------------------------------------------------------
| Initial Itinerary
|--------------------------------------------------------------------------
*/

updateNights();
generateItinerary();


/*
|--------------------------------------------------------------------------
| MAP
|--------------------------------------------------------------------------
*/

function extractMapUrl(value) {

    value =
        value.trim();


    if (
        value
            .toLowerCase()
            .includes('<iframe')
    ) {

        const match =
            value.match(
                /src=["']([^"']+)["']/i
            );


        if (match) {

            return match[1];

        }

    }


    return value;
}


function updateMapPreview() {

    const input =
        document.getElementById(
            'map_url'
        );


    const preview =
        document.getElementById(
            'mapPreview'
        );


    const container =
        document.getElementById(
            'mapPreviewContainer'
        );


    const status =
        document.getElementById(
            'mapStatus'
        );


    const value =
        extractMapUrl(
            input.value
        );


    if (value === '') {

        preview.src = '';

        container.style.display =
            'none';

        status.textContent =
            '';

        return;
    }


    const validGoogleMap =
        value.includes(
            'google.com/maps'
        ) ||
        value.includes(
            'maps.google.com'
        );


    if (!validGoogleMap) {

        container.style.display =
            'none';

        status.className =
            'map-status error';

        status.textContent =
            'Please enter a valid Google Maps URL or iframe code.';

        return;
    }


    preview.src =
        value;

    container.style.display =
        'block';

    status.className =
        'map-status success';

    status.textContent =
        'Google Maps preview loaded.';
}


document
    .getElementById('map_url')
    .addEventListener(
        'input',
        updateMapPreview
    );


updateMapPreview();

</script>

</body>

</html>