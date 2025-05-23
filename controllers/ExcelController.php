<?php
require_once __DIR__ . '/../models/ExcelModel.php';
require_once(__DIR__ . '/../lib/data.php');

class ExcelController
{
    private ExcelModel $excelModel;

    public function __construct()
    {
        $this->excelModel = new ExcelModel();
        $this->data = new Data();
    }

    public function showUploadForm(): void
    {
        $content = $this->render('upload/index');
        echo $this->render('layout', ['content' => $content]);
    }

    public function handleImportingProductsCartForm(): void
    {
        header('Content-Type: application/json');

        if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] === UPLOAD_ERR_OK) {
            $filePath = $_FILES['excel_file']['tmp_name'];
            $originalName = $_FILES['excel_file']['name'];

            $this->excelModel->importProductsCart($filePath, $originalName);
            echo json_encode(['success' => true]);

        } else {
            echo json_encode(['error' => 'Upload failed']);
        }
    }

    public function handleImportingUserArticlesForm()
    {
        header('Content-Type: application/json');

        if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] === UPLOAD_ERR_OK) {
            $filePath = $_FILES['excel_file']['tmp_name'];
            $originalName = $_FILES['excel_file']['name'];

            $this->excelModel->importUserArticles($filePath, $originalName);
            echo json_encode(['success' => true]);

        } else {
            echo json_encode(['error' => 'Upload failed']);
        }
    }

    private function render($view, $data = [])
    {
        extract($data);
        ob_start();
        include "views/$view.php";
        return ob_get_clean();
    }
}