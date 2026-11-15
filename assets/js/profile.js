document.addEventListener("DOMContentLoaded", () => {

    /* ==========================================================
       Show / hide toggles for the three password fields
       ========================================================== */

    const passwordToggles = [
        { buttonId: "toggleCurrentPassword", inputId: "currentPassword" },
        { buttonId: "toggleNewPassword", inputId: "newPassword" },
        { buttonId: "toggleConfirmNewPassword", inputId: "confirmNewPassword" },
        { buttonId: "toggleDeletePassword", inputId: "deletePassword" },
    ];

    passwordToggles.forEach(({ buttonId, inputId }) => {
        const button = document.getElementById(buttonId);
        const input  = document.getElementById(inputId);

        if (!button || !input) return;

        button.addEventListener("click", () => {
            if (input.type === "password") {
                input.type = "text";
                button.textContent = "Hide";
            } else {
                input.type = "password";
                button.textContent = "Show";
            }
        });
    });

    /* ==========================================================
       Live preview when a new photo is selected — updates both the
       inline "Replace Photo" preview circle and the profile summary
       avatar at the top of the page, before Save Changes is even
       clicked.
       ========================================================== */

    const photoInputs = [
        { inputId: "businessPhoto", previewId: "businessPhotoPreview" },
        { inputId: "avatar", previewId: "avatarPreview" },
    ];

    const profileSummaryAvatar = document.getElementById("profileSummaryAvatar");

    // Remember what each preview looked like before any edits, so
    // clicking "Cancel" can put back the saved photo if the user picked
    // a new one but didn't actually save.
    const originalPreviewHtml = {};

    if (profileSummaryAvatar) {
        originalPreviewHtml.profileSummaryAvatar = profileSummaryAvatar.innerHTML;
    }

    photoInputs.forEach(({ previewId }) => {
        const preview = document.getElementById(previewId);
        if (preview) originalPreviewHtml[previewId] = preview.innerHTML;
    });

    photoInputs.forEach(({ inputId, previewId }) => {
        const input   = document.getElementById(inputId);
        const preview = document.getElementById(previewId);

        if (!input || !preview) return;

        input.addEventListener("change", () => {
            const file = input.files[0];

            if (!file) return;

            const reader = new FileReader();

            reader.onload = (event) => {
                const imageHtml =
                    '<img src="' + event.target.result + '" alt="New photo preview">';

                preview.innerHTML = imageHtml;

                if (profileSummaryAvatar) {
                    profileSummaryAvatar.innerHTML = imageHtml;
                }
            };

            reader.readAsDataURL(file);
        });
    });

    /* ==========================================================
       Toggle between read-only Account Details view and edit form
       ========================================================== */

    const accountDetailsView = document.getElementById("accountDetailsView");
    const accountDetailsFormEl = document.getElementById("accountDetailsForm");
    const editDetailsBtn = document.getElementById("editDetailsBtn");
    const cancelEditBtn = document.getElementById("cancelEditBtn");

    if (editDetailsBtn && accountDetailsView && accountDetailsFormEl) {
        editDetailsBtn.addEventListener("click", () => {
            accountDetailsView.style.display = "none";
            accountDetailsFormEl.style.display = "";
        });
    }

    if (cancelEditBtn && accountDetailsView && accountDetailsFormEl) {
        cancelEditBtn.addEventListener("click", () => {
            accountDetailsFormEl.style.display = "none";
            accountDetailsView.style.display = "";

            // Undo any unsaved photo selection — put the previews back to
            // what was actually saved, and clear the file inputs so a
            // future edit doesn't still show the old selection.
            photoInputs.forEach(({ inputId, previewId }) => {
                const input   = document.getElementById(inputId);
                const preview = document.getElementById(previewId);

                if (input) input.value = "";
                if (preview && originalPreviewHtml[previewId] !== undefined) {
                    preview.innerHTML = originalPreviewHtml[previewId];
                }
            });

            if (profileSummaryAvatar && originalPreviewHtml.profileSummaryAvatar !== undefined) {
                profileSummaryAvatar.innerHTML = originalPreviewHtml.profileSummaryAvatar;
            }
        });
    }

    /* ==========================================================
       Account Details form
       ========================================================== */

    const accountDetailsForm = document.getElementById("accountDetailsForm");

    if (accountDetailsForm) {
        const fullNameInput     = document.getElementById("fullName");
        const emailInput        = document.getElementById("email");
        const phoneInput        = document.getElementById("phone");
        const locationInput     = document.getElementById("location");
        const businessNameInput = document.getElementById("businessName");

        const fullNameError     = document.getElementById("fullNameError");
        const emailError        = document.getElementById("emailError");
        const phoneError        = document.getElementById("phoneError");
        const locationError     = document.getElementById("locationError");
        const businessNameError = document.getElementById("businessNameError");

        // Letters (incl. accented), spaces, apostrophes, hyphens and
        // periods only — no digits or other symbols.
        const namePattern  = /^[\p{L}\s'.-]+$/u;
        const phonePattern = /^[0-9+\-\s]+$/;

        accountDetailsForm.addEventListener("submit", function (event) {
            let isValid = true;

            fullNameError.textContent = "";
            emailError.textContent = "";
            phoneError.textContent = "";
            locationError.textContent = "";
            if (businessNameError) businessNameError.textContent = "";

            const fullName = fullNameInput.value.trim();
            const email    = emailInput.value.trim();
            const phone    = phoneInput.value.trim();
            const location = locationInput.value.trim();

            if (fullName === "") {
                fullNameError.textContent = "Full name is required.";
                isValid = false;
            } else if (!namePattern.test(fullName)) {
                fullNameError.textContent = "Full name can only contain letters, spaces, hyphens and apostrophes.";
                isValid = false;
            }

            if (email === "") {
                emailError.textContent = "Email address is required.";
                isValid = false;
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                emailError.textContent = "Please enter a valid email address.";
                isValid = false;
            }

            if (phone === "") {
                phoneError.textContent = "Phone number is required.";
                isValid = false;
            } else if (phone.length < 9 || !phonePattern.test(phone)) {
                phoneError.textContent = "Please enter a valid phone number (numbers only).";
                isValid = false;
            }

            if (location === "") {
                locationError.textContent = "Location is required.";
                isValid = false;
            }

            if (businessNameInput) {
                const businessName = businessNameInput.value.trim();

                if (businessName === "") {
                    businessNameError.textContent = "Business name is required.";
                    isValid = false;
                }
            }

            if (!isValid) {
                event.preventDefault();
            }
            // If valid, let the form submit normally to actions/update-profile.php
        });
    }

    /* ==========================================================
       Change Password form
       ========================================================== */

    const changePasswordForm = document.getElementById("changePasswordForm");

    if (changePasswordForm) {
        const currentPasswordInput = document.getElementById("currentPassword");
        const newPasswordInput     = document.getElementById("newPassword");
        const confirmPasswordInput = document.getElementById("confirmNewPassword");

        const currentPasswordError = document.getElementById("currentPasswordError");
        const newPasswordError     = document.getElementById("newPasswordError");
        const confirmPasswordError = document.getElementById("confirmNewPasswordError");

        changePasswordForm.addEventListener("submit", function (event) {
            let isValid = true;

            currentPasswordError.textContent = "";
            newPasswordError.textContent = "";
            confirmPasswordError.textContent = "";

            const currentPassword = currentPasswordInput.value;
            const newPassword     = newPasswordInput.value;
            const confirmPassword = confirmPasswordInput.value;

            if (currentPassword === "") {
                currentPasswordError.textContent = "Current password is required.";
                isValid = false;
            }

            if (newPassword === "") {
                newPasswordError.textContent = "New password is required.";
                isValid = false;
            } else if (newPassword.length < 6) {
                newPasswordError.textContent = "New password must be at least 6 characters.";
                isValid = false;
            }

            if (confirmPassword === "") {
                confirmPasswordError.textContent = "Please confirm your new password.";
                isValid = false;
            } else if (confirmPassword !== newPassword) {
                confirmPasswordError.textContent = "Passwords do not match.";
                isValid = false;
            }

            if (!isValid) {
                event.preventDefault();
            }
            // If valid, let the form submit normally to actions/update-password.php
        });
    }

    /* ==========================================================
       Delete Account form
       ========================================================== */

    const deleteAccountView = document.getElementById("deleteAccountView");
    const deleteAccountBtn = document.getElementById("deleteAccountBtn");
    const cancelDeleteBtn = document.getElementById("cancelDeleteBtn");
    const deleteAccountForm = document.getElementById("deleteAccountForm");

    if (deleteAccountBtn && deleteAccountView && deleteAccountForm) {
        deleteAccountBtn.addEventListener("click", () => {
            deleteAccountView.style.display = "none";
            deleteAccountForm.style.display = "";
        });
    }

    if (cancelDeleteBtn && deleteAccountView && deleteAccountForm) {
        cancelDeleteBtn.addEventListener("click", () => {
            deleteAccountForm.style.display = "none";
            deleteAccountView.style.display = "";
        });
    }

    if (deleteAccountForm) {
        const deletePasswordInput = document.getElementById("deletePassword");
        const deletePasswordError = document.getElementById("deletePasswordError");

        deleteAccountForm.addEventListener("submit", function (event) {
            deletePasswordError.textContent = "";

            if (deletePasswordInput.value === "") {
                deletePasswordError.textContent = "Please enter your password to confirm.";
                event.preventDefault();
                return;
            }

            const confirmed = window.confirm(
                "Are you sure you want to permanently delete your account? This cannot be undone."
            );

            if (!confirmed) {
                event.preventDefault();
            }
            // If confirmed, let the form submit normally to actions/delete-account.php
        });
    }
});
