let currentUserRole = "user";

const POSTS_PER_PAGE = 5;

document.addEventListener("DOMContentLoaded", () => {
    displayMessages();
    checkAuth(false);

    initPagination(changePage);
    loadPosts(1);
});

function loadPosts(page = 1) {
    fetch(`${API_BASE}/controllers/post_controller.php?action=get_posts&page=${page}&per_page=${POSTS_PER_PAGE}`, {
        credentials: "include",
        headers: { 'Accept': 'application/json' }
    })
        .then(res => {
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return res.json();
        })
        .then(data => {
            if (data.success) {
                displayPosts(data.posts);
                setupPagination(data.pagination);
            } else {
                showMessage('Failed to load posts: ' + (data.error || 'Unknown error'), 'error');

            }
        })
        .catch(() => {
            showMessage('Error loading posts.', 'error');
        });
}

function displayPosts(posts) {
    const postList = document.getElementById('postList');
    if (!postList) return;

    if (!posts || posts.length === 0) {
        postList.innerHTML = '<p>No posts yet. Be the first to post!</p>';
        return;
    }

    postList.innerHTML = posts.map(post => `
        <article class="post" data-post-id="${post.id}">
            <h3><a href="/~iskaktim/client/detail_post.html?id=${post.id}" class="post-link">${escapeHtml(post.title)}</a></h3>

            <p>${escapeHtml(post.content.substring(0, 200))}${post.content.length > 200 ? '...' : ''}</p>

                ${post.image ? `<img src="/~iskaktim/client/uploads/posts/${post.image}" class="post-image">` : ''}

            <div class="post-meta">
                <span>By ${escapeHtml(post.username)}</span>
                <br><br>
                <span>
                    ${post.created_at !== post.updated_at
                        ? `Posted: ${formatDate(post.created_at)} • Edited: ${formatDate(post.updated_at)}`
                        : `Posted: ${formatDate(post.created_at)}`}
                </span>
            </div>

            <br>

            <div class="post-actions">
                ${(currentUserRole === "admin" || currentUserRole === "owner")
            ? `<button class="button delete-post-btn" data-id="${post.id}">Delete</button>`
            : ""
        }
            </div>

            <svg class="meta-line" height="2">
                <line x1="0" y1="1" x2="100" y2="1" stroke="#000" stroke-width="0.5"></line>
            </svg>
        </article>
    `).join('');
}

function setupPagination(p) {
    drawPagination({
        currentPage: p.current_page,
        totalPages: p.total_pages,
        hasPrevious: p.has_previous,
        hasNext: p.has_next,
        totalCount: p.total_posts,
        containerId: "pagination"
    });
}

function changePage(page) {
    loadPosts(page);
    window.scrollTo(0, 0);
}