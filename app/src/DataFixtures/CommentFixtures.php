<?php

/**
 * Comment fixtures.
 */

namespace App\DataFixtures;

use App\Entity\Bug;
use App\Entity\Comment;
use App\Entity\User;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;

/**
 * Class CommentFixtures.
 *
 * @psalm-suppress MissingConstructor
 */
class CommentFixtures extends AbstractBaseFixtures implements DependentFixtureInterface
{
    /**
     * Load data.
     *
     * @psalm-suppress PossiblyNullPropertyFetch
     * @psalm-suppress PossiblyNullReference
     * @psalm-suppress UnusedClosureParam
     */
    public function loadData(): void
    {
        if (!$this->manager instanceof ObjectManager || !$this->faker instanceof Generator) {
            return;
        }

        $this->createMany(80, 'comment', function (int $i) {
            $comment = new Comment();
            $comment->setContent($this->faker->realTextBetween(60, 80));
            $comment->setCreatedAt(
                \DateTimeImmutable::createFromMutable(
                    $this->faker->dateTimeBetween('-100 days', '-1 days')
                )
            );
            $comment->setUpdatedAt(
                \DateTimeImmutable::createFromMutable(
                    $this->faker->dateTimeBetween('-100 days', '-1 days')
                )
            );

            /** @var Bug $bug */
            $bug = $this->getRandomReference('bug', Bug::class);
            $comment->setBug($bug);

            /** @var User $author */
            $author = $this->getRandomReference('user', User::class);
            $comment->setAuthor($author);

            return $comment;
        });
    }

    /**
     * This method must return an array of fixtures classes
     * on which the implementing class depends on.
     *
     * @return string[] of dependencies
     *
     * @psalm-return array{0: CommentFixtures::class}
     */
    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}
