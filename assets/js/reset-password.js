const resetPasswordForm = document.getElementById("resetPasswordForm");

if (resetPasswordForm) {
    const newPasswordInput = document.getElementById("newPassword");
    const confirmNewPasswordInput = document.getElementById("confirmNewPassword");

    const newPasswordError = document.getElementById("newPasswordError");
    const confirmNewPasswordError = document.getElementById("confirmNewPasswordError");

    const showNewPasswordButton = document.getElementById("showNewPassword");
    const showConfirmNewPasswordButton = document.getElementById("showConfirmNewPassword");

    showNewPasswordButton.addEventListener("click", function () {
        if (newPasswordInput.type === "password") {
            newPasswordInput.type = "text";
            showNewPasswordButton.textContent = "Hide";
        } else {
            newPasswordInput.type = "password";
            showNewPasswordButton.textContent = "Show";
        }
    });

    showConfirmNewPasswordButton.addEventListener("click", function () {
        if (confirmNewPasswordInput.type === "password") {
            confirmNewPasswordInput.type = "text";
            showConfirmNewPasswordButton.textContent = "Hide";
        } else {
            confirmNewPasswordInput.type = "password";
            showConfirmNewPasswordButton.textContent = "Show";
        }
    });

    resetPasswordForm.addEventListener("submit", function (event) {
        event.preventDefault();

        newPasswordError.textContent = "";
        confirmNewPasswordError.textContent = "";

        const newPassword = newPasswordInput.value;
        const confirmNewPassword = confirmNewPasswordInput.value;

        let isValid = true;

        if (newPassword === "") {
            newPasswordError.textContent = "Password is required.";
            isValid = false;
        } else if (newPassword.length < 6) {
            newPasswordError.textContent = "Password must contain at least 6 characters.";
            isValid = false;
        }

        if (confirmNewPassword === "") {
            confirmNewPasswordError.textContent = "Please confirm your new password.";
            isValid = false;
        } else if (confirmNewPassword !== newPassword) {
            confirmNewPasswordError.textContent = "Passwords do not match.";
            isValid = false;
        }

        if (isValid) {
            resetPasswordForm.submit();
        }
    });
}
