document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector('form[action="save-service.php"]');

    if (!form) return;

    const categoryInput = document.getElementById("categoryId");
    const titleInput     = document.getElementById("title");
    const priceInput     = document.getElementById("startingPrice");
    const durationInput  = document.getElementById("estimatedDuration");

    const categoryError = document.getElementById("categoryIdError");
    const titleError     = document.getElementById("titleError");
    const priceError     = document.getElementById("startingPriceError");
    const durationError  = document.getElementById("estimatedDurationError");

    form.addEventListener("submit", function (event) {
        let isValid = true;

        categoryError.textContent = "";
        titleError.textContent = "";
        priceError.textContent = "";
        durationError.textContent = "";

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

        if (!isValid) {
            event.preventDefault();
        }
        // If valid, let the form submit normally to save-service.php
    });
});
