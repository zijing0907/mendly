const expertRegistrationForm =
    document.getElementById("expertRegistrationForm");

const fullNameInput =
    document.getElementById("fullName");

const businessNameInput =
    document.getElementById("businessName");

const emailInput =
    document.getElementById("email");

const phoneInput =
    document.getElementById("phone");

const experienceInput =
    document.getElementById("experience");

const locationInput =
    document.getElementById("location");

const descriptionInput =
    document.getElementById("description");

const passwordInput =
    document.getElementById("password");

const confirmPasswordInput =
    document.getElementById("confirmPassword");

const termsInput =
    document.getElementById("terms");

const fullNameError =
    document.getElementById("fullNameError");

const businessNameError =
    document.getElementById("businessNameError");

const emailError =
    document.getElementById("emailError");

const phoneError =
    document.getElementById("phoneError");

const experienceError =
    document.getElementById("experienceError");

const locationError =
    document.getElementById("locationError");

const descriptionError =
    document.getElementById("descriptionError");

const passwordError =
    document.getElementById("passwordError");

const confirmPasswordError =
    document.getElementById("confirmPasswordError");

const termsError =
    document.getElementById("termsError");

const successMessage =
    document.getElementById("successMessage");

const showPasswordButton =
    document.getElementById("showPassword");

const showConfirmPasswordButton =
    document.getElementById("showConfirmPassword");

const characterCount =
    document.getElementById("characterCount");

const businessPhotoInput =
    document.getElementById("businessPhoto");

const businessPhotoError =
    document.getElementById("businessPhotoError");

const businessPhotoSuccess =
    document.getElementById("businessPhotoSuccess");

const businessPhotoUploadLabel =
    document.getElementById("businessPhotoUploadLabel");

const removeBusinessPhotoBtn =
    document.getElementById("removeBusinessPhotoBtn");

const photoPreviewImage =
    document.getElementById("photoPreviewImage");

const photoPlaceholder =
    document.getElementById("photoPlaceholder");

/* Preview uploaded business photo */

businessPhotoInput.addEventListener("change", function () {
    businessPhotoError.textContent = "";
    businessPhotoSuccess.textContent = "";

    const file = businessPhotoInput.files[0];

    if (!file) {
        return;
    }

    if (file.size > 5 * 1024 * 1024) {
        businessPhotoError.textContent =
            "Photo must be smaller than 5MB.";

        businessPhotoInput.value = "";

        return;
    }

    const reader = new FileReader();

    reader.onload = function (event) {
        photoPreviewImage.src = event.target.result;
        photoPreviewImage.hidden = false;
        photoPlaceholder.hidden = true;

        businessPhotoSuccess.textContent = "✓ Photo added successfully!";
        businessPhotoUploadLabel.textContent = "Change Photo";
        removeBusinessPhotoBtn.hidden = false;
    };

    reader.readAsDataURL(file);
});

/* Remove the selected photo and reset back to the placeholder */

removeBusinessPhotoBtn.addEventListener("click", function () {
    businessPhotoInput.value = "";
    businessPhotoError.textContent = "";
    businessPhotoSuccess.textContent = "";

    photoPreviewImage.hidden = true;
    photoPreviewImage.src = "";
    photoPlaceholder.hidden = false;

    businessPhotoUploadLabel.textContent = "Upload Photo";
    removeBusinessPhotoBtn.hidden = true;
});

/* Show or hide password */

showPasswordButton.addEventListener("click", function () {
    if (passwordInput.type === "password") {
        passwordInput.type = "text";
        showPasswordButton.textContent = "Hide";
    } else {
        passwordInput.type = "password";
        showPasswordButton.textContent = "Show";
    }
});

showConfirmPasswordButton.addEventListener(
    "click",
    function () {
        if (confirmPasswordInput.type === "password") {
            confirmPasswordInput.type = "text";
            showConfirmPasswordButton.textContent = "Hide";
        } else {
            confirmPasswordInput.type = "password";
            showConfirmPasswordButton.textContent = "Show";
        }
    }
);

/* Count description characters */

