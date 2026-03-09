<?php

declare(strict_types=1);

namespace App\Tests\Story;

use App\Tests\Factory\LabelFactory;
use Zenstruck\Foundry\Story;

final class LabelStory extends Story
{
    public function build(): void
    {
        $this->addState('bug', LabelFactory::new()->with(['name' => 'Bug', 'color' => '#ef4444'])->create());
        $this->addState('feature', LabelFactory::new()->with(['name' => 'Feature', 'color' => '#3b82f6'])->create());
        $this->addState('hotfix', LabelFactory::new()->with(['name' => 'Hotfix', 'color' => '#f97316'])->create());
        $this->addState('refactor', LabelFactory::new()->with(['name' => 'Refactor', 'color' => '#8b5cf6'])->create());
        $this->addState('docs', LabelFactory::new()->with(['name' => 'Docs', 'color' => '#6b7280'])->create());
    }
}
