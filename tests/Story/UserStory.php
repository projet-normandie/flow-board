<?php

declare(strict_types=1);

namespace App\Tests\Story;

use App\Domain\Entity\Enum\JobTitle;
use App\Tests\Factory\UserFactory;
use Zenstruck\Foundry\Story;

final class UserStory extends Story
{
    public function build(): void
    {
        $this->addState('admin', UserFactory::new()->asAdmin()->create());
        $this->addState('superAdmin', UserFactory::new()->asSuperAdmin()->create());

        $this->addState('alice', UserFactory::new()->with([
            'email' => 'alice@flowboard.dev',
            'fullName' => 'Alice Martin',
            'jobTitle' => JobTitle::DEVELOPER,
        ])->create());

        $this->addState('bob', UserFactory::new()->with([
            'email' => 'bob@flowboard.dev',
            'fullName' => 'Bob Dupont',
            'jobTitle' => JobTitle::TESTER,
        ])->create());

        $this->addState('charlie', UserFactory::new()->with([
            'email' => 'charlie@flowboard.dev',
            'fullName' => 'Charlie Durand',
            'jobTitle' => JobTitle::SYS_ADMIN,
        ])->create());
    }
}
