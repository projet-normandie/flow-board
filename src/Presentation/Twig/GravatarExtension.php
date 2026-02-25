<?php

declare(strict_types=1);

namespace App\Presentation\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class GravatarExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('gravatar', $this->gravatar(...)),
        ];
    }

    public function gravatar(string $email, int $size = 26): string
    {
        $hash = md5(strtolower(trim($email)));

        return "https://www.gravatar.com/avatar/{$hash}?d=mp&s={$size}";
    }
}
