<?php

/**
 * Comment controller tests.
 */

namespace App\Tests\Controller;

use App\Entity\Bug;
use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Enum\UserRole;
use App\Entity\User;
use App\Repository\BugRepository;
use App\Repository\CategoryRepository;
use App\Repository\CommentRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class CommentControllerTest.
 */
class CommentControllerTest extends WebTestCase
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
     * Test edit comment by anonymous user.
     */
    public function testEditCommentAnonymous(): void
    {
        // given
        $author = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $bug = $this->createBug($author);

        $comment = $this->createComment($author, $bug);

        $expectedStatusCode = 302;

        // when
        $this->httpClient->request(
            'GET',
            '/bug/'.$bug->getId().'/comment/'.$comment->getId().'/edit'
        );

        $resultStatusCode = $this->httpClient
            ->getResponse()
            ->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);

        $this->assertSelectorExists('html');
    }

    /**
     * Test edit comment by user - not author.
     */
    public function testEditCommentForbiddenForUser(): void
    {
        // given
        $author = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $otherUser = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $bug = $this->createBug($author);

        $comment = $this->createComment($author, $bug);

        $this->httpClient->loginUser($otherUser);

        $expectedStatusCode = 403;

        // when
        $this->httpClient->request(
            'GET',
            '/bug/'.$bug->getId().'/comment/'.$comment->getId().'/edit'
        );

        $resultStatusCode = $this->httpClient
            ->getResponse()
            ->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);

        $this->assertSelectorExists('html');
    }

    /**
     * Test edit comment by user - author.
     */
    public function testEditOwnCommentUser(): void
    {
        // given
        $author = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $bug = $this->createBug($author);

        $comment = $this->createComment($author, $bug);

        $this->httpClient->loginUser($author);

        $expectedStatusCode = 200;

        // when
        $crawler = $this->httpClient->request(
            'GET',
            '/bug/'.$bug->getId().'/comment/'.$comment->getId().'/edit'
        );

        $resultStatusCode = $this->httpClient
            ->getResponse()
            ->getStatusCode();

        $form = $crawler->selectButton('submit')->form([
            'comment[content]' => 'Updated '.uniqid(),
        ]);

        $this->httpClient->submit($form);

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test edit comment by admin.
     */
    public function testEditCommentAdmin(): void
    {
        // given
        $author = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $admin = $this->createUser([
            UserRole::ROLE_ADMIN->value,
        ]);

        $bug = $this->createBug($author);

        $comment = $this->createComment($author, $bug);

        $this->httpClient->loginUser($admin);

        $expectedStatusCode = 200;

        // when
        $crawler = $this->httpClient->request(
            'GET',
            '/bug/'.$bug->getId().'/comment/'.$comment->getId().'/edit'
        );

        $resultStatusCode = $this->httpClient
            ->getResponse()
            ->getStatusCode();

        $form = $crawler->selectButton('submit')->form([
            'comment[content]' => 'Updated '.uniqid(),
        ]);

        $this->httpClient->submit($form);

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test edit comment with invalid bug.
     */
    public function testEditCommentWithInvalidBug(): void
    {
        // given
        $author = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $this->httpClient->loginUser($author);

        $bug1 = $this->createBug($author);
        $bug2 = $this->createBug($author);

        $comment = $this->createComment($author, $bug1);

        $expectedStatusCode = 404;

        // when
        $this->httpClient->request(
            'GET',
            '/bug/'.$bug2->getId().'/comment/'.$comment->getId().'/edit'
        );

        $resultStatusCode = $this->httpClient
            ->getResponse()
            ->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /*
     * DELETE
     */

    /**
     * Test delete comment by anonymous user.
     */
    public function testDeleteCommentAnonymous(): void
    {
        // given
        $author = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $bug = $this->createBug($author);

        $comment = $this->createComment($author, $bug);

        $expectedStatusCode = 302;

        // when
        $this->httpClient->request(
            'GET',
            '/bug/'.$bug->getId().'/comment/'.$comment->getId().'/delete'
        );

        $resultStatusCode = $this->httpClient
            ->getResponse()
            ->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);

        $this->assertSelectorExists('html');
    }

    /**
     * Test delete comment by user - not author.
     */
    public function testDeleteCommentForbiddenForUser(): void
    {
        // given
        $author = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $otherUser = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $bug = $this->createBug($author);

        $comment = $this->createComment($author, $bug);

        $this->httpClient->loginUser($otherUser);

        $expectedStatusCode = 403;

        // when
        $this->httpClient->request(
            'GET',
            '/bug/'.$bug->getId().'/comment/'.$comment->getId().'/delete'
        );

        $resultStatusCode = $this->httpClient
            ->getResponse()
            ->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);

        $this->assertSelectorExists('html');
    }

    /**
     * Test delete comment by user - author.
     */
    public function testDeleteOwnCommentUser(): void
    {
        // given
        $author = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $bug = $this->createBug($author);

        $comment = $this->createComment($author, $bug);

        $this->httpClient->loginUser($author);

        $expectedStatusCode = 200;

        // when
        $crawler = $this->httpClient->request(
            'GET',
            '/bug/'.$bug->getId().'/comment/'.$comment->getId().'/delete'
        );

        $resultStatusCode = $this->httpClient
            ->getResponse()
            ->getStatusCode();

        $form = $crawler->selectButton('submit')->form();

        $this->httpClient->submit($form);

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test delete comment by admin.
     */
    public function testDeleteCommentAdmin(): void
    {
        // given
        $author = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $admin = $this->createUser([
            UserRole::ROLE_ADMIN->value,
        ]);

        $bug = $this->createBug($author);

        $comment = $this->createComment($author, $bug);

        $this->httpClient->loginUser($admin);

        $expectedStatusCode = 200;

        // when
        $crawler = $this->httpClient->request(
            'GET',
            '/bug/'.$bug->getId().'/comment/'.$comment->getId().'/delete'
        );

        $resultStatusCode = $this->httpClient
            ->getResponse()
            ->getStatusCode();

        $form = $crawler->selectButton('submit')->form();

        $this->httpClient->submit($form);

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test delete comment with invalid bug.
     */
    public function testDeleteCommentWithInvalidBug(): void
    {
        // given
        $author = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $this->httpClient->loginUser($author);

        $bug1 = $this->createBug($author);
        $bug2 = $this->createBug($author);

        $comment = $this->createComment($author, $bug1);

        $expectedStatusCode = 404;

        // when
        $this->httpClient->request(
            'GET',
            '/bug/'.$bug2->getId().'/comment/'.$comment->getId().'/delete'
        );

        $resultStatusCode = $this->httpClient
            ->getResponse()
            ->getStatusCode();

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
            $passwordHasher->hashPassword(
                $user,
                'password'
            )
        );

        $userRepository->save($user);

        return $user;
    }

    /**
     * Create bug helper.
     *
     * @param User $author User
     *
     * @return Bug Bug entity
     */
    private function createBug(User $author): Bug
    {
        $container = static::getContainer();

        $bugRepository = $container->get(BugRepository::class);
        $categoryRepository = $container->get(CategoryRepository::class);

        $category = new Category();
        $category->setTitle('Category '.uniqid());

        $categoryRepository->save($category);

        $bug = new Bug();
        $bug->setTitle('Bug '.uniqid());
        $bug->setDescription('Description');
        $bug->setAuthor($author);
        $bug->setCategory($category);

        $bugRepository->save($bug);

        return $bug;
    }

    /**
     * Create comment helper.
     *
     * @param User $author User
     * @param Bug  $bug    Bug
     *
     * @return Comment Comment entity
     */
    private function createComment(User $author, Bug $bug): Comment
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
}
