<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

class UserService implements UserServiceInterface
{
    private const PER_PAGE = 10;

    public function __construct(private readonly UserRepository $userRepository, private readonly PaginatorInterface $paginator)
    {
    }

    public function getPaginatedList(int $page): PaginationInterface
    {
        return $this->paginator->paginate(
            $this->userRepository->queryAll(),
            $page,
            self::PER_PAGE
        );
    }

    public function getUserDetails(int $id): ?User
    {
        return $this->userRepository->find($id);
    }

    public function countAdmins(): int
    {
        return $this->userRepository->countAdmins();
    }

    public function findWithStats(int $id): array
    {
        return $this->userRepository->findWithStats($id);
    }

    public function toggleAdminRole(User $user): void
    {
        $roles = $user->getRoles();

        if (in_array('ROLE_ADMIN', $roles, true)) {
            if ($this->countAdmins() <= 1) {
                throw new \LogicException('You are the last admin.');
            }

            $roles = array_filter($roles, fn ($r) => 'ROLE_ADMIN' !== $r);
        } else {
            $roles[] = 'ROLE_ADMIN';
        }

        $user->setRoles(array_values(array_unique($roles)));
        $this->userRepository->save($user);
    }

    public function delete(User $user): void
    {
        if (in_array('ROLE_ADMIN', $user->getRoles(), true) && $this->countAdmins() <= 1) {
            throw new \LogicException('You are the last admin.');
        }

        $this->userRepository->delete($user);
    }
}
