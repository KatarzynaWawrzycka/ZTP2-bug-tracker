<?php

/**
 * User service interface.
 */

namespace App\Service;

use App\Entity\User;
use Knp\Component\Pager\Pagination\PaginationInterface;

/**
 * Interface UserServiceInterface.
 */
interface UserServiceInterface
{
    /**
     * Get paginated list of users.
     */
    public function getPaginatedList(int $page): PaginationInterface;

    /**
     * Get single user details.
     */
    public function getUserDetails(int $id): ?User;

    /**
     * Count all admin users.
     */
    public function countAdmins(): int;

    public function findWithStats(int $id): array;

    /**
     * Toggle ROLE_ADMIN for user.
     */
    public function toggleAdminRole(User $user): void;

    /**
     * Delete user safely (with admin rules).
     */
    public function delete(User $user): void;

    /**
     * Change password.
     */
    public function changePassword(User $user, string $plainPassword): void;

    public function changeEmail(User $user, string $email): void;

    public function findAdmins(): array;

    /**
     * Register new user.
     */
    public function register(User $user, string $plainPassword): void;
}
