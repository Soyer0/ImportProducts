<?php
require __DIR__ . '/config/config.php';
require __DIR__ . '/controllers/ExcelController.php';

session_start();

if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'en';
}

if (!isset($_SESSION['all_languages'])) {
    $_SESSION['all_languages'] = ['en'];
}

if (!isset($GLOBALS['multilanguage_type'])) {
    $GLOBALS['multilanguage_type'] = 'main domain';
}

$controller = new ExcelController();

$action = $_GET['action'] ?? 'showUploadForm';

switch ($action) {
    case 'showUploadForm':
        $controller->showUploadForm();
        break;
    case 'handleImportingProductsCartForm':
        $controller->handleImportingProductsCartForm();
        break;
    case 'handleImportingUserArticlesForm':
        $controller->handleImportingUserArticlesForm();
        break;
    default:
        echo "404 Not Found";
        break;
}

