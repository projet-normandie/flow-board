<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Domain\Entity\Column;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Column>
 */
final class ColumnFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Column::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'name' => self::faker()->word(),
            'position' => self::faker()->numberBetween(1, 20) * 1000,
            'board' => BoardFactory::new(),
        ];
    }
}
