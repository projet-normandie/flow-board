<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\Board;
use App\Domain\Entity\Column;
use App\Domain\Entity\Project;
use PHPUnit\Framework\TestCase;

final class BoardTest extends TestCase
{
    private Board $board;

    protected function setUp(): void
    {
        $this->board = new Board();
    }

    public function testIdIsNullByDefault(): void
    {
        self::assertNull($this->board->getId());
    }

    public function testName(): void
    {
        $this->board->setName('Main Board');
        self::assertSame('Main Board', $this->board->getName());
    }

    public function testProject(): void
    {
        $project = new Project();
        $project->setName('API Backend');
        $this->board->setProject($project);
        self::assertSame($project, $this->board->getProject());
    }

    public function testColumnsCollectionIsEmptyByDefault(): void
    {
        self::assertCount(0, $this->board->getColumns());
    }

    public function testAddColumn(): void
    {
        $column = new Column();
        $column->setName('To Do');
        $column->setPosition(1000);

        $this->board->addColumn($column);

        self::assertCount(1, $this->board->getColumns());
        self::assertTrue($this->board->getColumns()->contains($column));
        self::assertSame($this->board, $column->getBoard());
    }

    public function testAddColumnIsIdempotent(): void
    {
        $column = new Column();
        $column->setName('To Do');
        $column->setPosition(1000);

        $this->board->addColumn($column);
        $this->board->addColumn($column);

        self::assertCount(1, $this->board->getColumns());
    }

    public function testRemoveColumn(): void
    {
        $column = new Column();
        $column->setName('To Do');
        $column->setPosition(1000);

        $this->board->addColumn($column);
        $this->board->removeColumn($column);

        self::assertCount(0, $this->board->getColumns());
    }

    public function testToString(): void
    {
        $this->board->setName('Sprint Board');
        self::assertSame('Sprint Board', (string) $this->board);
    }

    public function testFluentSetters(): void
    {
        $project = new Project();
        $project->setName('Test Project');

        $result = $this->board
            ->setName('Main Board')
            ->setProject($project);

        self::assertSame($this->board, $result);
    }
}
