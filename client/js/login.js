document.addEventListener("DOMContentLoaded", () => {

    const form = document.getElementById("loginForm");

    form.addEventListener("submit", async (e) => {
        e.preventDefault();

        const username = document.getElementById("username").value.trim();
        const password = document.getElementById("password").value;

        if (!username || !password) {
            showMessage("Username and password are required.", "error");
            return;
        }

        try {
            const formData = new FormData();
            formData.append("action", "login");
            formData.append("username", username);
            formData.append("password", password);

            const response = await fetch("../server/controllers/user_controller.php?action=login", {
                method: "POST",
                credentials: "include",
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                window.location.href =
                    "../index.html?success=" + encodeURIComponent("Logged in successfully");
            } else {
                showMessage(data.message || "Login failed. Incorrect password or username.", "error");
                document.getElementById("password").value = "";
            }

        } catch {
            showMessage("Server error. Try again.", "error");
        }
    });
});