descriptionInput.addEventListener("input", function () {
    characterCount.textContent =
        descriptionInput.value.length;
});

/* Validate form */

expertRegistrationForm.addEventListener(
    "submit",
    function (event) {
        event.preventDefault();

        clearMessages();

        const fullName =
            fullNameInput.value.trim();

        const businessName =
            businessNameInput.value.trim();

        const email =
            emailInput.value.trim();

        const phone =
            phoneInput.value.trim();

        const experience =
            experienceInput.value;

        const location =
            locationInput.value.trim();

        const description =
            descriptionInput.value.trim();

        const password =
            passwordInput.value;

        const confirmPassword =
            confirmPasswordInput.value;

        let isValid = true;

        if (fullName === "") {
            fullNameError.textContent =
                "Full name is required.";

            isValid = false;
        }

        if (businessName === "") {
            businessNameError.textContent =
                "Business or workshop name is required.";

            isValid = false;
        }

        if (email === "") {
            emailError.textContent =
                "Email address is required.";

            isValid = false;
        } else if (!email.includes("@")) {
            emailError.textContent =
                "Please enter a valid email address.";

            isValid = false;
        }

        if (phone === "") {
            phoneError.textContent =
                "Phone number is required.";

            isValid = false;
        } else if (phone.length < 9) {
            phoneError.textContent =
                "Please enter a valid phone number.";

            isValid = false;
        }



        if (experience === "") {
            experienceError.textContent =
                "Please select your years of experience.";

            isValid = false;
        }

        if (location === "") {
            locationError.textContent =
                "Business location is required.";

            isValid = false;
        }

        if (description === "") {
            descriptionError.textContent =
                "Professional description is required.";

            isValid = false;
        } else if (description.length < 20) {
            descriptionError.textContent =
                "Please provide at least 20 characters.";

            isValid = false;
        }

        if (password === "") {
            passwordError.textContent =
                "Password is required.";

            isValid = false;
        } else if (password.length < 6) {
            passwordError.textContent =
                "Password must contain at least 6 characters.";

            isValid = false;
        }

        if (confirmPassword === "") {
            confirmPasswordError.textContent =
                "Please confirm your password.";

            isValid = false;
        } else if (confirmPassword !== password) {
            confirmPasswordError.textContent =
                "Passwords do not match.";

            isValid = false;
        }

        if (!termsInput.checked) {
            termsError.textContent =
                "You must agree to the terms and conditions.";

            isValid = false;
        }

        if (isValid) {
            // Client-side checks passed — hand off to the server
            // (save-expert.php) to validate, hash the password, store the
            // photo upload and insert the user + expert rows.
            expertRegistrationForm.submit();
        } else {
            // This is a long form — if the first problem field is above
            // the person's current scroll position (e.g. they clicked
            // submit while still down near the button), the error
            // messages can end up completely out of view, making it look
            // like nothing happened at all. Scroll straight to the first
            // one so it's impossible to miss.
            const fieldOrder = [
                { input: fullNameInput, error: fullNameError },
                { input: businessNameInput, error: businessNameError },
                { input: emailInput, error: emailError },
                { input: phoneInput, error: phoneError },
                { input: experienceInput, error: experienceError },
                { input: locationInput, error: locationError },
                { input: descriptionInput, error: descriptionError },
                { input: passwordInput, error: passwordError },
                { input: confirmPasswordInput, error: confirmPasswordError },
                { input: termsInput, error: termsError },
            ];

            const firstInvalid = fieldOrder.find(
                (field) => field.error.textContent !== ""
            );

            if (firstInvalid) {
                firstInvalid.input.scrollIntoView({
                    behavior: "smooth",
                    block: "center",
                });
                firstInvalid.input.focus();
            }
        }
    }
);

function clearMessages() {
    fullNameError.textContent = "";
    businessNameError.textContent = "";
    emailError.textContent = "";
    phoneError.textContent = "";
    experienceError.textContent = "";
    locationError.textContent = "";
    descriptionError.textContent = "";
    passwordError.textContent = "";
    confirmPasswordError.textContent = "";
    termsError.textContent = "";
    successMessage.textContent = "";
}