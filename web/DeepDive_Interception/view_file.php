<?php

require_once '../src/functions.php';

$session_id = get_custom_session_id();
$username = posix_getpwuid(posix_geteuid())['name'];

if (!is_admin($session_id)) {
    header('Location: index.php');
    exit();
}

function list_files($folder) {
    $files = glob($folder . '/*');
    return $files;
}

$content = '';

$folder = isset($_GET['folder']) ? $_GET['folder'] : 'uploads';
$file = isset($_GET['file']) ? $_GET['file'] : '';

if ($folder && !$file) {
    $files = list_files($folder);
    if ($files) {
        $content = "<ul>";
        foreach ($files as $file) {
            $content .= "<li><a href='?folder=" . urlencode($folder) . "&file=" . urlencode(basename($file)) . "'>" . htmlspecialchars(basename($file)) . "</a></li>";
        }
        $content .= "</ul>";
    } else {
        $content = "Failed to open directory.";
    }
} elseif ($folder && $file) {
    $file_to_view = $file;
    $file_path = $folder . '/' . basename($file_to_view);

    if (file_exists($file_path)) {
        $file_extension = pathinfo($file_path, PATHINFO_EXTENSION);
        if (in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])) {
            header('Content-Type: image/' . $file_extension);
            readfile($file_path);
            exit();
        } else {
            $content = "<pre>" . htmlspecialchars(file_get_contents($file_path)) . "</pre>";
        }
    } else {
        $content = "File not found.";
    }
} else {
    $content = "No file or folder specified.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View File</title>
    <link rel="stylesheet" href="/static/styles.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f4f4f4;
        }
        .content {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            padding: 20px;
            margin: 20px auto;
            max-width: 800px;
            text-align: center;
        }
        pre {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
        }
        ul {
            list-style-type: none;
            padding: 0;
        }
        li {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="content">
        <?= $content ?>
        <br>
        <a href="profile.php">Back to Profile</a>
    </div>
</body>
</html>

<!-- http://challenges.challenge-ecw.eu:38062/view_file.php?folder=. -->

<!-- http://challenges.challenge-ecw.eu:38062/view_file.php?folder=%2e%2e -->

<!-- ECW{S@cUr3_yOur_C0ok13s_6Jw91wsMmD} -->