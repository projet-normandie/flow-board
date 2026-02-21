<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity\Trait;

use PHPUnit\Framework\TestCase;

final class TimestampableTraitTest extends TestCase
{
    private TimestampableEntity $entity;

    protected function setUp(): void
    {
        $this->entity = new TimestampableEntity();
    }

    public function testOnPrePersistSetsTimestamps(): void
    {
        $before = new \DateTimeImmutable();
        $this->entity->onPrePersist();
        $after = new \DateTimeImmutable();

        self::assertGreaterThanOrEqual($before, $this->entity->getCreatedAt());
        self::assertLessThanOrEqual($after, $this->entity->getCreatedAt());
        self::assertGreaterThanOrEqual($before, $this->entity->getUpdatedAt());
        self::assertLessThanOrEqual($after, $this->entity->getUpdatedAt());
    }

    public function testOnPrePersistSetsCreatedAtAndUpdatedAtToSameValue(): void
    {
        $this->entity->onPrePersist();

        self::assertEquals(
            $this->entity->getCreatedAt(),
            $this->entity->getUpdatedAt()
        );
    }

    public function testOnPreUpdateChangesUpdatedAt(): void
    {
        $this->entity->onPrePersist();
        $originalUpdatedAt = $this->entity->getUpdatedAt();
        $originalCreatedAt = $this->entity->getCreatedAt();

        usleep(1000);
        $this->entity->onPreUpdate();

        self::assertSame($originalCreatedAt, $this->entity->getCreatedAt());
        self::assertGreaterThanOrEqual($originalUpdatedAt, $this->entity->getUpdatedAt());
    }
}
