const API_BASE = "/~iskaktim/server";

const pagesWithoutSidebar = ["add_post.html", "detail_post.html"];
const pagesWithoutAddPostButton = ["add_post.html", "detail_post.html"];


function getCurrentPage() {
    return window.location.pathname.split("/").pop();
}

document.addEventListener("DOMContentLoaded", () => {
    injectHeader();
    injectSidebar();
    injectFooter();

    checkAuth().then(data => {
        updateAuthUI(data.loggedIn, data.username, data.role);
    });
});

function injectHeader() {
    const page = getCurrentPage();

    const disabledPages = ["login.html", "register.html"];
    if (disabledPages.includes(page)) return;

    const simplified = ["add_post.html", "detail_post.html"].includes(page);
    const hideAddPostButton = ["add_post.html"].includes(page);

    let headerHTML = "";

    if (simplified) {

        headerHTML = `
            <header>
                <h1>Discussion Forum</h1>
                <div class="header-buttons">
                    <a href="/~iskaktim/index.html" class="button-link">Forum</a>
                    <a id="authButton" href="/~iskaktim/client/login.html" class="button-link">Login</a>
                </div>
            </header>
        `;

    } else {

        headerHTML = `
            <header>
                <h1>Discussion Forum</h1>
                <div class="header-buttons">
                    ${hideAddPostButton ? "" : `<a href="/~iskaktim/client/add_post.html" class="button-link">Add a post</a>`}
                    <a id="authButton" href="/~iskaktim/client/login.html" class="button-link">Login</a>
                </div>
            </header>
        `;

    }

    document.body.insertAdjacentHTML("afterbegin", headerHTML);
}


function injectSidebar() {
    const page = getCurrentPage();
    const hideSidebar = ["add_post.html", "edit_post.html", "detail_post.html"].includes(page);

    if (hideSidebar) return;

    const container = document.querySelector(".container");
    const main = document.querySelector(".content");

    if (!container || !main) return;

    const sidebarHTML = `
        <aside class="sidebar">
            <h2 id="sidebarUsername">Guest</h2>
            <nav>
                <a href="/~iskaktim/client/profile.html">Profile</a>
                <a href="/~iskaktim/index.html">Forum</a>
                <a href="/~iskaktim/client/my_posts.html">My Posts</a>
                <a id="adminLink" href="/~iskaktim/client/admin.html" class="hidden">Admin</a>
            </nav>
        </aside>
    `;

    main.insertAdjacentHTML("beforebegin", sidebarHTML);
}


function injectFooter() {

    const page = getCurrentPage();

    const disabledPages = ["login.html", "register.html"];
    if (disabledPages.includes(page)) return;

    const html = `
        <footer>
            <p>© 2025 ZWA Forum Project by Timur Iskakov</p>
        </footer>
    `;
    document.body.insertAdjacentHTML("beforeend", html);
}

function escapeHtml(text) {
    if (text == null) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function updateAuthUI(isLoggedIn, username = "Guest", role = "user") {

    currentUserRole = role;

    const sidebarUser = document.getElementById("sidebarUsername");
    const authButton = document.getElementById("authButton");
    const adminLink = document.getElementById("adminLink");

    if (sidebarUser) sidebarUser.textContent = username;

    if (adminLink) {
        if (role === "admin" || role === "owner") {
            adminLink.style.display = "block";
        } else {
            adminLink.style.display = "none";
        }
    }


    if (!authButton) return;

    if (isLoggedIn) {
        authButton.textContent = "Logout";
        authButton.href = "#";
        authButton.onclick = logout;
    } else {
        authButton.textContent = "Login";
        authButton.href = "/~iskaktim/client/login.html";
        authButton.onclick = null;
    }
}

function checkAuth(requireLogin = false) {
    return fetch(`${API_BASE}/controllers/user_controller.php?action=check_session`, {
        credentials: "include"
    })
        .then(res => res.json())
        .then(data => {

            const logged = data.loggedIn === true;
            const username = data.username || "Guest";
            const role = data.role || "user";

            updateAuthUI(logged, username, role);

            if (requireLogin && !logged) {
                window.location.href =
                    "dashboard.html?error=" + encodeURIComponent("Please log in.");
                return;
            }

            return {
                loggedIn: logged,
                username: username,
                role: role
            };
        })
        .catch(() => {
            updateAuthUI(false);

            if (requireLogin) {
                window.location.href =
                    "dashboard.html?error=" + encodeURIComponent("Please log in.");
            }

            return {
                loggedIn: false,
                username: "Guest",
                role: "user"
            };
        });
}

function logout(e) {
    if (e) e.preventDefault();

    fetch(`${API_BASE}/controllers/user_controller.php?action=logout`, {
        credentials: "include"
    })
        .then(res => res.json())
        .then(() => {
            window.location.href =
                "/~iskaktim/client/login.html?success=" + encodeURIComponent("Logged out successfully");
        })
        .catch(() => {
            window.location.href = "/~iskaktim/client/login.html";
        });
}

function displayMessages() {
    const params = new URLSearchParams(window.location.search);
    const messagesDiv = document.getElementById("messages");
    if (!messagesDiv) return;

    if (params.has("success")) {
        messagesDiv.innerHTML =
            `<div class="message success">${escapeHtml(params.get("success"))}</div>`;
        setTimeout(() => messagesDiv.innerHTML = "", 3000);
    } else if (params.has("error")) {
        messagesDiv.innerHTML =
            `<div class="message error">${escapeHtml(params.get("error"))}</div>`;
    }
}

function showMessage(text, type = "info") {
    let msgDiv = document.getElementById("messages");

    if (!msgDiv) {
        msgDiv = document.createElement("div");
        msgDiv.id = "messages";

        const container = document.querySelector(".container") || document.body;
        container.prepend(msgDiv);
    }

    msgDiv.className = "";
    msgDiv.classList.add("message", type);

    if (Array.isArray(text)) {
        msgDiv.innerHTML =
            "<ul>" + text.map(t => `<li>${escapeHtml(t)}</li>`).join("") + "</ul>";
    } else {
        msgDiv.innerHTML = `<p>${escapeHtml(text)}</p>`;
    }

    if (type === "success" || type === "info") {
        setTimeout(() => {
            if (msgDiv) msgDiv.innerHTML = "";
        }, 3000);
    }

    window.scrollTo(0, 0);
}

function formatDate(dateString) {
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return dateString;

        const d = String(date.getDate()).padStart(2, "0");
        const m = String(date.getMonth() + 1).padStart(2, "0");
        const y = date.getFullYear();
        const h = String(date.getHours()).padStart(2, "0");
        const min = String(date.getMinutes()).padStart(2, "0");

        return `${d}.${m}.${y} ${h}:${min}`;
    } catch {
        return dateString;
    }
}

