<?php

/**
 * Admin User Controller Test.
 */

namespace App\Tests\Controller;

use App\Entity\Enum\UserRole;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserServiceInterface;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class AdminUserControllerTest.
 */
class AdminUserControllerTest extends WebTestCase
{
    public const TEST_ROUTE = '/admin/users';

    private KernelBrowser $httpClient;

    /**
     * Set up test.
     */
    public function setUp(): void
    {
        $this->httpClient = static::createClient();
    }

    /**
     * Test view user list for anonymous user.
     */
    public function testIndexAnonymous(): void
    {
        // given
        $expectedStatusCode = 302;

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE);
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test view user list for user.
     */
    public function testIndexUserForbidden(): void
    {
        // given
        $user = $this->createUser([UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($user);

        $expectedStatusCode = 403;

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE);
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test view user list for admin.
     */
    public function testIndexAdmin(): void
    {
        // given
        $admin = $this->createUser([UserRole::ROLE_ADMIN->value]);
        $this->httpClient->loginUser($admin);

        $expectedStatusCode = 200;

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE);
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test view user details by anonymous user.
     */
    public function testViewAnonymous(): void
    {
        // given
        $user = $this->createUser([UserRole::ROLE_USER->value]);
        $expectedStatusCode = 302;

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$user->getId());
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test view user details by user.
     */
    public function testViewUserForbidden(): void
    {
        // given
        $user = $this->createUser([UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($user);

        $targetUser = $this->createUser([UserRole::ROLE_USER->value]);

        $expectedStatusCode = 403;

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$targetUser->getId());
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test view user details by admin.
     */
    public function testViewAdmin(): void
    {
        // given
        $admin = $this->createUser([UserRole::ROLE_ADMIN->value]);
        $this->httpClient->loginUser($admin);

        $targetUser = $this->createUser([UserRole::ROLE_USER->value]);

        $expectedStatusCode = 200;

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$targetUser->getId());
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test view user details by admin - exception for invalid user id.
     */
    public function testViewUserNotFound(): void
    {
        // given
        $admin = $this->createUser([UserRole::ROLE_ADMIN->value]);
        $this->httpClient->loginUser($admin);

        $userService = $this->createStub(UserServiceInterface::class);

        $userService
            ->method('findWithStats')
            ->willReturn([]);

        self::getContainer()->set(
            UserServiceInterface::class,
            $userService
        );

        $expectedStatusCode = 404;

        // when
        $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/999999'
        );

        $resultStatusCode = $this->httpClient
            ->getResponse()
            ->getStatusCode();

        // then
        $this->assertEquals(
            $expectedStatusCode,
            $resultStatusCode
        );
    }

    /**
     * Test toggle admin role by anonymous user.
     */
    public function testToggleRoleAnonymous(): void
    {
        $user = $this->createUser([UserRole::ROLE_USER->value]);

        $expectedStatusCode = 302;

        $this->httpClient->request(
            'POST',
            self::TEST_ROUTE.'/'.$user->getId().'/toggle-role'
        );

        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test toggle admin role by user.
     */
    public function testToggleRoleUserForbidden(): void
    {
        $user = $this->createUser([UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($user);

        $targetUser = $this->createUser([UserRole::ROLE_USER->value]);

        $expectedStatusCode = 403;

        $this->httpClient->request(
            'POST',
            self::TEST_ROUTE.'/'.$targetUser->getId().'/toggle-role'
        );

        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test assign admin role by admin.
     */
    public function testToggleRoleAdmin(): void
    {
        $admin = $this->createUser([UserRole::ROLE_ADMIN->value]);
        $this->httpClient->loginUser($admin);

        $targetUser = $this->createUser([UserRole::ROLE_USER->value]);

        $expectedStatusCode = 302;

        $this->httpClient->request(
            'POST',
            self::TEST_ROUTE.'/'.$targetUser->getId().'/toggle-role'
        );

        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test remove admin role by admin - last admin.
     */
    public function testToggleRoleLastAdminBlocked(): void
    {
        $admin = $this->createUser([UserRole::ROLE_ADMIN->value]);
        $this->httpClient->loginUser($admin);

        $expectedStatusCode = 302;

        $this->httpClient->request(
            'POST',
            self::TEST_ROUTE.'/'.$admin->getId().'/toggle-role'
        );

        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test delete user by anonymous user.
     */
    public function testDeleteAnonymous(): void
    {
        $user = $this->createUser([UserRole::ROLE_USER->value]);

        $expectedStatusCode = 302;

        $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/'.$user->getId().'/delete'
        );

        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test delete other user by user.
     */
    public function testDeleteUserForbidden(): void
    {
        $user = $this->createUser([UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($user);

        $targetUser = $this->createUser([UserRole::ROLE_USER->value]);

        $expectedStatusCode = 403;

        $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/'.$targetUser->getId().'/delete'
        );

        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test delete user by anonymous admin.
     */
    public function testDeleteAdmin(): void
    {
        $admin = $this->createUser([UserRole::ROLE_ADMIN->value]);
        $this->httpClient->loginUser($admin);

        $targetUser = $this->createUser([UserRole::ROLE_USER->value]);

        $expectedStatusCode = 200;

        $crawler = $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/'.$targetUser->getId().'/delete'
        );

        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        $form = $crawler->selectButton('submit')->form();

        $this->httpClient->submit($form);

        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test delete user by admin when service throws LogicException.
     */
    public function testDeleteUserLogicException(): void
    {
        // given
        $admin = $this->createUser([UserRole::ROLE_ADMIN->value]);
        $this->httpClient->loginUser($admin);

        $targetUser = $this->createUser([UserRole::ROLE_USER->value]);

        $userService = $this->createStub(UserServiceInterface::class);

        $userService
            ->method('delete')
            ->willThrowException(new \LogicException('Cannot delete user'));

        self::getContainer()->set(
            UserServiceInterface::class,
            $userService
        );

        $expectedStatusCode = 302;

        // when
        $crawler = $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/'.$targetUser->getId().'/delete'
        );

        $form = $crawler->selectButton('submit')->form();

        $this->httpClient->submit($form);

        $resultStatusCode = $this->httpClient
            ->getResponse()
            ->getStatusCode();

        // then
        $this->assertEquals(
            $expectedStatusCode,
            $resultStatusCode
        );
    }

    /**
     * Create user helper.
     *
     * @param array $roles User roles
     *
     * @return User User entity
     */
    private function createUser(array $roles): User
    {
        $container = static::getContainer();

        $passwordHasher = $container->get('security.password_hasher');
        $userRepository = $container->get(UserRepository::class);

        $user = new User();
        $user->setEmail('user'.uniqid().'@example.com');
        $user->setRoles($roles);

        $user->setPassword(
            $passwordHasher->hashPassword($user, 'password')
        );

        $userRepository->save($user);

        return $user;
    }
}
