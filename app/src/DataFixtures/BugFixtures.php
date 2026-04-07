<?php
/**
 * Bug fixtures.
 */

namespace App\DataFixtures;

use App\Entity\Bug;

/**
 * Class BugFixtures.
 */
class BugFixtures extends AbstractBaseFixtures
{
    /**
     * Load data.
     */
    public function loadData(): void
    {
        for ($i = 0; $i < 10; ++$i) {
            $bug = new Bug();
            $bug->setTitle($this->faker->sentence);
            $bug->setCreatedAt(
                \DateTimeImmutable::createFromMutable($this->faker->dateTimeBetween('-100 days', '-1 days'))
            );
            $bug->setUpdatedAt(
                \DateTimeImmutable::createFromMutable($this->faker->dateTimeBetween('-100 days', '-1 days'))
            );
            $this->manager->persist($bug);
        }

        $this->manager->flush();
    }
}
