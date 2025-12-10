<?php

require_once __DIR__ . '/../config.php';

/**
 * PostModel
 *
 * Handles all database operations related to posts,
 * including creation, retrieval, updating, deletion, and pagination.
 */

class PostModel
{
    /** @var PDO Database connection instance */
    private PDO $db;

    /**
     * Constructor for PostModel.
     *
     * @param PDO $pdo The PDO database connection.
     */
    public function __construct(PDO $pdo)
    {
        $this->db = $pdo;
    }

     /**
     * Create a new post.
     *
     * @param int $user_id  The ID of the user creating the post.
     * @param string $title The title of the post.
     * @param string $content The main text content of the post.
     * @param string|null $image Optional image filename associated with the post.
     *
     * @return bool|string Returns true on success, or an error message string on failure.
     */
    public function createPost(int $user_id, string $title, string $content, ?string $image = null): bool|string
    {
        $query = "INSERT INTO posts (user_id, title, content, image, created_at, updated_at)
              VALUES (:user_id, :title, :content, :image, NOW(), NOW())";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            'user_id' => $user_id,
            'title' => $title,
            'content' => $content,
            'image' => $image,
        ]);

        return true;
    }

    /**
     * Retrieve a post by its ID.
     *
     * Includes the author's username for convenience.
     *
     * @param int $post_id The ID of the post to retrieve.
     *
     * @return array|null Returns the post as an associative array, or null if not found.
     */
    public function getPostById(int $post_id): ?array
    {
        $query = "SELECT p.*, u.username
                  FROM posts p
                  JOIN users u ON p.user_id = u.id
                  WHERE p.id = :id
                  LIMIT 1";

        $stmt = $this->db->prepare($query);
        $stmt->execute(['id' => $post_id]);
        $post = $stmt->fetch();

        return $post ?: null;
    }

    /**
     * Update an existing post.
     *
     * @param int $post_id  The ID of the post to update.
     * @param string $title The updated title.
     * @param string $content The updated content.
     * @param string|null $image Optional new image filename.
     *
     * @return bool|string Returns true on success, or an error message string on validation failure.
     */
    public function updatePost(int $post_id, string $title, string $content, ?string $image = null): bool|string
{
    if (trim($title) === '' || trim($content) === '') {
        return "Title or content cannot be empty.";
    }

    $query = "UPDATE posts
              SET title = :title,
                  content = :content,
                  image = :image,
                  updated_at = NOW()
              WHERE id = :id";

    $stmt = $this->db->prepare($query);
    $stmt->execute([
        'id' => $post_id,
        'title' => $title,
        'content' => $content,
        'image' => $image
    ]);

    return true;
    }

     /**
     * Delete a post by its ID.
     *
     * @param int $post_id The ID of the post to delete.
     *
     * @return bool|string Returns true on success, or an error message string on failure.
     */
    public function deletePost(int $post_id): bool|string
    {
        $query = "DELETE FROM posts WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->execute(['id' => $post_id]);
        return true;
    }

    /**
     * Retrieve paginated posts.
     *
     * If $userId is provided, fetches only posts by that user.
     * Otherwise, retrieves all posts in the system.
     * Posts are ordered by most recent update.
     *
     * @param int $page The current page number.
     * @param int $perPage Number of posts per page.
     * @param int|null $userId Optional ID of a user to filter posts.
     *
     * @return array Returns an array containing:
     *               - 'posts': A list of post records with usernames.
     *               - 'pagination': Metadata including current page, total pages, post count, and navigation flags.
     */
    public function getPostsPaginated(int $page, int $perPage, ?int $userId = null): array
    {
        $offset = ($page - 1) * $perPage;

        if ($userId === null) {
            $countQuery = "SELECT COUNT(*) AS total FROM posts";
            $countStmt = $this->db->query($countQuery);
        } else {
            $countStmt = $this->db->prepare("SELECT COUNT(*) AS total FROM posts WHERE user_id = :uid");
            $countStmt->execute(['uid' => $userId]);
        }

        $totalPosts = $countStmt->fetch()['total'];
        $totalPages = ceil($totalPosts / $perPage);

        if ($userId === null) {
            $query = "SELECT p.*, u.username
                  FROM posts p
                  JOIN users u ON p.user_id = u.id
                  ORDER BY GREATEST(p.created_at, p.updated_at) DESC
                  LIMIT :limit OFFSET :offset";
            $stmt = $this->db->prepare($query);
        } else {
            $query = "SELECT p.*, u.username
                  FROM posts p
                  JOIN users u ON p.user_id = u.id
                  WHERE p.user_id = :uid
                  ORDER BY GREATEST(p.created_at, p.updated_at) DESC
                  LIMIT :limit OFFSET :offset";
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        }

        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'posts' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_posts' => $totalPosts,
                'total_pages' => $totalPages,
                'has_previous' => $page > 1,
                'has_next' => $page < $totalPages
            ]
        ];
    }
}