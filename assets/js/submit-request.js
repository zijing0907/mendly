document.addEventListener('DOMContentLoaded', () => {
  const fileInput = document.getElementById('reference_images');
  const previewContainer = document.getElementById('image-preview');
  const uploadBox = document.getElementById('upload-box');
  const counter = document.getElementById('image-count');

  if (!fileInput || !previewContainer || !uploadBox) return;

  const MAX_FILES = 5;
  uploadBox.addEventListener('click', () => {
    fileInput.click();
  });
  const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

  fileInput.addEventListener('change', () => {
    previewContainer.innerHTML = '';

    const files = Array.from(fileInput.files);

    if (files.length > MAX_FILES) {
      alert('You can upload a maximum of 5 images.');
      fileInput.value = '';
      previewContainer.innerHTML = '';
      if (counter) {
        counter.textContent = '0 / 5 images selected';
      }
      return;
    }

    files.forEach((file) => {
      if (!ALLOWED_TYPES.includes(file.type)) {
        alert(file.name + ' is not a supported image.');
        fileInput.value = '';
        previewContainer.innerHTML = '';
        if (counter) {
          counter.textContent = `${files.length} / 5 images selected`;
        }
        return;
      }

      const reader = new FileReader();

      reader.onload = function (e) {
        const image = document.createElement('img');
        image.src = e.target.result;
        image.className = 'preview-image';
        previewContainer.appendChild(image);
      };

      reader.readAsDataURL(file);
    });
  });
});