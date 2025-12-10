<?php

require_once __DIR__ . '/../config.php';

/**
 * AuthMiddleware
 *
 * Provides access control checks for different user roles.
 * Ensures that only authenticated users, admins, or owners
 * can perform certain actions or access restricted pages.
 */

class AuthMiddleware
{
     /**
     * Require the user to be logged in.
     *
     * Redirects to the login page if the session does not contain a valid user ID.
     *
     * @return void
     */
    public static function requireLogin(): void
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: ../../client/login.html?error=' . urlencode('Log in to access.'));
            exit;
        }

    }

     /**
     * Require the user to have an admin or owner role.
     *
     * Ensures the user is logged in, then checks their role.
     * Redirects to the home page if the user is neither admin nor owner.
     *
     * @return void
     */
    public static function requireAdmin(): void
    {
        self::requireLogin();

        $role = $_SESSION['role'] ?? 'user';

        if ($role !== 'admin' && $role !== 'owner') {
            header('Location: ../../index.html?error=' . urlencode('No permission.'));
            exit;
        }
    }

    /**
     * Require the user to have the owner role.
     *
     * Only the user with the "owner" role is allowed to proceed.
     * Others are redirected to the home page.
     *
     * @return void
     */
    public static function requireOwner(): void
    {
        self::requireLogin();

        $role = $_SESSION['role'] ?? 'user';

        if ($role !== 'owner') {
            header('Location: ../../index.html?error=' . urlencode('Owner only.'));
            exit;
        }
    }

     /**
     * Require the user to be the owner, an admin, or the owner of the resource.
     *
     * This is used for actions such as deleting posts or comments.
     * Users with the "owner" or "admin" role are always allowed.
     * Otherwise, only the resource owner (matching user_id) may proceed.
     *
     * @param int $owner_id The ID of the user who owns the resource.
     *
     * @return void
     */
    public static function requireOwnerOrAdmin(int $owner_id): void
    {
        self::requireLogin();

        $currentUserId = $_SESSION['user_id'] ?? null;
        $currentRole = $_SESSION['role'] ?? 'user';

        if ($currentRole === 'owner' || $currentRole === 'admin') {
            return;
        }

        if ($currentUserId !== $owner_id) {
            header('Location: ../../index.html?error=' . urlencode('You are not allowed to modify this resource.'));
            exit;
        }
    }
}

