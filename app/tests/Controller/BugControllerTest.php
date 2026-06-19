<?php
/**
 * Bug controller tests.
 */

namespace App\Tests\Controller;

use App\Entity\Bug;
use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Enum\BugStatus;
use App\Entity\Enum\UserRole;
use App\Entity\User;
use App\Repository\BugRepository;
use App\Repository\CategoryRepository;
use App\Repository\CommentRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BugControllerTest extends WebTestCase
{
    public const TEST_ROUTE = '/bug';

    private KernelBrowser $httpClient;

    /**
     * Set up test.
     */
    public function setUp(): void
    {
        $this->httpClient = static::createClient();
    }


    /*
     * HELPERS
     */


    /**
     * Create user helper.
     */
    private function createUser(array $roles): User
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $passwordHasher = static::getContainer()->get('security.password_hasher');

        $user = new User();
        $user->setEmail('user'.uniqid().'@example.com');
        $user->setRoles($roles);
        $user->setPassword(
            $passwordHasher->hashPassword($user, 'password')
        );

        $em->persist($user);
        $em->flush();

        return $user;
    }


    /**
     * Create category helper.
     */
    private function createCategory(): Category
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $category = new Category();
        $category->setTitle('Category ' . uniqid());

        $em->persist($category);
        $em->flush();

        return $category;
    }


    /**
     * Create bug helper.
     */
    private function createBug(User $author): Bug
    {
        $repository = static::getContainer()
            ->get(BugRepository::class);

        $bug = new Bug();

        $bug->setTitle('Bug '.uniqid());
        $bug->setDescription('Description');
        $bug->setAuthor($author);
        $bug->setCategory($this->createCategory());
        $bug->setStatusEnum(BugStatus::OPEN);

        $repository->save($bug);

        return $bug;
    }


    /**
     * Create comment helper.
     */
    private function createComment(Bug $bug, User $author): Comment
    {
        $repository = static::getContainer()
            ->get(CommentRepository::class);

        $comment = new Comment();

        $comment->setContent('Comment '.uniqid());
        $comment->setAuthor($author);
        $comment->setBug($bug);

        $repository->save($comment);

        return $comment;
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

        $resultStatusCode = $this->httpClient
            ->getResponse()
            ->getStatusCode();

        // then
        $this->assertEquals(
            $expectedStatusCode,
            $resultStatusCode
        );
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

        $resultStatusCode = $this->httpClient
            ->getResponse()
            ->getStatusCode();

        // then
        $this->assertEquals(
            $expectedStatusCode,
            $resultStatusCode
        );
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

        $resultStatusCode = $this->httpClient
            ->getResponse()
            ->getStatusCode();

        // then
        $this->assertEquals(
            $expectedStatusCode,
            $resultStatusCode
        );
    }


    /*
     * VIEW
     */
    public function testViewBugAnonymous(): void
    {
        // given
        $expectedStatusCode = 200;

        $bug = $this->createBug(
            $this->createUser([
                UserRole::ROLE_USER->value,
            ])
        );

        // when
        $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/'.$bug->getId()
        );

        $resultStatusCode = $this->httpClient
            ->getResponse()
            ->getStatusCode();

        // then
        $this->assertEquals(
            $expectedStatusCode,
            $resultStatusCode
        );

        $this->assertSelectorExists('html');
    }


    public function testViewBugUser(): void
    {
        // given
        $user = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $this->httpClient->loginUser($user);

        $bug = $this->createBug($user);

        $expectedStatusCode = 200;


        // when
        $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/'.$bug->getId()
        );

        $resultStatusCode = $this->httpClient
            ->getResponse()
            ->getStatusCode();


        // then
        $this->assertEquals(
            $expectedStatusCode,
            $resultStatusCode
        );

        $this->assertSelectorExists('html');
    }

    public function testViewBugAddCommentSuccess(): void
    {
        $user = $this->createUser([UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($user);

        $bug = $this->createBug($user);

        $crawler = $this->httpClient->request(
            'GET',
            self::TEST_ROUTE . '/' . $bug->getId()
        );

        $form = $crawler->filter('form')->form([
            'comment[content]' => 'Test comment ' . uniqid(),
        ]);

        $this->httpClient->submit($form);

        $this->assertEquals(
            302,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    public function testViewBugAdmin(): void
    {
        // given
        $admin = $this->createUser([
            UserRole::ROLE_ADMIN->value,
        ]);

        $this->httpClient->loginUser($admin);

        $bug = $this->createBug(
            $this->createUser([
                UserRole::ROLE_USER->value,
            ])
        );

        $expectedStatusCode = 200;

        // when
        $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/'.$bug->getId()
        );

        $resultStatusCode = $this->httpClient
            ->getResponse()
            ->getStatusCode();


        // then
        $this->assertEquals(
            $expectedStatusCode,
            $resultStatusCode
        );

        $this->assertSelectorExists('html');
    }

    /*
     * CREATE
     */
    public function testCreateBugAnonymous(): void
    {
        // given
        $expectedStatusCode = 302;

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE . '/create');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    public function testCreateBugUser(): void
    {
        // given
        $user = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $this->httpClient->loginUser($user);

        $category = $this->createCategory();

        // when
        $crawler = $this->httpClient->request('GET', self::TEST_ROUTE . '/create');

        $form = $crawler->selectButton('submit')->form([
            'bug[title]' => 'Test bug ' . uniqid(),
            'bug[description]' => 'Test bug description',
            'bug[category]' => (string) $category->getId(),
        ]);

        $this->httpClient->submit($form);

        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then (po create redirect)
        $this->assertEquals(302, $resultStatusCode);
    }

    public function testCreateBugAdmin(): void
    {
        // given
        $admin = $this->createUser([
            UserRole::ROLE_ADMIN->value,
        ]);

        $this->httpClient->loginUser($admin);

        $category = $this->createCategory();

        // when
        $crawler = $this->httpClient->request('GET', self::TEST_ROUTE . '/create');

        $form = $crawler->selectButton('submit')->form([
            'bug[title]' => 'Test bug ' . uniqid(),
            'bug[description]' => 'Test bug description',
            'bug[category]' => (string) $category->getId(),
        ]);

        $this->httpClient->submit($form);

        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals(302, $resultStatusCode);
    }

    /*
     * EDIT
     */
    public function testEditBugAnonymous(): void
    {
        $bug = $this->createBug(
            $this->createUser([UserRole::ROLE_USER->value])
        );

        $this->httpClient->request(
            'GET',
            self::TEST_ROUTE . '/' . $bug->getId() . '/edit'
        );

        $this->assertEquals(
            302,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    public function testEditBugUserAuthor(): void
    {
        $user = $this->createUser([UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($user);

        $bug = $this->createBug($user);

        $crawler = $this->httpClient->request(
            'GET',
            self::TEST_ROUTE . '/' . $bug->getId() . '/edit'
        );

        $form = $crawler->selectButton('submit')->form([
            'bug[title]' => 'Updated title',
            'bug[description]' => 'Updated description',
            'bug[category]' => (string) $bug->getCategory()->getId(),
        ]);

        $this->httpClient->submit($form);

        $this->assertEquals(
            302,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    public function testEditBugUserForbidden(): void
    {
        $user = $this->createUser([UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($user);

        $otherUser = $this->createUser([UserRole::ROLE_USER->value]);

        $bug = $this->createBug($otherUser);

        $this->httpClient->request(
            'GET',
            self::TEST_ROUTE . '/' . $bug->getId() . '/edit'
        );

        $this->assertEquals(
            403,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    public function testEditBugAdmin(): void
    {
        $admin = $this->createUser([UserRole::ROLE_ADMIN->value]);
        $this->httpClient->loginUser($admin);

        $otherUser = $this->createUser([UserRole::ROLE_USER->value]);
        $bug = $this->createBug($otherUser);

        $crawler = $this->httpClient->request(
            'GET',
            self::TEST_ROUTE . '/' . $bug->getId() . '/edit'
        );

        $form = $crawler->selectButton('submit')->form([
            'bug[title]' => 'Admin update',
            'bug[description]' => 'Admin update',
            'bug[category]' => (string) $bug->getCategory()->getId(),
        ]);

        $this->httpClient->submit($form);

        $this->assertEquals(
            302,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /*
     * DELETE
     */
    public function testDeleteBugAnonymous(): void
    {
        $bug = $this->createBug(
            $this->createUser([UserRole::ROLE_USER->value])
        );

        $this->httpClient->request(
            'GET',
            self::TEST_ROUTE . '/' . $bug->getId() . '/delete'
        );

        $this->assertEquals(
            302,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    public function testDeleteBugUserAuthor(): void
    {
        $user = $this->createUser([UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($user);

        $bug = $this->createBug($user);

        $crawler = $this->httpClient->request(
            'GET',
            self::TEST_ROUTE . '/' . $bug->getId() . '/delete'
        );

        $form = $crawler->selectButton('submit')->form();

        $this->httpClient->submit($form);

        $this->assertEquals(
            302,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    public function testDeleteBugUserForbidden(): void
    {
        $user = $this->createUser([UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($user);

        $otherUser = $this->createUser([UserRole::ROLE_USER->value]);
        $bug = $this->createBug($otherUser);

        $this->httpClient->request(
            'GET',
            self::TEST_ROUTE . '/' . $bug->getId() . '/delete'
        );

        $this->assertEquals(
            403,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    public function testDeleteBugAdmin(): void
    {
        $admin = $this->createUser([UserRole::ROLE_ADMIN->value]);
        $this->httpClient->loginUser($admin);

        $user = $this->createUser([UserRole::ROLE_USER->value]);
        $bug = $this->createBug($user);

        $crawler = $this->httpClient->request(
            'GET',
            self::TEST_ROUTE . '/' . $bug->getId() . '/delete'
        );

        $form = $crawler->selectButton('submit')->form();

        $this->httpClient->submit($form);

        $this->assertEquals(
            302,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /*
     * CHANGE STATUS
     */
    public function testChangeStatusAnonymous(): void
    {
        $bug = $this->createBug(
            $this->createUser([UserRole::ROLE_USER->value])
        );

        $this->httpClient->request(
            'POST',
            self::TEST_ROUTE . '/' . $bug->getId() . '/status/' . BugStatus::OPEN->value
        );

        $this->assertEquals(
            302,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    public function testChangeStatusUserForbidden(): void
    {
        $user = $this->createUser([UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($user);

        $bug = $this->createBug($user);

        $this->httpClient->request(
            'POST',
            self::TEST_ROUTE . '/' . $bug->getId() . '/status/' . BugStatus::OPEN->value
        );

        $this->assertEquals(
            403,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    public function testChangeStatusAdmin(): void
    {
        $admin = $this->createUser([UserRole::ROLE_ADMIN->value]);
        $this->httpClient->loginUser($admin);

        $bug = $this->createBug($admin);

        $this->httpClient->request(
            'POST',
            self::TEST_ROUTE . '/' . $bug->getId() . '/status/' . BugStatus::CLOSED->value
        );

        $this->assertEquals(
            302,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /*
     * ASSIGN
     */
    public function testAssignAnonymous(): void
    {
        $bug = $this->createBug(
            $this->createUser([UserRole::ROLE_USER->value])
        );

        $this->httpClient->request(
            'GET',
            self::TEST_ROUTE . '/' . $bug->getId() . '/assign'
        );

        $this->assertEquals(
            302,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    public function testAssignUserForbidden(): void
    {
        $user = $this->createUser([UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($user);

        $bug = $this->createBug($user);

        $this->httpClient->request(
            'GET',
            self::TEST_ROUTE . '/' . $bug->getId() . '/assign'
        );

        $this->assertEquals(
            403,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    public function testAssignAdminSuccess(): void
    {
        $admin = $this->createUser([UserRole::ROLE_ADMIN->value]);
        $this->httpClient->loginUser($admin);

        $bug = $this->createBug($admin);

        $bug->setStatusEnum(BugStatus::OPEN);

        $crawler = $this->httpClient->request(
            'GET',
            self::TEST_ROUTE . '/' . $bug->getId() . '/assign'
        );

        $form = $crawler->selectButton('submit')->form([
            'bug_assign[assignedTo]' => $admin->getId(),
        ]);

        $this->httpClient->submit($form);

        $this->assertEquals(
            302,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    public function testAssignAdminNotOpenBug(): void
    {
        $admin = $this->createUser([UserRole::ROLE_ADMIN->value]);
        $this->httpClient->loginUser($admin);

        $bug = $this->createBug($admin);

        $bug->setStatusEnum(BugStatus::CLOSED);

        $this->httpClient->request(
            'GET',
            self::TEST_ROUTE . '/' . $bug->getId() . '/assign'
        );

        $this->assertEquals(
            302,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }
}
