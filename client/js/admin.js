document.addEventListener("DOMContentLoaded", () => {
    checkAuth().then(data => {
        window.currentRole = data.role;

        if (!data.loggedIn || (data.role !== "admin" && data.role !== "owner")) {
            window.location.href = "../index.html?error=No permission";
            return;
        }

        loadUsers();

        document.addEventListener("click", (e) => {
            const btn = e.target;

            if (btn.classList.contains("promote-btn")) {
                promoteUser(btn.dataset.id);
            }

            if (btn.classList.contains("demote-btn")) {
                demoteUser(btn.dataset.id);
            }

            if (btn.classList.contains("delete-btn")) {
                deleteUser(btn.dataset.id);
            }
        });

    });
});

function loadUsers() {
    checkAuth().then(current => {

        fetch(`${API_BASE}/controllers/user_controller.php?action=get_users`, {
            credentials: 'include'
        })
            .then(res => res.json())
            .then(data => {

                if (!data.success) {
                    document.getElementById("userList").innerHTML = "Failed to load.";
                    return;
                }

                const html = `
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Posts</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.users.map(u => {

                            let actions = "";

                            if (current.role === "owner") {
                                if (u.role === "owner") {
                                    actions = `<span class="protected">Owner</span>`;
                                } else if (u.role === "admin") {
                                    actions = `
                                        <button class="button demote-btn" data-id="${u.id}">Demote</button>
                                        <button class="button delete-btn" data-id="${u.id}">Delete</button>
                                    `;
                                } else {
                                    actions = `
                                        <button class="button promote-btn" data-id="${u.id}">Make Admin</button>
                                        <button class="button delete-btn" data-id="${u.id}">Delete</button>
                                    `;
                                }
                            }

                            else if (current.role === "admin") {
                                if (u.role === "admin" || u.role === "owner") {
                                    actions = `<span class="protected">No access</span>`;
                                } else {
                                    actions = `
                                        <button class="button promote-btn" data-id="${u.id}">Make Admin</button>
                                        <button class="button delete-btn" data-id="${u.id}">Delete</button>
                                    `;
                                }
                            }

                            return `
                                <tr>
                                    <td>${u.id}</td>
                                    <td>${u.username}</td>
                                    <td>${u.email}</td>
                                    <td>${u.role}</td>
                                    <td>${u.post_count}</td>
                                    <td>${actions}</td>
                                </tr>
                            `;
                        }).join("")}
                    </tbody>
                </table>
                `;

                document.getElementById("userList").innerHTML = html;
            });
    });
}

function promoteUser(id) {
    const f = new FormData();
    f.append("action", "promote");
    f.append("user_id", id);

    fetch(`${API_BASE}/controllers/user_controller.php`, {
        method: "POST",
        body: f,
        credentials: "include"
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) loadUsers();
            else alert(data.error);
        });
}


function demoteUser(id) {
    const f = new FormData();
    f.append("action", "demote");
    f.append("user_id", id);

    fetch(`${API_BASE}/controllers/user_controller.php`, {
        method: "POST",
        body: f,
        credentials: "include"
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) loadUsers();
            else alert(data.error);
        });
}
function deleteUser(id) {
    if (!confirm("Delete this user?")) return;

    const f = new FormData();
    f.append("action", "delete_user");
    f.append("user_id", id);

    fetch(`${API_BASE}/controllers/user_controller.php`, {
        method: "POST",
        body: f,
        credentials: "include"
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) loadUsers();
            else alert(data.error);
        });
}
