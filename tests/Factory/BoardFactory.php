<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Domain\Entity\Board;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Board>
 */
final class BoardFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Board::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'name' => self::faker()->words(2, true),
            'project' => ProjectFactory::new(),
        ];
    }
}
