document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector('form[action="actions/save-service.php"]');

    if (!form) return;

    const categoryInput = document.getElementById("categoryId");
    const titleInput     = document.getElementById("title");
    const priceInput     = document.getElementById("startingPrice");
    const durationInput  = document.getElementById("estimatedDuration");

    const categoryError = document.getElementById("categoryIdError");
    const titleError     = document.getElementById("titleError");
    const priceError     = document.getElementById("startingPriceError");
    const durationError  = document.getElementById("estimatedDurationError");

    const photoInput   = document.getElementById("servicePhotos");
    const photoPreview = document.getElementById("photoPreview");
    const photoError   = document.getElementById("servicePhotosError");
    const MAX_PHOTOS   = 6;
    const MAX_BYTES    = 5 * 1024 * 1024;
    const PHOTO_TYPES  = ["image/jpeg", "image/png", "image/webp"];

    // How many photos the service will end up with after this save
    function photoTotal() {
        const existing = Number(photoInput.dataset.existing || 0);
        const removing = form.querySelectorAll('input[name="removePhotos[]"]:checked').length;
        return existing - removing + photoInput.files.length;
    }

    // Returns an error message for the chosen photos, or "" if they're fine
    function photoProblem() {
        for (const file of photoInput.files) {
            if (!PHOTO_TYPES.includes(file.type)) return `${file.name} isn't a JPG, PNG or WEBP image.`;
            if (file.size > MAX_BYTES) return `${file.name} is bigger than 5MB.`;
        }
        if (photoTotal() > MAX_PHOTOS) return `A service can have up to ${MAX_PHOTOS} photos.`;
        return "";
    }

    // Show thumbnails of the newly chosen photos
    photoInput.addEventListener("change", () => {
        photoPreview.innerHTML = "";
        for (const file of photoInput.files) {
            if (!PHOTO_TYPES.includes(file.type)) continue;
            const tile = document.createElement("div");
            tile.className = "photo-tile";
            const img = document.createElement("img");
            img.alt = file.name;
            img.src = URL.createObjectURL(file);
            tile.appendChild(img);
            photoPreview.appendChild(tile);
        }
        photoError.textContent = photoProblem();
    });

    form.addEventListener("submit", function (event) {
        let isValid = true;

        categoryError.textContent = "";
        titleError.textContent = "";
        priceError.textContent = "";
        durationError.textContent = "";
        photoError.textContent = "";

        if (categoryInput.value === "") {
            categoryError.textContent = "Please select a category.";
            isValid = false;
        }

        if (titleInput.value.trim() === "") {
            titleError.textContent = "Service title is required.";
            isValid = false;
        }

        const price = priceInput.value.trim();

        if (price === "") {
            priceError.textContent = "Starting price is required.";
            isValid = false;
        } else if (isNaN(price) || Number(price) <= 0) {
            priceError.textContent = "Please enter a valid price.";
            isValid = false;
        }

        if (durationInput.value.trim() === "") {
            durationError.textContent = "Estimated duration is required.";
            isValid = false;
        }

        const problem = photoProblem();
        if (problem) {
            photoError.textContent = problem;
            isValid = false;
        }

        if (!isValid) {
            event.preventDefault();
        }
        // If valid, let the form submit normally to actions/save-service.php
    });
});
