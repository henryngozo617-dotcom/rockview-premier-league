<?php

require_once "auth.php";

require_once "../db.php";

if (!isset($_SESSION['rpl_admin_id'])) {
    header("Location: index.php");
    exit;
}

require_once "../db.php";


/* =========================================================
   HELPER
========================================================= */

function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}


/* =========================================================
   IMAGE UPLOAD
========================================================= */

function uploadNewsImage($file) {

    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    /* Maximum 5MB */

    if ($file['size'] > 5 * 1024 * 1024) {
        return false;
    }

    /* Allowed image types */

    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    $mime = mime_content_type($file['tmp_name']);

    if (!isset($allowedTypes[$mime])) {
        return false;
    }

    $extension = $allowedTypes[$mime];

    /* Random filename */

    $filename =
        'news_' .
        bin2hex(random_bytes(8)) .
        '.' .
        $extension;

    $uploadDirectory =
        __DIR__ . '/../images/news/';

    if (!is_dir($uploadDirectory)) {
        mkdir($uploadDirectory, 0755, true);
    }

    $destination =
        $uploadDirectory . $filename;

    if (!move_uploaded_file(
        $file['tmp_name'],
        $destination
    )) {
        return false;
    }

    return 'images/news/' . $filename;
}


/* =========================================================
   DELETE IMAGE FILE
========================================================= */

function deleteNewsImage($imagePath) {

    if (!$imagePath) {
        return;
    }

    $fullPath =
        __DIR__ . '/../' . $imagePath;

    if (file_exists($fullPath)) {
        unlink($fullPath);
    }
}


/* =========================================================
   CREATE NEWS
========================================================= */

if (isset($_POST['create_news'])) {

    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $status = $_POST['status'] ?? 'Published';

    if ($author === '') {
        $author = 'RPL Admin';
    }

    $imagePath = uploadNewsImage($_FILES['image'] ?? null);

    if ($imagePath === false) {

        header("Location: news.php?error=image");
        exit;
    }

    if ($title !== '' && $content !== '') {

        $stmt = $conn->prepare("
            INSERT INTO news
            (
                title,
                content,
                image,
                author,
                status
            )
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "sssss",
            $title,
            $content,
            $imagePath,
            $author,
            $status
        );

        $stmt->execute();

        $stmt->close();

        header("Location: news.php?success=created");
        exit;
    }
}


/* =========================================================
   UPDATE NEWS
========================================================= */

