<?php
/**
 * Bug service tests.
 */

namespace App\Tests\Service;

use App\Dto\BugListFiltersDto;
use App\Dto\BugListInputFiltersDto;
use App\Entity\Bug;
use App\Entity\Category;
use App\Entity\Enum\BugStatus;
use App\Entity\Enum\UserRole;
use App\Entity\Tag;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\TagRepository;
use App\Repository\UserRepository;
use App\Service\BugService;
use App\Service\BugServiceInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class BugServiceTest.
 */
class BugServiceTest extends KernelTestCase
{
    /**
     * Bug repository.
     */
    private ?EntityManagerInterface $entityManager;

    /**
     * Bug service.
     */
    private ?BugServiceInterface $bugService;

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
        $this->bugService = $container->get(BugService::class);
    }

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
     * Create tag helper.
     */
    private function createTag(): Tag
    {
        $repo = static::getContainer()
            ->get(TagRepository::class);

        $tag = new Tag();
        $tag->setTitle('Tag '.uniqid());

        $repo->save($tag);

        return $tag;
    }

    /**
     * Test save.
     *
     * @throws ORMException
     */
    public function testSave(): void
    {
        $expectedBug = new Bug();
        $expectedBug->setTitle('Test Bug');
        $expectedBug->setDescription('Test Bug Description');
        $expectedBug->setCategory($this->createCategory());
        $expectedBug->setAuthor($this->createUser());

        // when
        $this->bugService->save($expectedBug);

        // then
        $expectedBugId = $expectedBug->getId();
        $resultBug = $this->entityManager->createQueryBuilder()
            ->select('bug')
            ->from(Bug::class, 'bug')
            ->where('bug.id = :id')
            ->setParameter(':id', $expectedBugId, Types::INTEGER)
            ->getQuery()
            ->getSingleResult();

        $this->assertEquals($expectedBug, $resultBug);
    }

    /**
     * Test delete.
     *
     * @throws OptimisticLockException|ORMException
     */
    public function testDelete(): void
    {
        // given
        $bugToDelete = new Bug();
        $bugToDelete->setTitle('Test Bug');
        $bugToDelete->setDescription('Test Bug Description');
        $bugToDelete->setCategory($this->createCategory());
        $bugToDelete->setAuthor($this->createUser());

        $this->entityManager->persist($bugToDelete);
        $this->entityManager->flush();
        $deletedBugId = $bugToDelete->getId();

        // when
        $this->bugService->delete($bugToDelete);

        // then
        $resultBug = $this->entityManager->createQueryBuilder()
            ->select('bug')
            ->from(Bug::class, 'bug')
            ->where('bug.id = :id')
            ->setParameter(':id', $deletedBugId, Types::INTEGER)
            ->getQuery()
            ->getOneOrNullResult();

        $this->assertNull($resultBug);
    }

    public function testGetPaginatedListWithoutFilters(): void
    {
        // given
        $page = 1;

        for ($i = 0; $i < 10; $i++) {
            $bug = new Bug();
            $bug->setTitle('Bug '.$i);
            $bug->setDescription('desc');
            $bug->setAuthor($this->createUser());
            $bug->setCategory($this->createCategory());

            $this->bugService->save($bug);
        }

        $filters = new BugListInputFiltersDto();

        // when
        $result = $this->bugService->getPaginatedList($page, $filters);

        // then
        $this->assertCount(10, $result);
    }

    public function testGetPaginatedListWithCategoryFilter(): void
    {
        // given
        $category1 = $this->createCategory();
        $category2 = $this->createCategory();

        $bug1 = new Bug();
        $bug1->setTitle('Bug 1');
        $bug1->setDescription('desc');
        $bug1->setAuthor($this->createUser());
        $bug1->setCategory($category1);
        $this->bugService->save($bug1);

        $bug2 = new Bug();
        $bug2->setTitle('Bug 2');
        $bug2->setDescription('desc');
        $bug2->setAuthor($this->createUser());
        $bug2->setCategory($category2);
        $this->bugService->save($bug2);

        $filters = new BugListInputFiltersDto(
            $category1->getId(),
            null
        );

        // when
        $result = $this->bugService->getPaginatedList(1, $filters);

        // then
        $this->assertCount(1, $result);
        $this->assertSame('Bug 1', $result[0]->getTitle());
    }

    public function testGetPaginatedListWithTagFilter(): void
    {
        // given
        $tag1 = $this->createTag();
        $tag2 = $this->createTag();

        $bug1 = new Bug();
        $bug1->setTitle('Bug 1');
        $bug1->setDescription('desc');
        $bug1->setAuthor($this->createUser());
        $bug1->setCategory($this->createCategory());
        $bug1->addTag($tag1);
        $this->bugService->save($bug1);

        $bug2 = new Bug();
        $bug2->setTitle('Bug 2');
        $bug2->setDescription('desc');
        $bug2->setAuthor($this->createUser());
        $bug2->setCategory($this->createCategory());
        $bug2->addTag($tag2);
        $this->bugService->save($bug2);

        $filters = new BugListInputFiltersDto(
            null,
            $tag1->getId()
        );

        // when
        $result = $this->bugService->getPaginatedList(1, $filters);

        // then
        $this->assertCount(1, $result);
        $this->assertSame('Bug 1', $result[0]->getTitle());
    }

    public function testChangeStatusFromOpenToClosed(): void
    {
        //given
        $bug = new Bug();
        $bug->setTitle('Test Bug');
        $bug->setDescription('Test Bug Description');
        $bug->setAuthor($this->createUser());
        $bug->setCategory($this->createCategory());
        $bug->setStatusEnum(BugStatus::OPEN);

        $this->bugService->save($bug);

        // when
        $this->bugService->changeStatus($bug, BugStatus::CLOSED);

        // then
        $this->assertEquals(BugStatus::CLOSED, $bug->getStatusEnum());
    }

    public function testChangeStatusFromClosedToOpen(): void
    {
        // given
        $bug = new Bug();
        $bug->setTitle('Test Bug');
        $bug->setDescription('Test Bug Description');
        $bug->setAuthor($this->createUser());
        $bug->setCategory($this->createCategory());
        $bug->setStatusEnum(BugStatus::CLOSED);

        $this->bugService->save($bug);

        // when
        $this->bugService->changeStatus($bug, BugStatus::OPEN);

        // then
        $this->assertEquals(BugStatus::OPEN, $bug->getStatusEnum());
    }

    public function testChangeStatusFromClosedToArchived(): void
    {
        // given
        $bug = new Bug();
        $bug->setTitle('Test Bug');
        $bug->setDescription('TestBugDescription');
        $bug->setAuthor($this->createUser());
        $bug->setCategory($this->createCategory());
        $bug->setStatusEnum(BugStatus::CLOSED);

        $this->bugService->save($bug);

        // when
        $this->bugService->changeStatus($bug, BugStatus::ARCHIVED);

        // then
        $this->assertEquals(BugStatus::ARCHIVED, $bug->getStatusEnum());
    }

    public function testChangeStatusFromArchivedToOpen(): void
    {
        // given
        $bug = new Bug();
        $bug->setTitle('Test Bug');
        $bug->setDescription('Test Bug Description');
        $bug->setAuthor($this->createUser());
        $bug->setCategory($this->createCategory());
        $bug->setStatusEnum(BugStatus::ARCHIVED);

        $this->bugService->save($bug);

        // when
        $this->bugService->changeStatus($bug, BugStatus::OPEN);

        // then
        $this->assertEquals(BugStatus::OPEN, $bug->getStatusEnum());
    }

    public function testChangeStatusThrowsException(): void
    {
        // given
        $bug = new Bug();
        $bug->setTitle('Test Bug');
        $bug->setDescription('Test Bug Description');
        $bug->setAuthor($this->createUser());
        $bug->setCategory($this->createCategory());
        $bug->setStatusEnum(BugStatus::OPEN);

        $this->bugService->save($bug);

        // then
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Invalid status transition from OPEN to ARCHIVED'
        );

        // when
        $this->bugService->changeStatus($bug, BugStatus::ARCHIVED);
    }

    public function testChangeStatusSameStatusDoesNothing(): void
    {
        // given
        $bug = new Bug();
        $bug->setTitle('Test Bug');
        $bug->setDescription('Test Bug Description');
        $bug->setAuthor($this->createUser());
        $bug->setCategory($this->createCategory());
        $bug->setStatusEnum(BugStatus::OPEN);

        $this->bugService->save($bug);

        // when
        $this->bugService->changeStatus($bug, BugStatus::OPEN);

        // then
        $this->assertEquals(BugStatus::OPEN, $bug->getStatusEnum());
    }

    public function testAssign(): void
    {
        // given
        $userToAssign = $this->createUser();

        $bug = new Bug();
        $bug->setTitle('Test Bug');
        $bug->setDescription('Test Bug Description');
        $bug->setAuthor($this->createUser());
        $bug->setCategory($this->createCategory());
        $bug->setStatusEnum(BugStatus::ARCHIVED);
        $bug->setAssignedTo(null);

        $this->bugService->save($bug);

        // when
        $this->bugService->assign($bug, $userToAssign);

        // then
        $this->assertEquals($userToAssign, $bug->getAssignedTo());
    }

    public function testUnassign(): void
    {
        // given
        $user = $this->createUser();

        $bug = new Bug();
        $bug->setTitle('Test Bug');
        $bug->setDescription('Test Bug Description');
        $bug->setAuthor($this->createUser());
        $bug->setCategory($this->createCategory());
        $bug->setAssignedTo($user);

        $this->bugService->save($bug);

        // when
        $this->bugService->assign($bug, null);

        // then
        $this->assertNull($bug->getAssignedTo());
    }

    public function testChangeAassignTo(): void
    {
        // given
        $userAssigned = $this->createUser();
        $userToAssign = $this->createUser();

        $bug = new Bug();
        $bug->setTitle('Test Bug');
        $bug->setDescription('Description');
        $bug->setAuthor($this->createUser());
        $bug->setCategory($this->createCategory());
        $bug->setAssignedTo($userAssigned);

        $this->bugService->save($bug);

        // when
        $this->bugService->assign($bug, $userToAssign);

        // then
        $this->assertEquals($userToAssign, $bug->getAssignedTo());
    }
}
