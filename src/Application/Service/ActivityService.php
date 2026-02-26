<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\DTO\ActivityItem;
use App\Application\DTO\ActivityType;
use App\Domain\Entity\Card;
use App\Infrastructure\Doctrine\Repository\CardRepository;
use App\Infrastructure\Doctrine\Repository\ColumnRepository;
use App\Infrastructure\Doctrine\Repository\CommentRepository;
use App\Infrastructure\Doctrine\Repository\LabelRepository;
use App\Infrastructure\Doctrine\Repository\UserRepository;
use DH\Auditor\Model\Entry;
use DH\Auditor\Provider\Doctrine\Persistence\Reader\Reader;

class ActivityService
{
    public function __construct(
        private readonly Reader $reader,
        private readonly CommentRepository $commentRepository,
        private readonly ColumnRepository $columnRepository,
        private readonly LabelRepository $labelRepository,
        private readonly UserRepository $userRepository,
        private readonly CardRepository $cardRepository,
    ) {
    }

    /**
     * @return list<ActivityItem>
     */
    public function getCardTimeline(Card $card, int $limit = 50): array
    {
        $items = [];

        $query = $this->reader->createQuery(Card::class, [
            'object_id' => (string) $card->getId(),
            'page_size' => $limit,
            'page' => 1,
        ]);

        /** @var Entry[] $entries */
        $entries = $query->execute();

        foreach ($entries as $entry) {
            $auditItems = $this->buildAuditItems($entry);
            foreach ($auditItems as $item) {
                $items[] = $item;
            }
        }

        $comments = $this->commentRepository->findByCard($card);
        foreach ($comments as $comment) {
            $items[] = new ActivityItem(
                activityType: ActivityType::Comment,
                occurredAt: $comment->getCreatedAt(),
                username: $comment->getAuthor()->getFullName(),
                userEmail: $comment->getAuthor()->getEmail(),
                commentId: $comment->getId(),
                commentContent: $comment->getContent(),
                commentAuthorId: $comment->getAuthor()->getId(),
            );
        }

        usort($items, static fn (ActivityItem $a, ActivityItem $b): int => $b->occurredAt <=> $a->occurredAt);

        return \array_slice($items, 0, $limit);
    }

    /**
     * @return list<array{item: ActivityItem, card: Card|null}>
     */
    public function getRecentActivity(int $limit = 15): array
    {
        $query = $this->reader->createQuery(Card::class, [
            'page_size' => $limit * 3,
            'page' => 1,
        ]);

        /** @var Entry[] $entries */
        $entries = $query->execute();

        $results = [];
        foreach ($entries as $entry) {
            $auditItems = $this->buildAuditItems($entry);
            /** @var Card|null $card */
            $card = $this->cardRepository->find((int) $entry->objectId);

            foreach ($auditItems as $item) {
                $results[] = ['item' => $item, 'card' => $card];
            }
        }

        return \array_slice($results, 0, $limit);
    }

    /**
     * @return list<ActivityItem>
     */
    private function buildAuditItems(Entry $entry): array
    {
        $username = $entry->username ?? 'System';
        $userEmail = '';

        if ($entry->userId !== null) {
            $user = $this->userRepository->find($entry->userId);
            if ($user !== null) {
                $username = $user->getFullName();
                $userEmail = $user->getEmail();
            }
        }

        $createdAt = $entry->createdAt ?? new \DateTimeImmutable();

        return match ($entry->type) {
            'insert' => [$this->buildInsertItem($username, $userEmail, $createdAt)],
            'remove' => [$this->buildRemoveItem($username, $userEmail, $createdAt)],
            'update' => $this->buildUpdateItems($entry, $username, $userEmail, $createdAt),
            'associate' => $this->buildAssociateItems($entry, $username, $userEmail, $createdAt),
            'dissociate' => $this->buildDissociateItems($entry, $username, $userEmail, $createdAt),
            default => [],
        };
    }

    private function buildInsertItem(
        string $username,
        string $userEmail,
        \DateTimeImmutable $createdAt,
    ): ActivityItem {
        return new ActivityItem(
            activityType: ActivityType::Audit,
            occurredAt: $createdAt,
            username: $username,
            userEmail: $userEmail,
            auditAction: 'insert',
            translationKey: 'activity.card.created',
            translationParams: [],
            iconClass: 'bi-plus-circle',
        );
    }

    private function buildRemoveItem(
        string $username,
        string $userEmail,
        \DateTimeImmutable $createdAt,
    ): ActivityItem {
        return new ActivityItem(
            activityType: ActivityType::Audit,
            occurredAt: $createdAt,
            username: $username,
            userEmail: $userEmail,
            auditAction: 'remove',
            translationKey: 'activity.card.archived',
            translationParams: [],
            iconClass: 'bi-archive',
        );
    }

