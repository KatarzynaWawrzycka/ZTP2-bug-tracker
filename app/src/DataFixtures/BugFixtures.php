<?php

/**
 * Bug fixtures.
 */

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Bug;
use App\Entity\Tag;
use App\Entity\User;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;
use App\Entity\Enum\BugStatus;

/**
 * Class BugFixtures.
 *
 * @psalm-suppress MissingConstructor
 */
class BugFixtures extends AbstractBaseFixtures implements DependentFixtureInterface
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

        $this->createMany(100, 'bug', function (int $i) {
            $bug = new Bug();
            $bug->setTitle($this->faker->realTextBetween(20, 35));
            $bug->setDescription($this->faker->realText);
            $bug->setCreatedAt(
                \DateTimeImmutable::createFromMutable(
                    $this->faker->dateTimeBetween('-100 days', '-1 days')
                )
            );
            $bug->setUpdatedAt(
                \DateTimeImmutable::createFromMutable(
                    $this->faker->dateTimeBetween('-100 days', '-1 days')
                )
            );
            /** @var Category $category */
            $category = $this->getRandomReference('category', Category::class);
            $bug->setCategory($category);

            /** @var Tag[] $tags */
            $tags = $this->getRandomReferenceList(
                'tag',
                Tag::class,
                $this->faker->numberBetween(0, 3)
            );
            foreach ($tags as $tag) {
                $bug->addTag($tag);
            }

            /** @var User $author */
            $author = $this->getRandomReference('user', User::class);
            $bug->setAuthor($author);

            $bug->setStatus(
                $this->faker->randomElement([
                    BugStatus::OPEN->value,
                    BugStatus::CLOSED->value,
                    BugStatus::ARCHIVED->value,
                ])
            );

            return $bug;
        });
    }

    /**
     * This method must return an array of fixtures classes
     * on which the implementing class depends on.
     *
     * @return string[] of dependencies
     *
     * @psalm-return array{0: CategoryFixtures::class}
     */
    public function getDependencies(): array
    {
        return [CategoryFixtures::class, TagFixtures::class, UserFixtures::class];
    }
}
