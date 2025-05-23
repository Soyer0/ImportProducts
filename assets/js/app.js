function handleUploadFormSubmit(event) {
    event.preventDefault();

    const form = event.target;
    const formData = new FormData(form);
    let url = '';
    let alertMessage = '';
    let inputToClearSelector = '';

    if (form.id === 'uploadImportingProductsCartForm') {
        url = '?action=handleImportingProductsCartForm';
        alertMessage = 'Products import done';
        inputToClearSelector = '#import-products-cart';
    } else if (form.id === 'uploadImportingUserArticlesForm') {
        url = '?action=handleImportingUserArticlesForm';
        alertMessage = 'User articles import done';
        inputToClearSelector = '#import-user-articles';
    } else {
        console.warn('Unknown form submitted:', form.id);
        return;
    }

    fetch(url, {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(result => {
            alert(alertMessage);
            const input = document.querySelector(inputToClearSelector);
            if (input) input.value = '';
        })
        .catch(error => {
            console.error('Upload failed:', error);
        });
}

document.getElementById('uploadImportingProductsCartForm').addEventListener('submit', handleUploadFormSubmit);
document.getElementById('uploadImportingUserArticlesForm').addEventListener('submit', handleUploadFormSubmit);
