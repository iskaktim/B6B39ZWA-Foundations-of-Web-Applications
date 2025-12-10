document.addEventListener("DOMContentLoaded", () => {
    checkAuth(true).then(data => {
        if (!data.loggedIn) return;

        const form = document.getElementById("postForm");
        if (!form) return;

        form.addEventListener("submit", handleCreatePost);
    });
});

async function handleCreatePost(event) {
    event.preventDefault();

    const title = document.getElementById("title").value.trim();
    const content = document.getElementById("content").value.trim();
    const imageInput = document.getElementById("image");
    const imageFile = imageInput?.files?.[0] || null;

    if (!title || !content) {
        showMessage("Title and content are required.", "error");
        return;
    }

    const formData = new FormData();
    formData.append("action", "create");
    formData.append("title", title);
    formData.append("content", content);

    if (imageFile) {
        formData.append("image", imageFile);
    }

    try {
        const response = await fetch(
            `${API_BASE}/controllers/post_controller.php?action=create`,
            {
                method: "POST",
                body: formData,
                credentials: "include"
            }
        );

        const data = await response.json();

        if (data.success) {
            window.location.href = "../index.html?success=" + encodeURIComponent("Post created.");
        } else {
            showMessage(data.error || "Failed to create post.", "error");
        }

    } catch {
        showMessage("Something went wrong. Try again.", "error");
    }
}
