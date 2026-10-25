<?php
/* Opens and closes a page with the same head, header and footer as the .html pages */

function page_open($title) {
    $t = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{$t} – Tails of Us</title>
<link rel="stylesheet" href="css/base.css">
<link rel="stylesheet" href="css/shell.css">
<link rel="stylesheet" href="css/feedback.css">
<link rel="stylesheet" href="css/contrast.css">
<script src="js/shell.js" defer></script>
</head>
<body>
<a class="skip-link" href="#main">Skip to main content</a>

HTML;
    include_once __DIR__ . '/header.html';
}

function page_close() {
    include_once __DIR__ . '/footer.html';
    echo "\n</body>\n</html>\n";
}
