<?php

require_once __DIR__ . '/../config.php';

/**
 * CommentModel
 *
 * Handles all database operations related to post comments,
 * including creation, retrieval, updating, deletion, and pagination.
 */

class CommentModel
{
    /** @var PDO Database connection instance */
    private PDO $db;

    /**
     * Constructor for CommentModel.
     *
     * @param PDO $pdo The PDO database connection.
     */
    public function __construct(PDO $pdo)
    {
        $this->db = $pdo;
    }

    /**
     * Create a new comment for a specific post.
     *
     * @param int $user_id  The ID of the user creating the comment.
     * @param int $post_id  The ID of the post being commented on.
     * @param string $content The comment text.
     *
     * @return bool|string Returns true on success, or an error message string on failure.
     */
    public function createComment(int $user_id, int $post_id, string $content): bool|string
    {
        if (trim($content) === '') {
            return "The comment cannot be empty.";
        }

        $query = "INSERT INTO comments (user_id, post_id, content, created_at, updated_at)
                  VALUES (:user_id, :post_id, :content, NOW(), NOW())";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            'user_id' => $user_id,
            'post_id' => $post_id,
            'content' => $content,
        ]);

        return true;
    }

     /**
     * Retrieve a single comment by its ID.
     *
     * @param int $comment_id The ID of the comment to fetch.
     *
     * @return array|null Returns the comment as an associative array, or null if not found.
     */
    public function getCommentById(int $comment_id): ?array
    {
        $query = "SELECT *
                  FROM comments
                  WHERE id = :id
                  LIMIT 1";

        $stmt = $this->db->prepare($query);
        $stmt->execute(['id' => $comment_id]);

        $comment = $stmt->fetch();

        return $comment ?: null;
    }

    /**
     * Update an existing comment.
     *
     * @param int $comment_id The ID of the comment to update.
     * @param string $content The new content of the comment.
     *
     * @return bool|string Returns true on success, or an error message string on failure.
     */
    public function updateComment(int $comment_id, string $content): bool|string
    {
        if (trim($content) === '') {
            return "The comment cannot be empty.";
        }

        $query = "UPDATE comments
                  SET content = :content,
                      updated_at = NOW()
                  WHERE id = :id";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            'id' => $comment_id,
            'content' => $content,
        ]);

        return true;
    }

     /**
     * Delete a comment by its ID.
     *
     * @param int $comment_id The ID of the comment to delete.
     *
     * @return bool|string Returns true on success, or an error message string on failure.
     */
    public function deleteComment(int $comment_id): bool|string
    {
        $query = "DELETE FROM comments WHERE id = :id";

        $stmt = $this->db->prepare($query);
        $stmt->execute(['id' => $comment_id]);

        return true;
    }

    /**
     * Retrieve paginated comments for a specific post.
     *
     * @param int $postId   The ID of the post whose comments are being fetched.
     * @param int $page     The current page number.
     * @param int $perPage  The number of comments per page.
     *
     * @return array Returns an array containing:
     *               - 'comments': A list of comment records with usernames.
     *               - 'pagination': Metadata including page, total pages, comment count, and navigation flags.
     */
    public function getCommentsPaginated(int $postId, int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;

        $countStmt = $this->db->prepare("SELECT COUNT(*) AS total FROM comments WHERE post_id = :post_id");
        $countStmt->execute(['post_id' => $postId]);
        $totalComments = $countStmt->fetch()['total'];

        $totalPages = ceil($totalComments / $perPage);

        $query = "
        SELECT c.*, u.username
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.post_id = :post_id
        ORDER BY c.created_at DESC
        LIMIT :limit OFFSET :offset
    ";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':post_id', $postId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'comments' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'pagination' => [
                'page' => $page,
                'total_pages' => $totalPages,
                'total_comments' => $totalComments,
                'can_go_prev' => $page > 1,
                'can_go_next' => $page < $totalPages
            ]
        ];
    }
}

