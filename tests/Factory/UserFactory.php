<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Domain\Entity\Enum\JobTitle;
use App\Domain\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<User>
 */
final class UserFactory extends PersistentObjectFactory
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    public static function class(): string
    {
        return User::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'email' => self::faker()->unique()->safeEmail(),
            'fullName' => self::faker()->name(),
            'roles' => ['ROLE_USER'],
            'jobTitle' => self::faker()->randomElement(JobTitle::cases()),
            'enabled' => true,
            'password' => 'FlowBoard2026!',
        ];
    }

    protected function initialize(): static
    {
        return $this->afterInstantiate(function (User $user, array $attributes): void {
            assert(is_string($attributes['password']));
            $user->setPassword(
                $this->passwordHasher->hashPassword($user, $attributes['password'])
            );
        });
    }

    public function asAdmin(): static
    {
        return $this->with([
            'email' => 'admin@flowboard.dev',
            'fullName' => 'Admin',
            'roles' => ['ROLE_ADMIN'],
            'jobTitle' => JobTitle::PRODUCT_OWNER,
        ]);
    }

    public function asSuperAdmin(): static
    {
        return $this->with([
            'email' => 'superadmin@flowboard.dev',
            'fullName' => 'Super Admin',
            'roles' => ['ROLE_SUPER_ADMIN'],
            'jobTitle' => null,
        ]);
    }
}
