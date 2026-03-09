<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Domain\Entity\Label;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Label>
 */
final class LabelFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Label::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'name' => self::faker()->unique()->word(),
            'color' => self::faker()->hexColor(),
        ];
    }
}