function initPagination(changePageFn, containerId = "pagination") {
    const paginationDiv = document.getElementById(containerId);
    if (!paginationDiv) return;

    window._globalChangePage = changePageFn;

    paginationDiv.addEventListener("click", (event) => {
        if (event.target.tagName !== "BUTTON") return;

        const page = parseInt(event.target.dataset.page);
        if (!isNaN(page)) {
            window._globalChangePage(page);
            window.scrollTo(0, 0);
        }
    });
}
window.initPagination = initPagination;

function drawPagination({
    currentPage,
    totalPages,
    hasPrevious,
    hasNext,
    totalCount,
    containerId = "pagination"
}) {
    const paginationDiv = document.getElementById(containerId);
    if (!paginationDiv) return;

    if (totalPages <= 1) {
        paginationDiv.innerHTML = "";
        return;
    }

    let html = "";

    if (hasPrevious) {
        html += `<button class="page-btn" data-page="${currentPage - 1}">Previous</button>`;
    }

    for (let p = 1; p <= totalPages; p++) {
        html += `<button class="page-btn ${p === currentPage ? "active-page" : ""}" 
                    data-page="${p}">${p}</button>`;
    }

    if (hasNext) {
        html += `<button class="page-btn" data-page="${currentPage + 1}">Next</button>`;
    }

    html += `<span class="page-info">Page ${currentPage} of ${totalPages} (${totalCount} objects)</span>`;

    paginationDiv.innerHTML = html;
}


window.drawPagination = drawPagination;



function deletePost(postId) {
    if (!confirm('Are you sure you want to delete this post? This action cannot be undone.')) {
        return;
    }

    fetch(`${API_BASE}/controllers/post_controller.php?action=delete&id=${postId}`, {
        credentials: 'include'
    })
        .then(res => {
            const contentType = res.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return res.json();
            } else {
                return res.text().then(text => {
                    throw new Error(`Expected JSON but got: ${text.substring(0, 100)}`);
                });
            }
        })
        .then(data => {
            if (!data.success) {
                showMessage(data.error || 'Failed to delete post.', 'error');
                return;
            }

            showMessage(data.message || 'Post deleted successfully!', 'success');

            if (window.location.pathname.includes("detail_post.html")) {
                setTimeout(() => {
                    window.location.href = "../index.html";
                }, 400);
                return;
            }


            const elemId = document.querySelector(`#post-${postId}`);

            const elemData = document.querySelector(`[data-post-id="${postId}"]`);

            if (elemId) elemId.remove();
            if (elemData) elemData.remove();

            if (typeof loadMyPosts === "function") {
                setTimeout(() => loadMyPosts(1), 400);
            }

            if (typeof loadPosts === "function") {
                setTimeout(() => loadPosts(1), 400);
            }
        })
        .catch(() => {
            showMessage('Error deleting post.', 'error');
        });
}

document.addEventListener("click", (event) => {
    const btn = event.target;

    if (btn.classList.contains("edit-post-btn")) {
        const postId = btn.dataset.id;
        if (postId) editPost(postId);
        return;
    }

    if (btn.classList.contains("delete-post-btn")) {
        const postId = btn.dataset.id;
        if (postId) deletePost(postId);
        return;
    }
});
