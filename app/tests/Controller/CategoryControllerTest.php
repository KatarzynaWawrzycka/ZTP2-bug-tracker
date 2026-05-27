<?php

namespace App\Tests\Controller;

use App\Entity\Bug;
use App\Entity\Category;
use App\Entity\Enum\UserRole;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CategoryControllerTest extends WebTestCase
{
    public const TEST_ROUTE = '/category';

    private KernelBrowser $httpClient;

    public function setUp(): void
    {
        $this->httpClient = static::createClient();
    }

    /*
     * HELPERS
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
            $passwordHasher->hashPassword(
                $user,
                'password'
            )
        );

        $userRepository->save($user);

        return $user;
    }

    private function createCategory(): Category
    {
        $container = static::getContainer();

        $repository = $container->get(CategoryRepository::class);

        $category = new Category();

        $category->setTitle('Category '.uniqid());

        $repository->save($category);

        return $category;
    }

    /*
     * INDEX
     */
    public function testIndexAnonymous(): void
    {
        // given
        $expectedStatusCode = 200;

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE);
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    public function testIndexUser(): void
    {
        // given
        $user = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $this->httpClient->loginUser($user);

        $expectedStatusCode = 200;

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE);
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    public function testIndexAdmin(): void
    {
        // given
        $admin = $this->createUser([
            UserRole::ROLE_ADMIN->value,
        ]);

        $this->httpClient->loginUser($admin);

        $expectedStatusCode = 200;

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE);
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /*
     * VIEW
     */
    public function testViewCategoryAnonymous(): void
    {
        // given
        $expectedStatusCode = 302;
        $category = $this->createCategory();

        // when
        $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/'.$category->getId()
        );
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);

        $this->assertSelectorExists('html');
    }

    public function testViewCategoryUserForbidden(): void
    {
        $user = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $this->httpClient->loginUser($user);

        $expectedStatusCode = 403;
        $category = $this->createCategory();

        // when
        $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/'.$category->getId()
        );
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);

        $this->assertSelectorExists('html');
    }

    public function testViewCategoryAdmin(): void
    {
        $admin = $this->createUser([
            UserRole::ROLE_ADMIN->value,
        ]);

        $this->httpClient->loginUser($admin);

        $category = $this->createCategory();

        $expectedStatusCode = 200;

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$category->getId());
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);

        $this->assertSelectorExists('html');
    }

    /*
     * CREATE
     */
    public function testCreateCategoryAnonymous(): void
    {
        // given
        $expectedStatusCode = 302;

        // when
        $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/create'
        );
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);

        $this->assertSelectorExists('html');
    }

    public function testCreateCategoryUserForbidden(): void
    {
        // given
        $user = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $this->httpClient->loginUser($user);

        $expectedStatusCode = 403;

        // when
        $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/create'
        );

        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);

        $this->assertSelectorExists('html');
    }

    public function testCreateCategoryAdmin(): void
    {
        // given
        $admin = $this->createUser([
            UserRole::ROLE_ADMIN->value,
        ]);

        $this->httpClient->loginUser($admin);

        $expectedStatusCode = 200;

        // when
        $crawler = $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/create'
        );
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        $form = $crawler->selectButton('submit')->form([
            'category[title]' => 'New Category '.uniqid(),
        ]);

        $this->httpClient->submit($form);

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /*
     * EDIT
     */

    public function testEditCategoryAnonymous(): void
    {
        // given
        $expectedStatusCode = 302;
        $category = $this->createCategory();

        // when
        $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/'.$category->getId().'/edit'
        );
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);

        $this->assertSelectorExists('html');
    }

    public function testEditCategoryForbiddenForUser(): void
    {
        // given
        $user = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $this->httpClient->loginUser($user);

        $category = $this->createCategory();

        $expectedStatusCode = 403;

        // when
        $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/'.$category->getId().'/edit'
        );

        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);

        $this->assertSelectorExists('html');
    }

    public function testEditCategoryAdmin(): void
    {
        // given
        $admin = $this->createUser([
            UserRole::ROLE_ADMIN->value,
        ]);

        $this->httpClient->loginUser($admin);

        $category = $this->createCategory();

        $expectedStatusCode = 200;

        $crawler = $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/'.$category->getId().'/edit'
        );

        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        $form = $crawler->selectButton('submit')->form([
            'category[title]' => 'Updated '.uniqid(),
        ]);

        $this->httpClient->submit($form);

        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /*
     * DELETE
     */

    public function testDeleteCategoryAnonymous(): void
    {
        // given
        $expectedStatusCode = 302;
        $category = $this->createCategory();

        // when
        $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/'.$category->getId().'/delete'
        );
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);

        $this->assertSelectorExists('html');
    }

    public function testDeleteCategoryForbiddenForUser(): void
    {
        // given
        $user = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $this->httpClient->loginUser($user);

        $category = $this->createCategory();

        $expectedStatusCode = 403;

        // when
        $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/'.$category->getId().'/delete'
        );

        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);

        $this->assertSelectorExists('html');
    }

    public function testDeleteCategoryAdmin(): void
    {
        // given
        $admin = $this->createUser([
            UserRole::ROLE_ADMIN->value,
        ]);

        $this->httpClient->loginUser($admin);

        $category = $this->createCategory();

        $expectedStatusCode = 200;

        $crawler = $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/'.$category->getId().'/delete'
        );

        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        $form = $crawler->selectButton('submit')->form();

        $this->httpClient->submit($form);

        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    public function testDeleteCategoryWithBugsAdmin(): void
    {
        // given
        $admin = $this->createUser([
            UserRole::ROLE_ADMIN->value,
        ]);

        $this->httpClient->loginUser($admin);

        $category = $this->createCategory();

        $bug = new Bug();
        $bug->setTitle('Bug');
        $bug->setDescription('Description');
        $bug->setAuthor($admin);
        $bug->setCategory($category);

        $entityManager = static::getContainer()
            ->get('doctrine.orm.entity_manager');

        $entityManager->persist($bug);
        $entityManager->flush();

        // when
        $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/'.$category->getId().'/delete'
        );

        // then
        $this->assertResponseRedirects(self::TEST_ROUTE);
    }
}
