<?php

declare(strict_types=1);

namespace App\Application\DTO;

enum ActivityType: string
{
    case Comment = 'comment';
    case Audit = 'audit';
}
