document.addEventListener('DOMContentLoaded', async () => {
    document.addEventListener("submit", handleDetailFormSubmit);

    checkAuth().then(async ({ loggedIn }) => {
        setupCommentUI(loggedIn);

        displayMessages();
        await fetchSession();
        loadPostDetail();
        loadComments();
        initPagination(changePage, "paginationComments");
        setupEventListeners();
    });
});


let CURRENT_USER_ID = null;
let CURRENT_USER_ROLE = "user";
let DELETE_IMAGE = false;

async function fetchSession() {
    const res = await fetch(`${API_BASE}/controllers/user_controller.php?action=check_session`, {
        credentials: "include"
    });

    const data = await res.json();

    if (data.loggedIn) {
        CURRENT_USER_ID = data.user_id;
        CURRENT_USER_ROLE = data.role;
    }
}

document.addEventListener("click", e => {

    if (e.target.classList.contains("cancel-post-btn")) {
        loadPostDetail();
    }

    if (e.target.classList.contains("delete-post-image-btn")) {
        DELETE_IMAGE = true;
        e.target.closest(".current-image-block")?.remove();
    }

    if (e.target.classList.contains("edit-comment-btn")) {
        const id = e.target.dataset.id;
        editComment(id);
    }

    if (e.target.classList.contains("delete-comment-btn")) {
        const id = e.target.dataset.id;
        deleteComment(id);
    }

    if (e.target.classList.contains("cancel-edit-comment-btn")) {
        loadComments();
    }
});

function loadPostDetail() {
    const postId = new URLSearchParams(window.location.search).get('id');

    if (!postId) {
        document.getElementById('postDetail').innerHTML =
            '<div><h1>404 NOT FOUND</h1><p class="error">Post not found.</p></div>';
        return;
    }

    fetch(`${API_BASE}/controllers/post_controller.php?action=get_post&id=${postId}`, {
        credentials: 'include'
    })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.post) {
                displayPost(data.post);
            } else {
                document.getElementById('postDetail').innerHTML = '<p class="error">Post not found.</p>';
                document.getElementById('commentsSection').style.display = 'none';
            }
        })
        .catch(() => {
            showMessage("Error loading post.", "error");
            document.getElementById('postDetail').innerHTML = `<p class="error">Error loading post.</p>`;
            document.getElementById('commentsSection').style.display = 'none';
        });
}

function displayPost(post) {
    const postDetail = document.getElementById('postDetail');

    const isAuthor = CURRENT_USER_ID === post.user_id;
    const isAdmin = CURRENT_USER_ROLE === "admin" || CURRENT_USER_ROLE === "owner";

    const dateDisplay =
        post.created_at !== post.updated_at
            ? `Posted: ${formatDate(post.created_at)} • Edited: ${formatDate(post.updated_at)}`
            : `Posted: ${formatDate(post.created_at)}`;

    postDetail.innerHTML = `
        <article class="post-detail">

            <div class="post-header-add">
                <h2>${escapeHtml(post.title)}</h2>

                <div class="post-actions">
                    ${isAuthor ? `<button class="edit-post-btn" data-id="${post.id}">Edit</button>` : ""}
                    ${(isAuthor || isAdmin) ? `<button class="delete-post-btn" data-id="${post.id}">Delete</button>` : ""}
                </div>
            </div>

            <div class="post-content-add">
                <p>${escapeHtml(post.content)}</p>
            </div>

            ${post.image ? `<img src="uploads/posts/${post.image}" class="post-image">` : ""}

            <div class="post-meta">
                <span>By ${escapeHtml(post.username)}</span>
                <br><br>
                <span>${dateDisplay}</span>
            </div>

            <svg class="meta-line" height="2">
                <line x1="0" y1="1" x2="100" y2="1" stroke="#000" stroke-width="0.5">
            </svg>

        </article>
    `;
}

