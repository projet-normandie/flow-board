<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class ActivityItem
{
    public function __construct(
        public ActivityType $activityType,
        public \DateTimeImmutable $occurredAt,
        public string $username,
        public string $userEmail,
        public ?int $commentId = null,
        public ?string $commentContent = null,
        public ?int $commentAuthorId = null,
        public ?string $auditAction = null,
        public ?string $translationKey = null,
        /** @var array<string, string> */
        public ?array $translationParams = null,
        public ?string $iconClass = null,
    ) {
    }
}
