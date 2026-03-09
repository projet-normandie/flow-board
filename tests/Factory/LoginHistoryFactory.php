<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Domain\Entity\Enum\AuthType;
use App\Domain\Entity\LoginHistory;
use App\Domain\Entity\User;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<LoginHistory>
 */
final class LoginHistoryFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return LoginHistory::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'user' => UserFactory::new(),
            'ipAddress' => self::faker()->ipv4(),
            'userAgent' => self::faker()->userAgent(),
            'authType' => self::faker()->randomElement(AuthType::cases()),
        ];
    }

    protected function initialize(): static
    {
        return $this->instantiateWith(function (array $attributes): LoginHistory {
            assert($attributes['user'] instanceof User);
            assert(is_string($attributes['ipAddress']));
            assert(is_string($attributes['userAgent']));
            assert($attributes['authType'] instanceof AuthType);

            return new LoginHistory(
                $attributes['user'],
                $attributes['ipAddress'],
                $attributes['userAgent'],
                $attributes['authType'],
            );
        });
    }
}