function editPost(postId) {
    const postDiv = document.querySelector(".post-detail");
    const title = postDiv.querySelector("h2").textContent.trim();
    const content = postDiv.querySelector(".post-content-add p").textContent.trim();
    const imageElement = postDiv.querySelector(".post-image");
    const hasImage = !!imageElement;

    postDiv.innerHTML = `
        <form class="edit-post-form" data-id="${postId}" enctype="multipart/form-data">

            <div class="edit-field">
                <label>Title: <span class="required">*</span></label>
                <input type="text" class="edit-post-title" value="${escapeHtml(title)}">
            </div>
            <br>
            <div class="edit-field">
                <label>Content: <span class="required">*</span></label>
                <textarea class="edit-post-textarea">${escapeHtml(content)}</textarea>
            </div>
            <br>
            ${hasImage ? `
                <div class="current-image-block">
                    <p>Current image:</p>
                    <img src="${imageElement.src}" class="post-image">
                    <button type="button" class="delete-post-image-btn">Delete image</button>
                </div>
            ` : ""}
            <br>
            <div class="edit-field">
                <label>Upload new image:</label>
                <input type="file" name="new_image" class="edit-post-image">
            </div>
            <br>
            <div class="edit-post-actions">
                <button type="submit" class="save-comment-btn save-post-btn">Save</button>
                <button type="button" class="cancel-edit-btn cancel-post-btn">Cancel</button>
            </div>
            <span class="required-info">
                Fields marked with <span class="required">*</span> are required.
            </span>

        </form>
    `;
}

function loadComments(page = 1) {
    const postId = new URLSearchParams(window.location.search).get('id');
    if (!postId) return;

    fetch(`${API_BASE}/controllers/comment_controller.php?action=get_comments&post_id=${postId}&page=${page}`, {
        credentials: 'include'
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                displayComments(data.comments);
                if (data.pagination) setupPagination(data.pagination);
            }
            else document.getElementById('commentList').innerHTML = '<p>No comments yet.</p>';
        })
        .catch(err => {
            console.error('Load comments failed:', err);
            document.getElementById('commentList').innerHTML = '<p>Error loading comments.</p>';
        });
}

console.log("COMMENT FORM ELEMENT:", document.getElementById("commentForm"));
console.log("COMMENT FORM PARENT:", document.getElementById("commentForm")?.parentElement);
console.log("COMMENT FORM WRAPPER:", document.getElementById("commentForm")?.closest(".section"));

function setupCommentUI(loggedIn) {
    const form = document.getElementById("commentForm");
    const msg = document.getElementById("loginToComment");

    if (!form || !msg) return;

    if (loggedIn) {
        form.classList.remove("hidden");
        msg.classList.add("hidden");
    } else {
        form.classList.add("hidden");
        msg.classList.remove("hidden");
    }
}

