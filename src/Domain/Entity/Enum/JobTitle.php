<?php

declare(strict_types=1);

namespace App\Domain\Entity\Enum;

enum JobTitle: string
{
    case DEVELOPER = 'developer';
    case TESTER = 'tester';
    case SYS_ADMIN = 'sys_admin';
    case PRODUCT_OWNER = 'product_owner';
}
