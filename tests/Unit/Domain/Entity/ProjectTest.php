<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\Project;
use PHPUnit\Framework\TestCase;

final class ProjectTest extends TestCase
{
    private Project $project;

    protected function setUp(): void
    {
        $this->project = new Project();
    }

    public function testIdIsNullByDefault(): void
    {
        self::assertNull($this->project->getId());
    }

    public function testName(): void
    {
        $this->project->setName('My Project');
        self::assertSame('My Project', $this->project->getName());
    }

    public function testColor(): void
    {
        $this->project->setColor('#3b82f6');
        self::assertSame('#3b82f6', $this->project->getColor());
    }

    public function testCardsCollectionIsEmptyByDefault(): void
    {
        self::assertCount(0, $this->project->getCards());
    }

    public function testFluentSetters(): void
    {
        $result = $this->project
            ->setName('Project')
            ->setColor('#ffffff');

        self::assertSame($this->project, $result);
    }
}