function displayComments(comments) {
    const commentList = document.getElementById('commentList');

    if (!comments.length) {
        commentList.innerHTML = '<p>No comments yet. Be the first to comment!</p>';
        return;
    }

    commentList.innerHTML = comments.map(comment => {

        const isAuthor = CURRENT_USER_ID === comment.user_id;

        const isAdmin = CURRENT_USER_ROLE === "admin" || CURRENT_USER_ROLE === "owner";

        return `
            <div class="comment" id="comment-${comment.id}">
                <div class="comment-content">
                    <p>${escapeHtml(comment.content)}</p>
                </div>

                <div class="comment-meta">
                    <span>By ${escapeHtml(comment.username)}</span>
                
                    <span>
                        ${comment.created_at === comment.updated_at
                ? `Posted: ${formatDate(comment.created_at)}`
                : `Posted: ${formatDate(comment.created_at)} • Edited: ${formatDate(comment.updated_at)}`
            }
                     </span>

                    <div class="comment-actions">

                        ${isAuthor ? `
                            <button class="edit-comment-btn" data-id="${comment.id}">Edit</button>
                        ` : ''}

                        ${(isAuthor || isAdmin) ? `
                            <button class="delete-comment-btn" data-id="${comment.id}">Delete</button>
                        ` : ''}
                        
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function setupEventListeners() {
    const commentForm = document.getElementById('commentForm');
    if (commentForm) {
        commentForm.addEventListener('submit', handleCommentSubmit);
    }
}

function handleCommentSubmit(e) {
    e.preventDefault();

    const postId = new URLSearchParams(window.location.search).get('id');
    const content = document.getElementById('commentContent').value.trim();

    if (!content) {
        alert('Please enter a comment.');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'create');
    formData.append('post_id', postId);
    formData.append('content', content);

    fetch(`${API_BASE}/controllers/comment_controller.php`, {
        method: 'POST',
        body: formData,
        credentials: 'include'
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById('commentContent').value = '';
                loadComments();
            } else {
                showMessage(data.error, 'error');
            }
        })
        .catch(err => {
            console.error('Comment submission failed:', err);
            showMessage('Error adding comment.', 'error');
        });
}

function handleDetailFormSubmit(event) {
    const form = event.target;

    if (form.classList.contains("edit-comment-form")) {
        event.preventDefault();

        const commentId = form.dataset.id;
        const newContent = form.querySelector(".edit-comment-textarea").value.trim();

        updateComment(commentId, newContent);
        return;
    }

    if (form.classList.contains("edit-post-form")) {
        event.preventDefault();

        const postId = form.dataset.id;
        updatePost(postId);
        return;
    }

}

function updatePost(postId) {
    const newTitle = document.querySelector(".edit-post-title").value.trim();
    const newContent = document.querySelector(".edit-post-textarea").value.trim();
    const imageInput = document.querySelector(".edit-post-image");

    const formData = new FormData();
    formData.append("action", "edit");
    formData.append("post_id", postId);
    formData.append("title", newTitle);
    formData.append("content", newContent);

    if (imageInput && imageInput.files.length > 0) {
        formData.append("new_image", imageInput.files[0]);
    }

    if (DELETE_IMAGE) {
        formData.append("delete_image", "1");
    }


    fetch(`${API_BASE}/controllers/post_controller.php`, {
        method: "POST",
        credentials: "include",
        body: formData
    })
        .then(async res => {
            const raw = await res.text();

            try {
                return JSON.parse(raw);
            } catch (err) {
                showMessage("Server returned invalid JSON. Check console.", "error");
                throw err;
            }
        })
        .then(data => {
            if (data.success) {
                loadPostDetail();
            } else {
                showMessage(data.error, "error");
            }
        })
        .catch(err => {
            console.error("FINAL ERROR:", err);
            showMessage("Update failed", "error");
        });
}

function editComment(commentId) {
    const commentDiv = document.getElementById(`comment-${commentId}`);
    const currentContent = commentDiv.querySelector('.comment-content p').textContent;

    commentDiv.innerHTML = `
        <form class="edit-comment-form" data-id="${commentId}">
            <textarea class="edit-comment-textarea">${currentContent}</textarea>

            <div class="edit-comment-actions">
                <button type="submit" class="save-comment-btn" data-id="${commentId}">Save</button>
                <button type="button" class="cancel-edit-comment-btn">Cancel</button>
            </div>
        </form>
    `;
}

function updateComment(commentId) {
    const textarea = document.querySelector(`#comment-${commentId} .edit-comment-textarea`);
    const content = textarea.value.trim();

    if (!content) {
        alert('Comment cannot be empty.');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'edit');
    formData.append('comment_id', commentId);

    const postId = new URLSearchParams(window.location.search).get('id');
    formData.append('post_id', postId);

    formData.append('content', content);

    fetch(`${API_BASE}/controllers/comment_controller.php`, {
        method: 'POST',
        body: formData,
        credentials: 'include'
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                loadComments();
            } else {
                showMessage(data.error, 'error');
            }
        })
        .catch(err => {
            console.error('Update comment failed:', err);
            showMessage('Error updating comment.', 'error');
        });
}

function deleteComment(commentId) {
    if (!confirm('Are you sure you want to delete this comment?')) return;

    const postId = new URLSearchParams(window.location.search).get('id');

    fetch(`${API_BASE}/controllers/comment_controller.php?action=delete&id=${commentId}&post_id=${postId}`, {
        credentials: 'include'
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                loadComments();
            } else {
                showMessage(data.error, 'error');
            }
        })
        .catch(err => {
            console.error('Delete comment failed:', err);
            showMessage('Error deleting comment.', 'error');
        });
}

function setupPagination(p) {
    drawPagination({
        currentPage: p.page,
        totalPages: p.total_pages,
        hasPrevious: p.can_go_prev,
        hasNext: p.can_go_next,
        totalCount: p.total_comments,
        containerId: "paginationComments"
    });
}

function changePage(page) {
    loadComments(page);
}
