<?php

/**
 * Security Controller Test.
 */

namespace App\Tests\Controller;

use App\Entity\Enum\UserRole;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class SecurityControllerTest.
 */
class SecurityControllerTest extends WebTestCase
{
    private KernelBrowser $httpClient;

    /**
     * Set up test.
     */
    public function setUp(): void
    {
        $this->httpClient = static::createClient();
    }

    /**
     * Test registration page.
     */
    public function testRegisterPage(): void
    {
        // given
        $expectedStatusCode = 200;

        // when
        $this->httpClient->request('GET', '/register');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test registration process.
     */
    public function testRegisterUser(): void
    {
        // given
        $expectedStatusCode = 302;

        // when
        $crawler = $this->httpClient->request('GET', '/register');

        $form = $crawler->selectButton('submit')->form([
            'registration[email]' => 'test'.uniqid().'@example.com',
            'registration[plainPassword][first]' => 'password123',
            'registration[plainPassword][second]' => 'password123',
        ]);

        $this->httpClient->submit($form);

        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test register process - password mismatch/.
     */
    public function testRegisterPasswordMismatch(): void
    {
        // given
        $expectedStatusCode = 200;

        // when
        $crawler = $this->httpClient->request('GET', '/register');

        $form = $crawler->selectButton('submit')->form([
            'registration[email]' => 'test'.uniqid().'@example.com',
            'registration[plainPassword][first]' => 'password123',
            'registration[plainPassword][second]' => 'different',
        ]);

        $this->httpClient->submit($form);

        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test login page.
     */
    public function testLoginPage(): void
    {
        // given
        $expectedStatusCode = 200;

        // when
        $this->httpClient->request('GET', '/login');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test redirection after logging in.
     */
    public function testLoginRedirectIfLogged(): void
    {
        // given
        $user = $this->createUser([UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($user);

        $expectedStatusCode = 302;

        // when
        $this->httpClient->request('GET', '/login');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test logout.
     */
    public function testLogout(): void
    {
        // given
        $user = $this->createUser([UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($user);

        $expectedStatusCode = 302;

        // when
        $this->httpClient->request('GET', '/logout');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
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
