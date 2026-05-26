<?php
/**
 * User service tests.
 */

namespace App\Tests\Service;

use App\Entity\Bug;
use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Enum\UserRole;
use App\Entity\User;
use App\Repository\BugRepository;
use App\Repository\CategoryRepository;
use App\Repository\CommentRepository;
use App\Repository\UserRepository;
use App\Service\UserService;
use App\Service\UserServiceInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class UserServiceTest.
 */
class UserServiceTest extends KernelTestCase
{
    /**
     * User repository.
     */
    private ?EntityManagerInterface $entityManager;

    /**
     * User service.
     */
    private ?UserServiceInterface $userService;

    /**
     * Create user helper.
     */
    private function createUser(): User
    {
        $container = static::getContainer();

        $passwordHasher = $container->get('security.password_hasher');

        $repo = $container->get(UserRepository::class);

        $user = new User();
        $user->setEmail('user'.uniqid().'@example.com');
        $user->setRoles([UserRole::ROLE_USER->value]);

        $user->setPassword($passwordHasher->hashPassword($user, 'password'));

        $repo->save($user);

        return $user;
    }

    /**
     * Create category helper.
     */
    private function createCategory(): Category
    {
        $repo = static::getContainer()
            ->get(CategoryRepository::class);

        $category = new Category();
        $category->setTitle('Category '.uniqid());

        $repo->save($category);

        return $category;
    }

    /**
     * Create bug helper.
     */
    private function createBug(): Bug
    {
        $repo = static::getContainer()
            ->get(BugRepository::class);

        $bug = new Bug();
        $bug->setTitle('Bug Title'.uniqid());
        $bug->setAuthor($this->createUser());
        $bug->setDescription('Bug Description');
        $bug->setCategory($this->createCategory());

        $repo->save($bug);

        return $bug;
    }

    /**
     * Create comment helper.
     */
    private function createComment(): Comment
    {
        $repo = static::getContainer()
            ->get(CommentRepository::class);

        $comment = new Comment();
        $comment->setAuthor($this->createUser());
        $comment->setContent('Comment Content');

        $repo->save($comment);

        return $comment;
    }

    /**
     * Set up test.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function setUp(): void
    {
        $container = static::getContainer();
        $this->entityManager = $container->get('doctrine.orm.entity_manager');
        $this->userService = $container->get(UserService::class);
    }

    /**
     * Test save.
     *
     * @throws ORMException
     */
    public function testSave(): void
    {
        // given
        $expectedUser = new User();
        $expectedUser->setemail('user'.uniqid().'@example.com');
        $expectedUser->setPassword('password');
        $expectedUser->setRoles([UserRole::ROLE_USER->value]);

        // when
        $this->userService->save($expectedUser);

        // then
        $expectedUserId = $expectedUser->getId();
        $resultUser = $this->entityManager->createQueryBuilder()
            ->select('user')
            ->from(User::class, 'user')
            ->where('user.id = :id')
            ->setParameter(':id', $expectedUserId, Types::INTEGER)
            ->getQuery()
            ->getSingleResult();

        $this->assertEquals($expectedUser, $resultUser);
    }

    /**
     * Test delete.
     *
     * @throws OptimisticLockException|ORMException
     */
    public function testDelete(): void
    {
        // given
        $userToDelete = new User();
        $userToDelete->setEmail('user'.uniqid().'@example.com');
        $userToDelete->setPassword('password');
        $userToDelete->setRoles([UserRole::ROLE_USER->value]);

        $this->entityManager->persist($userToDelete);
        $this->entityManager->flush();

        $deletedUserId = $userToDelete->getId();

        // when
        $this->userService->delete($userToDelete);

        // then
        $resultUser = $this->entityManager->createQueryBuilder()
            ->select('user')
            ->from(User::class, 'user')
            ->where('user.id = :id')
            ->setParameter(':id', $deletedUserId, Types::INTEGER)
            ->getQuery()
            ->getOneOrNullResult();

        $this->assertNull($resultUser);
    }

    /**
     * Test paginated list.
     */
    public function testGetPaginatedList(): void
    {
        // given
        $page = 1;
        $dataSetSize = 10;
        $expectedResultSize = 10;

        $counter = 0;
        while ($counter < $dataSetSize) {
            $user = new User();
            $user->setEmail('user'.uniqid().'@example.com');
            $user->setPassword('password');
            $user->setRoles([UserRole::ROLE_USER->value]);

            $this->userService->save($user);

            ++$counter;
        }

        // when
        $result = $this->userService->getPaginatedList($page);

        // then
        $this->assertEquals($expectedResultSize, $result->count());
    }

