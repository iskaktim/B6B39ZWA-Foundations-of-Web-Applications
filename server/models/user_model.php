<?php

require_once __DIR__ . '/../config.php';

/**
 * UserModel
 *
 * Handles all database operations related to users,
 * including registration, authentication, profile updates,
 * password changes, avatar updates, and retrieving user profiles.
 */

class UserModel
{
    /** @var PDO Database connection instance */
    private PDO $db;

    /**
     * Constructor for UserModel.
     *
     * @param PDO $pdo The PDO database connection.
     */
    public function __construct(PDO $pdo)
    {
        $this->db = $pdo;
    }

    /**
     * Register a new user.
     *
     * @param string $username Username chosen by the user.
     * @param string $email User's email address.
     * @param string $password Plaintext password to be hashed before saving.
     *
     * @return bool|string Returns true on success, or an error message string on failure.
     */
    public function register(string $username, string $email, string $password): bool|string
    {
        if (trim($username) === '' || trim($email) === '' || trim($password) === '') {
            return "All fields are required.";
        }

        $query = "SELECT id FROM users WHERE username = :username OR email = :email";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            'username' => $username,
            'email'    => $email
        ]);

        if ($stmt->fetch()) {
            return "A user with that username or email already exists.";
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $query = "INSERT INTO users (username, email, password, role, created_at)
                  VALUES (:username, :email, :password, 'user', NOW())";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            'username' => $username,
            'email'    => $email,
            'password' => $hashedPassword,
        ]);

        return true;
    }

    /**
     * Authenticate a user with username and password.
     *
     * @param string $username The username of the user attempting to log in.
     * @param string $password The plaintext password provided by the user.
     *
     * @return bool|string Returns true on successful login, or an error message string on failure.
     */
    public function login(string $username, string $password): bool|string
    {
        $user = $this->getUserByUsername($username);

        if (!$user || !password_verify($password, $user['password'])) {
            return "Invalid username or password.";
        }

        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role']     = $user['role'];

        return true;
    }

    /**
     * Retrieve a user by their ID.
     *
     * @param int $user_id The ID of the user.
     *
     * @return array|null Returns an associative array of user data, or null if not found.
     */
    public function getUserById(int $user_id): ?array
    {
        $query = "SELECT id, username, email, role, avatar, created_at
                  FROM users
                  WHERE id = :id
                  LIMIT 1";

        $stmt = $this->db->prepare($query);
        $stmt->execute(['id' => $user_id]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

      /**
     * Retrieve a user by their username.
     *
     * @param string $username The username to search for.
     *
     * @return array|null Returns user data including password hash, or null if not found.
     */
    public function getUserByUsername(string $username): ?array
    {
        $query = "SELECT * FROM users WHERE username = :username LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    /**
     * Log the current user out by clearing the session.
     *
     * @return void
     */
    public function logout(): void
    {
        session_unset();
        session_destroy();
    }

    /**
     * Update a user's profile information (username and email).
     *
     * @param int $user_id The ID of the user being updated.
     * @param string $username New username.
     * @param string $email New email address.
     *
     * @return bool|string Returns true on success, or an error message string on failure or validation error.
     */
    public function updateProfile(int $user_id, string $username, string $email): bool|string
    {
        if (trim($username) === '' || trim($email) === '') {
            return "Username and email cannot be empty.";
        }

        $query = "SELECT id FROM users WHERE (username = :username OR email = :email) AND id != :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            'username' => $username,
            'email'    => $email,
            'user_id'  => $user_id
        ]);

        if ($stmt->fetch()) {
            return "Username or email already exists.";
        }

        $query = "UPDATE users SET username = :username, email = :email, updated_at = NOW() WHERE id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            'username' => $username,
            'email'    => $email,
            'user_id'  => $user_id
        ]);

        if ($user_id === ($_SESSION['user_id'] ?? 0)) {
            $_SESSION['username'] = $username;
        }

        return true;
    }

    /**
     * Update a user's password.
     *
     * @param int $user_id The ID of the user.
     * @param string $current_password The current password for verification.
     * @param string $new_password The new password to be set.
     *
     * @return bool|string Returns true on success, or an error message string on failure.
     */
    public function updatePassword(int $user_id, string $current_password, string $new_password): bool|string
    {
        if (trim($new_password) === '') {
            return "New password cannot be empty.";
        }

        $query = "SELECT password FROM users WHERE id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->execute(['user_id' => $user_id]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($current_password, $user['password'])) {
            return "Current password is incorrect.";
        }

        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        $query = "UPDATE users SET password = :password, updated_at = NOW() WHERE id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            'password' => $hashed_password,
            'user_id'  => $user_id
        ]);

        return true;
    }

    /**
     * Update a user's avatar.
     *
     * @param int $user_id The ID of the user.
     * @param string $avatar_filename The filename of the uploaded avatar image.
     *
     * @return bool|string Returns true on success, or an error message string on failure.
     */
    public function updateAvatar(int $user_id, string $avatar_filename): bool|string
    {
        $query = "UPDATE users SET avatar = :avatar, updated_at = NOW() WHERE id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            'avatar'  => $avatar_filename,
            'user_id' => $user_id
        ]);

        return true;
    }

    /**
     * Remove a user's avatar.
     *
     * @param int $user_id The ID of the user.
     *
     * @return bool|string Returns true on success.
     */
    public function removeAvatar(int $user_id): bool|string
    {
        $query = "UPDATE users SET avatar = NULL, updated_at = NOW() WHERE id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->execute(['user_id' => $user_id]);

        return true;
    }

    /**
     * Retrieve full user profile information including post count.
     *
     * @param int $user_id The ID of the user.
     *
     * @return array|null Returns an associative array of user profile data, or null if user not found.
     */
    public function getUserProfile(int $user_id): ?array
    {
        $query = "SELECT u.id, u.username, u.email, u.avatar, u.role, u.created_at,
                     COUNT(p.id) AS post_count
                  FROM users u
                  LEFT JOIN posts p ON u.id = p.user_id
                  WHERE u.id = :user_id
                  GROUP BY u.id";

        $stmt = $this->db->prepare($query);
        $stmt->execute(['user_id' => $user_id]);
        $user = $stmt->fetch();

        return $user ?: null;
    }
}
