const POSTS_PER_PAGE = 5;


document.addEventListener("DOMContentLoaded", () => {
    displayMessages();

    checkAuth(true).then(() => {
        loadMyPosts(1);
        initPagination(changeMyPostsPage, "pagination");
    });
});




function loadMyPosts(page = 1) {
    fetch(`${API_BASE}/controllers/post_controller.php?action=get_posts&mode=my&page=${page}&per_page=${POSTS_PER_PAGE}`, {
        credentials: "include"
    })
        .then(res => {
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return res.json();
        })
        .then(data => {
            if (!data.success) {
                document.getElementById("postList").innerHTML = "<p>No posts found.</p>";
                return;
            }

            displayMyPosts(data.posts);
            setupPagination(data.pagination);
        })
        .catch(err => {
            document.getElementById("postList").innerHTML = "<p class='error'>Error loading posts.</p>";
        });
}

function displayMyPosts(posts) {
    const postList = document.getElementById('postList');

    if (posts.length === 0) {
        postList.innerHTML = '<p>You haven\'t created any posts yet. <a href="add_post.html">Create your first post!</a></p>';
        return;
    }

    postList.innerHTML = posts.map(post => `
        <article class="post" data-post-id="${post.id}">
            <div class="post-header">
                <h3><a href="detail_post.html?id=${post.id}" class="post-link">${escapeHtml(post.title)}</a></h3>
            </div>

            <div class="post-content">
                <p>${escapeHtml(post.content.substring(0, 200))}${post.content.length > 200 ? '...' : ''}</p>
            </div>

            ${post.image ? `<img src="uploads/posts/${post.image}" class="post-image">` : ''}
            
            <div class="post-meta">
                <span>
                    ${post.created_at !== post.updated_at
                        ? `Posted: ${formatDate(post.created_at)} • Edited: ${formatDate(post.updated_at)}`
                        : `Posted: ${formatDate(post.created_at)}`}
                </span>
            </div>
            <br>
            <div class="post-actions">
                    <button class="button delete-post-btn" data-id="${post.id}">Delete</button>
            </div>

            <svg class="meta-line" height="2">
                <line x1="0" y1="1" x2="100" y2="1" stroke="#000" stroke-width="0.5">
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


function changeMyPostsPage(page) {
    loadMyPosts(page);
    window.scrollTo(0, 0);
}