    public function testGetUserDetails(): void
    {
        // given
        $expectedUser = new User();
        $expectedUser->setEmail('user'.uniqid().'@example.com');
        $expectedUser->setPassword('password');
        $expectedUser->setRoles([UserRole::ROLE_USER->value]);

        $this->entityManager->persist($expectedUser);
        $this->entityManager->flush();

        $expectedUserId = $expectedUser->getId();

        // when
        $resultUser = $this->userService->getUserDetails($expectedUserId);

        // then
        $this->assertEquals($expectedUser, $resultUser);
    }

    public function testCountAdminsOne(): void
    {
        // given
        $user = new User();
        $user->setEmail('admin'.uniqid().'@example.com');
        $user->setPassword('password');
        $user->setRoles([UserRole::ROLE_ADMIN->value]);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $result = $this->userService->countAdmins();

        $this->assertSame(1, $result);
    }

    public function testCountAdminsZero(): void
    {
        $result = $this->userService->countAdmins();

        $this->assertSame(0, $result);
    }

    public function testFindWithStats(): void
    {
        // given
        $user = $this->createUser();

        $category = $this->createCategory();

        $bug = new Bug();
        $bug->setTitle('Test Bug');
        $bug->setDescription('Bug Description');
        $bug->setAuthor($user);
        $bug->setCategory($category);

        $comment = new Comment();
        $comment->setContent('Comment Content');
        $comment->setAuthor($user);
        $comment->setBug($bug);

        $this->entityManager->persist($bug);
        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        // when
        $result = $this->userService->findWithStats($user->getId());

        // then
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('bugCount', $result);
        $this->assertArrayHasKey('commentCount', $result);

        $this->assertSame(1, $result['bugCount']);
        $this->assertSame(1, $result['commentCount']);
        $this->assertSame($user->getId(), $result['user']->getId());
    }

    public function testFindWithStatsNotFound(): void
    {
        $result = $this->userService->findWithStats(999999);

        $this->assertSame([], $result);
    }

    public function testFindAdmins(): void
    {
        // given
        $admin = new User();
        $admin->setEmail('admin'.uniqid().'@example.com');
        $admin->setPassword('password');
        $admin->setRoles([UserRole::ROLE_ADMIN->value]);
        $this->entityManager->persist($admin);

        $user = new User();
        $user->setEmail('user'.uniqid().'@example.com');
        $user->setPassword('password');
        $user->setRoles([UserRole::ROLE_USER->value]);
        $this->entityManager->persist($user);

        $this->entityManager->flush();

        // when
        $result = $this->userService->findAdmins();

        // then
        $this->assertCount(1, $result);
        $this->assertSame($admin->getId(), $result[0]->getId());
    }

    public function testToggleAdminRoleAddAdmin(): void
    {
        // given
        $user = $this->createUser();
        $user->setRoles([UserRole::ROLE_USER->value]);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // when
        $this->userService->toggleAdminRole($user);

        // then
        $this->assertContains('ROLE_ADMIN', $user->getRoles());
    }

    public function testToggleAdminRoleRemoveAdminSuccess(): void
    {
        // given
        $admin1 = $this->createUser();
        $admin1->setRoles(['ROLE_ADMIN']);

        $admin2 = $this->createUser();
        $admin2->setRoles(['ROLE_ADMIN']);

        $this->entityManager->persist($admin1);
        $this->entityManager->persist($admin2);
        $this->entityManager->flush();

        // when
        $this->userService->toggleAdminRole($admin1);

        // then
        $this->assertNotContains('ROLE_ADMIN', $admin1->getRoles());
    }

    public function testToggleAdminRoleLastAdminThrowsException(): void
    {
        // given
        $admin = new User();
        $admin->setEmail('admin'.uniqid().'@example.com');
        $admin->setPassword('password');
        $admin->setRoles([UserRole::ROLE_ADMIN->value]);

        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        // then
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('You are the last admin.');

        // when
        $this->userService->toggleAdminRole($admin);
    }

    public function testChangePassword(): void
    {
        // given
        $user = $this->createUser();
        $oldPassword = $user->getPassword();

        $newPassword = 'new_password';

        // when
        $this->userService->changePassword($user, $newPassword);

        // then
        $this->assertNotSame($oldPassword, $user->getPassword());
        $this->assertNotEquals($newPassword, $user->getPassword());
    }

    public function testChangeEmail(): void
    {
        // given
        $user = $this->createUser();
        $newEmail = 'new'.uniqid().'@example.com';

        // when
        $this->userService->changeEmail($user, $newEmail);

        // then
        $this->assertSame($newEmail, $user->getEmail());
    }

    public function testRegister(): void
    {
        // given
        $user = new User();
        $user->setEmail('new'.uniqid().'@example.com');

        $plainPassword = 'password';

        // when
        $this->userService->register($user, $plainPassword);

        // then
        $this->assertContains('ROLE_USER', $user->getRoles());
        $this->assertNotSame($plainPassword, $user->getPassword());
    }
}
