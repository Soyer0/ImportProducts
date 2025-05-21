<?php
require_once 'Model.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
class ExcelModel extends Model {
    public function readExcel($filePath): void
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        $data = [];
        $isFirstRow = true;

        foreach ($sheet->getRowIterator() as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            $rowData = [];
            foreach ($cellIterator as $cell) {
                $rowData[] = trim((string) $cell->getValue());
            }

            $nonEmptyValues = array_filter($rowData, fn($val) => $val !== '');
            if (count($nonEmptyValues) === 0) {
                continue;
            }

            if ($isFirstRow) {
                $isFirstRow = false;
                $headers = array_map('strtolower', $rowData);
                if (
                    in_array('mpn', $headers) &&
                    in_array('sku', $headers) &&
                    in_array('amount', $headers)
                ) {
                    continue;
                }
            }

            if (
                (isset($rowData[0]) && $rowData[0] !== '') ||
                (isset($rowData[1]) && $rowData[1] !== '') ||
                (isset($rowData[2]) && $rowData[2] !== '')
            ) {
                $data[] = [
                    'mpn'    => $rowData[0] ?? '',
                    'sku'    => $rowData[1] ?? '',
                    'amount' => $rowData[2] ?? '',
                ];
            }
        }
        $this->processData($data);
    }

    public function processData($data): void
    {
        $mpns = [];
        $skus = [];
        foreach ($data as $row) {
            if(!empty($row['mpn']) && !empty($row['amount'])){
                $mpns[] = $row['mpn'];
            }
            if(!empty($row['sku']) && !empty($row['amount'])){
                $skus[] = $row['sku'];
            }
        }
        $products_mpn = $this->db->select('s_shopshowcase_products',
            '*', ['mpn' => $mpns])
            ->get('arrayIndexed:mpn');
        $products_sku = $this->db->select('b2b_company_product_articles as b2b', 'product_id, article', ['article' => $skus])
            ->join('s_shopshowcase_products', '*', '#b2b.product_id')
            ->get('arrayIndexed:article');
        foreach ($data as $item) {
            $product = null;

            if (!empty($item['mpn']) && isset($products_mpn[$item['mpn']])) {
                $product = $products_mpn[$item['mpn']];
            } elseif (!empty($item['sku']) && isset($products_sku[$item['sku']])) {
                $product = $products_sku[$item['sku']];
            }
            if ($product) {
                $this->db->insertRow('s_cart_products', [
                    'product_alias' => $product->wl_alias,
                    'product_id' => $product->id,
                    'price' => $product->price,
                    'currency_in' => $product->currency,
                    'quantity' => $item['amount'],
                    'date' => time(),
                ]);
            }
        }
    }
}