if (isset($_POST['update_news'])) {

    $id = (int)($_POST['id'] ?? 0);

    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $status = $_POST['status'] ?? 'Published';

    if ($author === '') {
        $author = 'RPL Admin';
    }


    /* Get existing image */

    $stmt = $conn->prepare("
        SELECT image
        FROM news
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $id);

    $stmt->execute();

    $result = $stmt->get_result();

    $existingImage = null;

    if ($result->num_rows > 0) {

        $existingRow = $result->fetch_assoc();

        $existingImage = $existingRow['image'];
    }

    $stmt->close();


    /* New image */

    $newImage = uploadNewsImage(
        $_FILES['image'] ?? null
    );

    if ($newImage === false) {

        header("Location: news.php?edit=$id&error=image");
        exit;
    }


    /* Keep old image if no new image */

    $finalImage =
        $newImage !== null
        ? $newImage
        : $existingImage;


    $stmt = $conn->prepare("
        UPDATE news
        SET
            title = ?,
            content = ?,
            image = ?,
            author = ?,
            status = ?,
            updated_at = NOW()
        WHERE id = ?
    ");

    $stmt->bind_param(
        "sssssi",
        $title,
        $content,
        $finalImage,
        $author,
        $status,
        $id
    );

    $stmt->execute();

    $stmt->close();


    /* Delete old image after successful replacement */

    if (
        $newImage !== null &&
        $existingImage &&
        $existingImage !== $newImage
    ) {

        deleteNewsImage($existingImage);
    }


    header("Location: news.php?success=updated");
    exit;
}


/* =========================================================
   DELETE NEWS
========================================================= */

if (isset($_GET['delete'])) {

    $id = (int)$_GET['delete'];


    /* Find image first */

    $stmt = $conn->prepare("
        SELECT image
        FROM news
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $id);

    $stmt->execute();

    $result = $stmt->get_result();

    $image = null;

    if ($result->num_rows > 0) {

        $row = $result->fetch_assoc();

        $image = $row['image'];
    }

    $stmt->close();


    /* Delete database record */

    $stmt = $conn->prepare("
        DELETE FROM news
        WHERE id = ?
    ");

    $stmt->bind_param("i", $id);

    $stmt->execute();

    $stmt->close();


    /* Delete image */

    if ($image) {
        deleteNewsImage($image);
    }


    header("Location: news.php?success=deleted");
    exit;
}


/* =========================================================
   EDIT MODE
========================================================= */

$editNews = null;

if (isset($_GET['edit'])) {

    $id = (int)$_GET['edit'];

    $stmt = $conn->prepare("
        SELECT *
        FROM news
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $id);

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {

        $editNews = $result->fetch_assoc();
    }

    $stmt->close();
}


/* =========================================================
   FETCH NEWS
========================================================= */

$newsList = [];

$result = $conn->query("
    SELECT *
    FROM news
    ORDER BY created_at DESC
");

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $newsList[] = $row;
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>RPL News & Updates</title>


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

    background: #f1f5f3;

    color: #17251f;
}


/* =========================================================
   HEADER
========================================================= */

.header {

    background:
        linear-gradient(
            135deg,
            #063b2a,
            #087443
        );

    color: white;

    padding: 22px 30px;

    display: flex;

    justify-content: space-between;

    align-items: center;

    box-shadow:
        0 4px 15px rgba(0,0,0,0.15);
}

.header h1 {

    margin: 0;

    font-size: 25px;
}

.header p {

    margin: 5px 0 0;

    opacity: 0.8;
}

.header-actions {

    display: flex;

    gap: 10px;

    flex-wrap: wrap;
}

.header-actions a {

    text-decoration: none;

    color: white;

    background:
        rgba(255,255,255,0.15);

    padding: 10px 15px;

    border-radius: 8px;
}

.header-actions a:hover {

    background:
        rgba(255,255,255,0.25);
}


/* =========================================================
   CONTAINER
========================================================= */

.container {

    max-width: 1250px;

    margin: 30px auto;

    padding: 0 20px;
}


/* =========================================================
   ALERT
========================================================= */

.alert {

    padding: 15px 18px;

    border-radius: 10px;

    margin-bottom: 20px;

    font-weight: bold;
}

.alert-success {

    background: #dff7e9;

    color: #087443;
}

.alert-error {

    background: #ffe5e5;

    color: #a52b2b;
}


/* =========================================================
   FORM CARD
========================================================= */

.form-card {

    background: white;

    border-radius: 15px;

    padding: 25px;

    box-shadow:
        0 5px 20px rgba(0,0,0,0.08);

    margin-bottom: 30px;
}

.form-card h2 {

    margin-top: 0;

    color: #063b2a;
}

.form-group {

    margin-bottom: 18px;
}

.form-group label {

    display: block;

    font-weight: bold;

    margin-bottom: 7px;
}

.form-group input,
.form-group textarea,
.form-group select {

    width: 100%;

    padding: 12px;

    border:
        1px solid #ccd8d2;

    border-radius: 8px;

    font-size: 15px;

    outline: none;
}

.form-group textarea {

    min-height: 180px;

    resize: vertical;
}

.form-row {

    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 18px;
}


/* =========================================================
   IMAGE UPLOAD
========================================================= */

.image-upload-box {

    border:
        2px dashed #b9ccc3;

    border-radius: 12px;

    padding: 20px;

    background: #f8fbf9;

    text-align: center;
}

.image-upload-box input {

    border: none;

    background: transparent;

    padding: 10px;
}

.image-help {

    margin-top: 8px;

    font-size: 13px;

    color: #718078;
}

.current-image {

    margin-top: 15px;
}

.current-image img {

    width: 220px;

    height: 130px;

    object-fit: cover;

    border-radius: 10px;

    border: 1px solid #dce5e0;
}


/* =========================================================
   BUTTONS
========================================================= */

.button-row {

    display: flex;

    gap: 10px;

    flex-wrap: wrap;
}

.btn {

    border: none;

    padding: 12px 20px;

    border-radius: 8px;

    cursor: pointer;

    font-weight: bold;

    text-decoration: none;

    display: inline-block;
}

.btn-primary {

    background: #087443;

    color: white;
}

.btn-primary:hover {

    background: #063b2a;
}

.btn-secondary {

    background: #e8eeeb;

    color: #17251f;
}

.btn-danger {

    background: #c62828;

    color: white;
}

.btn-warning {

    background: #e8a317;

    color: white;
}


/* =========================================================
   NEWS LIST
========================================================= */

.news-section {

    background: white;

    border-radius: 15px;

    padding: 25px;

    box-shadow:
        0 5px 20px rgba(0,0,0,0.08);
}

.news-section h2 {

    margin-top: 0;

    color: #063b2a;
}

.news-card {

    border:
        1px solid #e1e8e4;

    border-radius: 12px;

    padding: 20px;

    margin-bottom: 18px;

    transition: 0.2s;
}

.news-card:hover {

    box-shadow:
        0 5px 15px rgba(0,0,0,0.08);

    transform: translateY(-2px);
}

.news-header {

    display: flex;

    justify-content: space-between;

    gap: 15px;

    align-items: flex-start;
}

.news-title {

    font-size: 21px;

    font-weight: bold;

    color: #063b2a;

    margin-bottom: 8px;
}

.news-meta {

    color: #718078;

    font-size: 13px;

    margin-bottom: 15px;
}

.news-content {

    white-space: pre-line;

    line-height: 1.7;

    color: #394840;

    margin-bottom: 18px;
}

.news-image {

    width: 100%;

    max-width: 700px;

    max-height: 350px;

    object-fit: cover;

    border-radius: 12px;

    margin-bottom: 18px;

    display: block;
}


/* =========================================================
   STATUS
========================================================= */

.status {

    display: inline-block;

    padding: 5px 10px;

    border-radius: 20px;

    font-size: 12px;

    font-weight: bold;
}

.status-published {

    background: #dff7e9;

    color: #087443;
}

.status-draft {

    background: #fff0c9;

    color: #8a6200;
}


/* =========================================================
   ACTIONS
========================================================= */

.actions {

    display: flex;

    gap: 8px;

    flex-wrap: wrap;
}


/* =========================================================
   EMPTY
========================================================= */

.empty {

    text-align: center;

    padding: 40px;

    color: #718078;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 700px) {

    .header {

        flex-direction: column;

        align-items: flex-start;

        gap: 15px;
    }

    .form-row {

        grid-template-columns: 1fr;
    }

    .news-header {

        flex-direction: column;
    }

}

</style>

</head>


<body>


<!-- =========================================================
     HEADER
========================================================= -->

<div class="header">

    <div>

        <h1>
            📰 RPL News & Updates
        </h1>

        <p>
            Rockview Premier League Administration
        </p>

    </div>


    <div class="header-actions">

        <a href="dashboard.php">
            Dashboard
        </a>

        <a
            href="../index.html"
            target="_blank"
        >
            View Website
        </a>

        <a href="logout.php">
            Logout
        </a>

    </div>

</div>


<div class="container">


<!-- =========================================================
     ALERTS
========================================================= -->

<?php if (isset($_GET['success'])): ?>

    <div class="alert alert-success">

        <?php if ($_GET['success'] === 'created'): ?>

            ✅ News article created successfully!

        <?php elseif ($_GET['success'] === 'updated'): ?>

            ✅ News article updated successfully!

        <?php elseif ($_GET['success'] === 'deleted'): ?>

            🗑️ News article deleted successfully!

        <?php endif; ?>

    </div>

<?php endif; ?>


<?php if (isset($_GET['error']) && $_GET['error'] === 'image'): ?>

    <div class="alert alert-error">

        ❌ Image upload failed.

        Please use JPG, PNG or WEBP images
        smaller than 5MB.

    </div>

<?php endif; ?>


<!-- =========================================================
     CREATE / EDIT FORM
========================================================= -->

<div class="form-card">


<?php if ($editNews): ?>

    <h2>
        ✏️ Edit News Article
    </h2>

    <form
        method="POST"
        enctype="multipart/form-data"
    >

        <input
            type="hidden"
            name="id"
            value="<?= e($editNews['id']) ?>"
        >

<?php else: ?>

    <h2>
        ➕ Publish New Update
    </h2>

    <form
        method="POST"
        enctype="multipart/form-data"
    >

<?php endif; ?>


<!-- TITLE -->

<div class="form-group">

    <label>
        News Title
    </label>

    <input
        type="text"
        name="title"
        required
        placeholder="Enter the news headline..."
        value="<?= e($editNews['title'] ?? '') ?>"
    >

</div>


<!-- CONTENT -->

<div class="form-group">

    <label>
        News Content
    </label>

    <textarea
        name="content"
        required
        placeholder="Write the full RPL update here..."
    ><?= e($editNews['content'] ?? '') ?></textarea>

</div>


<!-- IMAGE -->

<div class="form-group">

    <label>
        📸 News Image
    </label>

    <div class="image-upload-box">

        <input
            type="file"
            name="image"
            accept="image/jpeg,image/png,image/webp"
        >

        <div class="image-help">

            JPG, PNG or WEBP • Maximum 5MB

        </div>


<?php if (
    $editNews &&
    !empty($editNews['image'])
): ?>

        <div class="current-image">

            <p>
                <strong>
                    Current Image
                </strong>
            </p>

            <img
                src="../<?= e($editNews['image']) ?>"
                alt="Current news image"
            >

            <p class="image-help">
                Choose a new image above to replace it.
            </p>

        </div>

<?php endif; ?>

    </div>

</div>


<!-- AUTHOR + STATUS -->

<div class="form-row">

    <div class="form-group">

        <label>
            Author
        </label>

        <input
            type="text"
            name="author"
            placeholder="RPL Admin"
            value="<?= e(
                $editNews['author'] ?? 'RPL Admin'
            ) ?>"
        >

    </div>


    <div class="form-group">

        <label>
            Status
        </label>

        <select name="status">

            <option
                value="Published"
                <?= (
                    ($editNews['status'] ?? '') === 'Published'
                )
                ? 'selected'
                : '' ?>
            >
                Published
            </option>

            <option
                value="Draft"
                <?= (
                    ($editNews['status'] ?? '') === 'Draft'
                )
                ? 'selected'
                : '' ?>
            >
                Draft
            </option>

        </select>

    </div>

</div>


<!-- BUTTONS -->

<div class="button-row">

<?php if ($editNews): ?>

    <button
        type="submit"
        name="update_news"
        class="btn btn-primary"
    >
        💾 Save Changes
    </button>

    <a
        href="news.php"
        class="btn btn-secondary"
    >
        Cancel
    </a>

<?php else: ?>

    <button
        type="submit"
        name="create_news"
        class="btn btn-primary"
    >
        🚀 Publish Update
    </button>

<?php endif; ?>

</div>


</form>

</div>


<!-- =========================================================
     EXISTING NEWS
========================================================= -->

<div class="news-section">

    <h2>
        📢 Existing News & Updates
    </h2>


<?php if (count($newsList) === 0): ?>

    <div class="empty">

        <h3>
            No news articles yet.
        </h3>

        <p>
            Create your first RPL update above.
        </p>

    </div>

<?php else: ?>


<?php foreach ($newsList as $news): ?>

    <div class="news-card">


        <div class="news-header">

            <div>

                <div class="news-title">

                    <?= e($news['title']) ?>

                </div>


                <div class="news-meta">

                    By <?= e($news['author']) ?>

                    •

                    <?= date(
                        'd M Y H:i',
                        strtotime($news['created_at'])
                    ) ?>

                </div>

            </div>


<?php if ($news['status'] === 'Published'): ?>

            <span
                class="status status-published"
            >
                PUBLISHED
            </span>

<?php else: ?>

            <span
                class="status status-draft"
            >
                DRAFT
            </span>

<?php endif; ?>

        </div>


<?php if (!empty($news['image'])): ?>

        <img
            class="news-image"
            src="../<?= e($news['image']) ?>"
            alt="<?= e($news['title']) ?>"
        >

<?php endif; ?>


        <div class="news-content">

            <?= e($news['content']) ?>

        </div>


        <div class="actions">

            <a
                href="news.php?edit=<?= (int)$news['id'] ?>"
                class="btn btn-warning"
            >
                ✏️ Edit
            </a>


            <a
                href="news.php?delete=<?= (int)$news['id'] ?>"
                class="btn btn-danger"
                onclick="
                    return confirm(
                        'Are you sure you want to delete this news article?'
                    );
                "
            >
                🗑️ Delete
            </a>

        </div>

    </div>

<?php endforeach; ?>


<?php endif; ?>

</div>

</div>

</body>

</html>