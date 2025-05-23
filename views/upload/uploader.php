<div class="container">
    <div class="row">
        <form class="upload-form text-center" id="uploadImportingProductsCartForm" method="post" enctype="multipart/form-data" role="form">
            <h2>Importing products into the cart</h2>
            <div class="form-group">
                <input type="file" id="import-products-cart" name="excel_file" accept=".xls,.xlsx" required class="center-block">
            </div>
            <button type="submit" class="btn btn-lg btn-primary">Upload</button>
        </form>

        <form class="upload-form text-center" id="uploadImportingUserArticlesForm" method="post" enctype="multipart/form-data" role="form">
            <h2>Importing user articles</h2>
            <div class="form-group">
                <input type="file" id="import-user-articles" name="excel_file" accept=".xls,.xlsx" required class="center-block">
            </div>
            <button type="submit" class="btn btn-lg btn-success">Upload</button>
        </form>
    </div>
</div>