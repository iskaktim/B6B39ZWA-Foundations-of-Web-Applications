document.addEventListener("DOMContentLoaded", () => {
    displayMessages();

    checkAuth(true)
        .then(() => {
            loadProfile();
            setupForms();
        });
});

function loadProfile() {
    fetch(`${API_BASE}/controllers/user_controller.php?action=get_profile`, {
        credentials: 'include'
    })
        .then(res => {
            if (!res.ok) throw new Error('Failed to load profile');
            return res.json();
        })
        .then(data => {
            if (data.success) {
                const user = data.user;

                document.getElementById('joinedDate').textContent =
                    formatDate(user.created_at);

                document.getElementById('totalPosts').textContent =
                    user.post_count || 0;

                document.getElementById('profileEmail').textContent =
                    escapeHtml(user.email);

                document.getElementById('profileRole').textContent =
                    escapeHtml(user.role);

                document.getElementById('profileUsername').textContent =
                    escapeHtml(user.username);


                const avatarImg = document.getElementById('profileAvatar');

                if (user.avatar) {
                    avatarImg.src =
                        `${API_BASE}/avatar.php?filename=${user.avatar}&t=${Date.now()}`;

                } else {
                    avatarImg.src =
                        `${API_BASE}/avatar.php?t=${Date.now()}`;

                }
            }
        })
        .catch(() => {
            showMessage('Failed to load profile data', 'error');
        });
}

function setupForms() {
    document.getElementById('profileForm').addEventListener('submit', handleProfileUpdate);
    document.getElementById('avatarForm').addEventListener('submit', handleAvatarUpload);
    document.getElementById('deleteAvatarBtn').addEventListener('click', handleAvatarDelete);

    const newPassword = document.getElementById('new-password');
    const confirmPassword = document.getElementById('confirm-password');

    confirmPassword.addEventListener('input', () => {
        confirmPassword.setCustomValidity(
            newPassword.value !== confirmPassword.value ? 'Passwords do not match' : ''
        );
    });
}

function handleProfileUpdate(e) {
    e.preventDefault();

    const formData = new FormData(e.target);

    fetch(`${API_BASE}/controllers/user_controller.php`, {
        method: 'POST',
        credentials: 'include',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showMessage(data.message, 'success');
                updateAuthUI(true, data.username);
                loadProfile();
                document.getElementById('new-username').value = "";
                document.getElementById('new-email').value = "";
            } else {
                showMessage(data.error, 'error');
            }
        })
        .catch(() => {
            showMessage('Error updating profile', 'error');
        });
}

function handleAvatarUpload(e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    formData.append('action', 'upload_avatar');

    fetch(`${API_BASE}/controllers/user_controller.php`, {
        method: 'POST',
        credentials: 'include',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showMessage(data.message, 'success');
                const avatarImg = document.getElementById('profileAvatar');
                avatarImg.src = `../server/avatar.php?filename=${data.filename}&t=${Date.now()}`;
            } else {
                showMessage(data.error, 'error');
            }
        })
        .catch(() => {
            showMessage('Error uploading avatar', 'error');
        });
}

function handleAvatarDelete() {
    if (!confirm('Are you sure you want to delete your avatar?')) return;

    fetch(`${API_BASE}/controllers/user_controller.php?action=remove_avatar`, {
        credentials: 'include'
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showMessage(data.message, 'success');
                document.getElementById('profileAvatar').src = 'images/default-avatar.jpg';
            } else {
                showMessage(data.error, 'error');
            }
        })
        .catch(() => {
            showMessage('Error deleting avatar', 'error');
        });
}

document.getElementById("passwordForm").addEventListener("submit", async (e) => {
    e.preventDefault();

    const errorBox = document.getElementById("passwordErrors");
    errorBox.innerHTML = "";

    const current = document.getElementById("current-password").value.trim();
    const password = document.getElementById("new-password").value.trim();
    const confirm = document.getElementById("confirm-password").value.trim();

    const errors = [];

    if (!current || !password || !confirm) {
        errors.push("All fields are required.");
    }

    if (password.length < 6) {
        errors.push("New password must be at least 6 characters long.");
    }

    if (password !== confirm) {
        errors.push("Passwords do not match.");
    }

    if (password === current) {
        errors.push("New password must be different from the current password.");
    }

    if (errors.length > 0) {
        errorBox.innerHTML = errors.join("<br>");
        return;
    }


    try {
        const formData = new FormData();
        formData.append("action", "update_password");
        formData.append("current_password", current);
        formData.append("new_password", password);
        formData.append("confirm_password", confirm);

        const response = await fetch("../server/controllers/user_controller.php", {
            method: "POST",
            credentials: "include",
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showMessage(data.message, "success");
            e.target.reset();
        } else {
            showMessage(data.error || "Password update failed.", "error");
        }

    } catch (err) {
        showMessage("Server error. Try again.", "error");
    }
});
