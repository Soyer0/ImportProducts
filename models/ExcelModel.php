<?php
require_once 'Model.php';
require(__DIR__ .  '/../lib/SpreadsheetReader/SpreadsheetReader.php');
class ExcelModel extends Model {
    private function readExcelForProductsCart($filePath, $originalName): array
    {
        $reader = new SpreadsheetReader($filePath, $originalName);

        $data = [];
        $isFirstRow = true;

        foreach ($reader as $rowData) {
            $rowData = array_map(fn($val) => trim((string) $val), $rowData);

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

        return $data;
    }

    private function readExcelForUserArticles($filePath, $originalName): array
    {
        $reader = new SpreadsheetReader($filePath, $originalName);
        $data = [];
        $isFirstRow = true;

        foreach ($reader as $rowData) {
            $rowData = array_map(fn($val) => trim((string) $val), $rowData);

            $nonEmptyValues = array_filter($rowData, fn($val) => $val !== '');
            if (count($nonEmptyValues) === 0) {
                continue;
            }

            if ($isFirstRow) {
                $isFirstRow = false;
                $headers = array_map('strtolower', $rowData);

                if (
                    in_array('mpn', $headers) &&
                    in_array('article', $headers) &&
                    in_array('sku', $headers)
                ) {
                    continue;
                }
            }

            if (
                (isset($rowData[0]) && $rowData[0] !== '') ||
                (isset($rowData[1]) && $rowData[1] !== '')
            ) {
                $data[] = [
                    'mpn'    => $rowData[0] ?? '',
                    'article'    => $rowData[1] ?? '',
                ];
            }
        }
        return $data;
    }

    public function importProductsCart($filePath, $originalName): void
    {
        $data = $this->readExcelForProductsCart($filePath, $originalName);
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
                    'user' => 1,
                    'active' => 1,
                    'quantity_wont' => 1,
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

    public function importUserArticles($filePath, $originalName): void
    {
        $data = $this->readExcelForUserArticles($filePath, $originalName);

        $filtered_data = array_filter($data, function ($row) {
            return !empty($row['mpn']) && !empty($row['article']);
        });

        $mpns = array_column($filtered_data, 'mpn');
        $articles = array_column($filtered_data, 'article');

        $products_id = $this->db->select('s_shopshowcase_products', 'id, mpn', [
            'mpn' => $mpns
        ])->get('arrayIndexed:mpn');

        $products_articles = $this->db->select('b2b_company_product_articles', 'article, product_id', [
            'article' => $articles
        ])->get('arrayIndexed:article');

        foreach ($filtered_data as $item) {
            if(isset($products_id[$item['mpn']])){
                if(isset($products_articles[$item['article']]))
                {
                    if($products_articles[$item['article']]->product_id !== $products_id[$item['mpn']]->id)
                    {
                        $this->db->updateRow('b2b_company_product_articles', [
                            'product_id' => $products_id[$item['mpn']]->id,
                            'date_update' => time(),
                        ], $item['article'], 'article');
                    }
                }
                else{
                    $date = time();
                    $this->db->insertRow('b2b_company_product_articles', [
                        'article' => $item['article'],
                        'company_id' => 0,
                        'product_id' => $products_id[$item['mpn']]->id,
                        'date_add' => $date,
                        'date_update' => $date,
                    ]);
                }
            }
        }
    }
}