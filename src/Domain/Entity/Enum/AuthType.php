<?php

declare(strict_types=1);

namespace App\Domain\Entity\Enum;

enum AuthType: string
{
    case FORM_LOGIN = 'form_login';
    case REMEMBER_ME = 'remember_me';
}
