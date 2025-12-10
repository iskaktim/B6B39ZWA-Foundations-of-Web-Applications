document.addEventListener("DOMContentLoaded", () => {

    const form = document.getElementById("registerForm");

    const usernameInput = document.getElementById("username");
    const emailInput = document.getElementById("email");
    const passwordInput = document.getElementById("password");
    const confirmPasswordInput = document.getElementById("confirm_password");

    function clearPasswords() {
        passwordInput.value = "";
        confirmPasswordInput.value = "";
    }

    form.addEventListener("submit", async (e) => {
        e.preventDefault();

        const username = usernameInput.value.trim();
        const email = emailInput.value.trim();
        const password = passwordInput.value.trim();
        const confirmPassword = confirmPasswordInput.value;

        
        const errors = [];

        if (!username || !email || !password || !confirmPassword) {
            errors.push("All fields are required.");
        }

        if (email && !email.includes("@")) {
            errors.push("Invalid email address.");
        }

        if (password && password.length < 6) {
            errors.push("Password must be at least 6 characters long.");
        }

        if (password && confirmPassword && password !== confirmPassword) {
            errors.push("Passwords do not match.");
        }

        if (errors.length > 0) {
            showMessage(errors, "error"); 
            clearPasswords();
            return;
        }

        try {
            const formData = new FormData();
            formData.append("action", "register");
            formData.append("username", username);
            formData.append("email", email);
            formData.append("password", password);
            formData.append("confirm_password", confirmPassword);

            const response = await fetch("../server/controllers/user_controller.php?action=register", {
                method: "POST",
                credentials: "include",
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                window.location.href =
                    "login.html?success=" + encodeURIComponent(data.message);
            } else {
                showMessage(data.message || "Registration failed.", "error");
            }

        } catch (err) {
            showMessage("Server error. Try again.", "error");
        }
    });
});
