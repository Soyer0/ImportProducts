document.getElementById('uploadForm').addEventListener('submit', function(e) {
    e.preventDefault();

    let formData = new FormData(this);

    fetch('?action=handleForm', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(result => {
            alert('done');
        })
        .catch(error => {
            console.error('Upload failed:', error);
        });
});