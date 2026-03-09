<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Domain\Entity\Comment;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Comment>
 */
final class CommentFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Comment::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'content' => self::faker()->paragraph(),
            'author' => UserFactory::new(),
            'card' => CardFactory::new(),
        ];
    }
}
