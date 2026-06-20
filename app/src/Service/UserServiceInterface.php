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
     * Get paginated list.
     *
     * @param int $page Page number
     *
     * @return PaginationInterface Paginated list
     */
    public function getPaginatedList(int $page): PaginationInterface;

    /**
     * Get single user details.
     *
     * @param int $id User id
     *
     * @return User|null User
     */
    public function getUserDetails(int $id): ?User;

    /**
     * Count all admin users.
     */
    public function countAdmins(): int;

    /**
     * Find user stats.
     *
     * @param int $id User id
     *
     * @return array Stats
     */
    public function findWithStats(int $id): array;

    /**
     * Toggle ROLE_ADMIN for user.
     *
     * @param User $user User entity
     */
    public function toggleAdminRole(User $user): void;

    /**
     * Delete user safely (with admin rules).
     *
     * @param User $user User entity
     */
    public function delete(User $user): void;

    /**
     * Change password.
     *
     * @param User   $user          User entity
     * @param string $plainPassword New password
     */
    public function changePassword(User $user, string $plainPassword): void;

    /**
     * Change email.
     *
     * @param User   $user  User entity
     * @param string $email New email
     */
    public function changeEmail(User $user, string $email): void;

    /**
     * FInd admins.
     *
     * @return array Admins
     */
    public function findAdmins(): array;

    /**
     * Register new user.
     *
     * @param User   $user          User entity
     * @param string $plainPassword Password
     */
    public function register(User $user, string $plainPassword): void;
}