    /**
     * @return list<ActivityItem>
     */
    private function buildUpdateItems(
        Entry $entry,
        string $username,
        string $userEmail,
        \DateTimeImmutable $createdAt,
    ): array {
        $diffs = $entry->getDiffs();
        $items = [];

        foreach ($diffs as $field => $diff) {
            if (!\is_string($field) || !\is_array($diff)) {
                continue;
            }

            /** @var array<string, mixed> $diff */
            $item = $this->buildUpdateItemForField(
                $field,
                $diff,
                $username,
                $userEmail,
                $createdAt,
            );
            if ($item !== null) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * @param array<string, mixed> $diff
     */
    private function buildUpdateItemForField(
        string $field,
        array $diff,
        string $username,
        string $userEmail,
        \DateTimeImmutable $createdAt,
    ): ?ActivityItem {
        $newValue = isset($diff['new']) && \is_scalar($diff['new'])
            ? (string) $diff['new']
            : '';

        return match ($field) {
            'title' => new ActivityItem(
                activityType: ActivityType::Audit,
                occurredAt: $createdAt,
                username: $username,
                userEmail: $userEmail,
                auditAction: 'update',
                translationKey: 'activity.card.title_changed',
                translationParams: ['%new%' => $newValue],
                iconClass: 'bi-pencil',
            ),
            'description' => new ActivityItem(
                activityType: ActivityType::Audit,
                occurredAt: $createdAt,
                username: $username,
                userEmail: $userEmail,
                auditAction: 'update',
                translationKey: 'activity.card.description_changed',
                translationParams: [],
                iconClass: 'bi-text-left',
            ),
            'priority' => new ActivityItem(
                activityType: ActivityType::Audit,
                occurredAt: $createdAt,
                username: $username,
                userEmail: $userEmail,
                auditAction: 'update',
                translationKey: 'activity.card.priority_changed',
                translationParams: ['%value%' => $newValue],
                iconClass: 'bi-flag',
            ),
            'dueDate' => new ActivityItem(
                activityType: ActivityType::Audit,
                occurredAt: $createdAt,
                username: $username,
                userEmail: $userEmail,
                auditAction: 'update',
                translationKey: 'activity.card.due_date_changed',
                translationParams: ['%value%' => $newValue],
                iconClass: 'bi-calendar-event',
            ),
            'column' => $this->buildColumnMoveItem(
                $diff['new'] ?? null,
                $username,
                $userEmail,
                $createdAt,
            ),
            'position' => null,
            default => null,
        };
    }

    private function buildColumnMoveItem(
        mixed $newColumn,
        string $username,
        string $userEmail,
        \DateTimeImmutable $createdAt,
    ): ActivityItem {
        $columnName = '?';
        if (\is_array($newColumn) && isset($newColumn['label']) && \is_string($newColumn['label'])) {
            $columnName = $newColumn['label'];
        } elseif (\is_scalar($newColumn)) {
            $column = $this->columnRepository->find((int) $newColumn);
            if ($column !== null) {
                $columnName = $column->getName();
            }
        }

        return new ActivityItem(
            activityType: ActivityType::Audit,
            occurredAt: $createdAt,
            username: $username,
            userEmail: $userEmail,
            auditAction: 'update',
            translationKey: 'activity.card.moved_to_column',
            translationParams: ['%column%' => $columnName],
            iconClass: 'bi-arrows-move',
        );
    }

    /**
     * @return list<ActivityItem>
     */
    private function buildAssociateItems(
        Entry $entry,
        string $username,
        string $userEmail,
        \DateTimeImmutable $createdAt,
    ): array {
        return $this->buildRelationItems(
            $entry,
            $username,
            $userEmail,
            $createdAt,
            'associate',
        );
    }

    /**
     * @return list<ActivityItem>
     */
    private function buildDissociateItems(
        Entry $entry,
        string $username,
        string $userEmail,
        \DateTimeImmutable $createdAt,
    ): array {
        return $this->buildRelationItems(
            $entry,
            $username,
            $userEmail,
            $createdAt,
            'dissociate',
        );
    }

    /**
     * @return list<ActivityItem>
     */
    private function buildRelationItems(
        Entry $entry,
        string $username,
        string $userEmail,
        \DateTimeImmutable $createdAt,
        string $action,
    ): array {
        $diffs = $entry->getDiffs();
        $items = [];
        $isAssociate = $action === 'associate';

        foreach ($diffs as $diff) {
            if (!\is_array($diff)) {
                continue;
            }

            $table = isset($diff['table']) && \is_string($diff['table'])
                ? $diff['table']
                : '';
            $idKey = $isAssociate ? 'new' : 'old';
            $id = $diff['id'] ?? ($diff[$idKey] ?? null);

            if ($table === 'card_label' || str_contains($table, 'label')) {
                $labelName = $this->resolveName($id, 'label');
                $items[] = new ActivityItem(
                    activityType: ActivityType::Audit,
                    occurredAt: $createdAt,
                    username: $username,
                    userEmail: $userEmail,
                    auditAction: $action,
                    translationKey: $isAssociate
                        ? 'activity.card.label_added'
                        : 'activity.card.label_removed',
                    translationParams: ['%label%' => $labelName],
                    iconClass: 'bi-tag',
                );
            } elseif ($table === 'card_user' || str_contains($table, 'user')) {
                $assigneeName = $this->resolveName($id, 'user');
                $items[] = new ActivityItem(
                    activityType: ActivityType::Audit,
                    occurredAt: $createdAt,
                    username: $username,
                    userEmail: $userEmail,
                    auditAction: $action,
                    translationKey: $isAssociate
                        ? 'activity.card.assignee_added'
                        : 'activity.card.assignee_removed',
                    translationParams: ['%user%' => $assigneeName],
                    iconClass: $isAssociate ? 'bi-person-plus' : 'bi-person-dash',
                );
            }
        }

        return $items;
    }

    private function resolveName(mixed $id, string $type): string
    {
        if (!\is_scalar($id)) {
            return '?';
        }

        if ($type === 'label') {
            $label = $this->labelRepository->find((int) $id);

            return $label !== null ? $label->getName() : '?';
        }

        $user = $this->userRepository->find((int) $id);

        return $user !== null ? $user->getFullName() : '?';
    }
}
