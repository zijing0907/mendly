const customerRegistrationForm =
    document.getElementById("customerRegistrationForm");

const avatarInput =
    document.getElementById("avatar");

const avatarError =
    document.getElementById("avatarError");

const avatarSuccess =
    document.getElementById("avatarSuccess");

const avatarUploadLabel =
    document.getElementById("avatarUploadLabel");

const removeAvatarBtn =
    document.getElementById("removeAvatarBtn");

const photoPreviewImage =
    document.getElementById("photoPreviewImage");

const photoPlaceholder =
    document.getElementById("photoPlaceholder");

/* Preview uploaded avatar */

avatarInput.addEventListener("change", function () {
    avatarError.textContent = "";
    avatarSuccess.textContent = "";

    const file = avatarInput.files[0];

    if (!file) {
        return;
    }

    if (file.size > 5 * 1024 * 1024) {
        avatarError.textContent =
            "Photo must be smaller than 5MB.";

        avatarInput.value = "";

        return;
    }

    const reader = new FileReader();

    reader.onload = function (event) {
        photoPreviewImage.src = event.target.result;
        photoPreviewImage.hidden = false;
        photoPlaceholder.hidden = true;

        avatarSuccess.textContent = "✓ Photo added successfully!";
        avatarUploadLabel.textContent = "Change Photo";
        removeAvatarBtn.hidden = false;
    };

    reader.readAsDataURL(file);
});

/* Remove the selected avatar and reset back to the placeholder */

removeAvatarBtn.addEventListener("click", function () {
    avatarInput.value = "";
    avatarError.textContent = "";
    avatarSuccess.textContent = "";

    photoPreviewImage.hidden = true;
    photoPreviewImage.src = "";
    photoPlaceholder.hidden = false;

    avatarUploadLabel.textContent = "Upload Photo";
    removeAvatarBtn.hidden = true;
});

const fullNameInput =
    document.getElementById("fullName");

const emailInput =
    document.getElementById("email");

const phoneInput =
    document.getElementById("phone");

const locationInput =
    document.getElementById("location");

const passwordInput =
    document.getElementById("password");

const confirmPasswordInput =
    document.getElementById("confirmPassword");

const termsInput =
    document.getElementById("terms");

const fullNameError =
    document.getElementById("fullNameError");

const emailError =
    document.getElementById("emailError");

const phoneError =
    document.getElementById("phoneError");

const locationError =
    document.getElementById("locationError");

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

/* Validate form */

customerRegistrationForm.addEventListener(
    "submit",
    function (event) {
        event.preventDefault();

        clearMessages();

        const fullName =
            fullNameInput.value.trim();

        const email =
            emailInput.value.trim();

        const phone =
            phoneInput.value.trim();

        const location =
            locationInput.value.trim();

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

        if (location === "") {
            locationError.textContent =
                "Location is required.";

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
            // (save-customer.php) to validate, hash the password and
            // insert the new customer row.
            customerRegistrationForm.submit();
        } else {
            // If the first problem field is above the person's current
            // scroll position, the error message can end up completely
            // out of view, making it look like nothing happened at all.
            // Scroll straight to the first one so it's impossible to miss.
            const fieldOrder = [
                { input: fullNameInput, error: fullNameError },
                { input: emailInput, error: emailError },
                { input: phoneInput, error: phoneError },
                { input: locationInput, error: locationError },
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
    emailError.textContent = "";
    phoneError.textContent = "";
    locationError.textContent = "";
    passwordError.textContent = "";
    confirmPasswordError.textContent = "";
    termsError.textContent = "";
    successMessage.textContent = "";
}
