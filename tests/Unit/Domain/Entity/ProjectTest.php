<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\Board;
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

    public function testBoardsCollectionIsEmptyByDefault(): void
    {
        self::assertCount(0, $this->project->getBoards());
    }

    public function testAddBoard(): void
    {
        $board = new Board();
        $board->setName('Sprint 1');

        $this->project->addBoard($board);

        self::assertCount(1, $this->project->getBoards());
        self::assertTrue($this->project->getBoards()->contains($board));
        self::assertSame($this->project, $board->getProject());
    }

    public function testAddBoardIsIdempotent(): void
    {
        $board = new Board();
        $board->setName('Sprint 1');

        $this->project->addBoard($board);
        $this->project->addBoard($board);

        self::assertCount(1, $this->project->getBoards());
    }

    public function testRemoveBoard(): void
    {
        $board = new Board();
        $board->setName('Sprint 1');

        $this->project->addBoard($board);
        $this->project->removeBoard($board);

        self::assertCount(0, $this->project->getBoards());
    }

    public function testToString(): void
    {
        $this->project->setName('My Project');
        self::assertSame('My Project', (string) $this->project);
    }

    public function testFluentSetters(): void
    {
        $result = $this->project
            ->setName('Project')
            ->setColor('#ffffff');

        self::assertSame($this->project, $result);
    }
}
