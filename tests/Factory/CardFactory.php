<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Domain\Entity\Card;
use App\Domain\Entity\Enum\CardPriority;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Card>
 */
final class CardFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Card::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'title' => self::faker()->sentence(4),
            'description' => self::faker()->optional(0.7)->paragraph(),
            'position' => self::faker()->numberBetween(1, 100) * 1000,
            'priority' => self::faker()->optional(0.8)->randomElement(CardPriority::cases()),
            'author' => UserFactory::new(),
            'column' => ColumnFactory::new(),
        ];
    }
}
