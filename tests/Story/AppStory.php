<?php

declare(strict_types=1);

namespace App\Tests\Story;

use App\Domain\Entity\Enum\CardPriority;
use App\Domain\Entity\Label;
use App\Domain\Entity\User;
use App\Tests\Factory\BoardFactory;
use App\Tests\Factory\CardFactory;
use App\Tests\Factory\ColumnFactory;
use App\Tests\Factory\CommentFactory;
use App\Tests\Factory\ProjectFactory;
use Doctrine\ORM\EntityManagerInterface;
use Zenstruck\Foundry\Story;

final class AppStory extends Story
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function build(): void
    {
        UserStory::load();
        LabelStory::load();

        $admin = UserStory::get('admin');
        assert($admin instanceof User);
        $alice = UserStory::get('alice');
        assert($alice instanceof User);
        $bob = UserStory::get('bob');
        assert($bob instanceof User);
        $charlie = UserStory::get('charlie');
        assert($charlie instanceof User);

        $labelBug = LabelStory::get('bug');
        assert($labelBug instanceof Label);
        $labelFeature = LabelStory::get('feature');
        assert($labelFeature instanceof Label);
        $labelHotfix = LabelStory::get('hotfix');
        assert($labelHotfix instanceof Label);
        $labelRefactor = LabelStory::get('refactor');
        assert($labelRefactor instanceof Label);
        $labelDocs = LabelStory::get('docs');
        assert($labelDocs instanceof Label);

        // --- Projects ---
        $projectApi = ProjectFactory::createOne(['name' => 'API Backend', 'color' => '#3b82f6']);
        $projectFront = ProjectFactory::createOne(['name' => 'Frontend App', 'color' => '#22c55e']);
        $projectInfra = ProjectFactory::createOne(['name' => 'Infrastructure', 'color' => '#f97316']);

        // --- Boards ---
        $boardApi = BoardFactory::createOne(['name' => 'Main Board', 'project' => $projectApi]);
        BoardFactory::createOne(['name' => 'Main Board', 'project' => $projectFront]);
        BoardFactory::createOne(['name' => 'Main Board', 'project' => $projectInfra]);

        // --- Columns (API board only) ---
        $colBacklog = ColumnFactory::createOne(['name' => 'Backlog', 'position' => 1000, 'board' => $boardApi]);
        $colTodo = ColumnFactory::createOne(['name' => 'To Do', 'position' => 2000, 'board' => $boardApi]);
        $colInProgress = ColumnFactory::createOne(['name' => 'In Progress', 'position' => 3000, 'board' => $boardApi]);
        $colReview = ColumnFactory::createOne(['name' => 'Review', 'position' => 4000, 'board' => $boardApi]);
        $colDone = ColumnFactory::createOne(['name' => 'Done', 'position' => 5000, 'board' => $boardApi]);

        // --- Cards ---
        $card1 = CardFactory::createOne([
            'title' => 'Set up authentication',
            'description' => 'Implement Symfony Security with form login, firewall and access control.',
            'position' => 1000,
            'priority' => CardPriority::HIGH,
            'author' => $admin,
            'column' => $colTodo,
        ]);
        $card1->addLabel($labelFeature);
        $card1->addAssignee($alice);

        $card2 = CardFactory::createOne([
            'title' => 'Fix CORS headers',
            'description' => 'API responses are missing CORS headers for the frontend app.',
            'position' => 2000,
            'priority' => CardPriority::CRITICAL,
            'author' => $alice,
            'column' => $colInProgress,
        ]);
        $card2->addLabel($labelBug);
        $card2->addLabel($labelHotfix);
        $card2->addAssignee($alice);
        $card2->addAssignee($bob);

        $card3 = CardFactory::createOne([
            'title' => 'Design board layout',
            'description' => 'Create the main Kanban board view with responsive columns.',
            'position' => 1000,
            'priority' => CardPriority::MEDIUM,
            'author' => $admin,
            'column' => $colReview,
        ]);
        $card3->addLabel($labelFeature);
        $card3->addAssignee($alice);

        $card4 = CardFactory::createOne([
            'title' => 'Set up CI/CD pipeline',
            'description' => null,
            'position' => 1000,
            'priority' => CardPriority::HIGH,
            'author' => $charlie,
            'column' => $colBacklog,
        ]);
        $card4->addLabel($labelFeature);
        $card4->addAssignee($charlie);

        $card5 = CardFactory::createOne([
            'title' => 'Refactor User entity',
            'description' => 'Extract validation logic into a dedicated service.',
            'position' => 3000,
            'priority' => CardPriority::LOW,
            'author' => $alice,
            'column' => $colTodo,
        ]);
        $card5->addLabel($labelRefactor);

        $card6 = CardFactory::createOne([
            'title' => 'Write API documentation',
            'description' => 'Document all REST endpoints with examples.',
            'position' => 2000,
            'priority' => null,
            'author' => $admin,
            'column' => $colBacklog,
        ]);
        $card6->addLabel($labelDocs);
        $card6->addAssignee($bob);

        $card7 = CardFactory::createOne([
            'title' => 'Implement drag & drop',
            'description' => 'Use SortableJS to enable card reordering within and between columns.',
            'position' => 1000,
            'priority' => CardPriority::HIGH,
            'author' => $admin,
            'column' => $colInProgress,
        ]);
        $card7->addLabel($labelFeature);
        $card7->addAssignee($alice);
        $card7->addAssignee($charlie);

        CardFactory::createOne([
            'title' => 'Fix login redirect loop',
            'description' => 'Users are stuck in a redirect loop after login when session expires.',
            'position' => 1000,
            'priority' => CardPriority::CRITICAL,
            'author' => $bob,
            'column' => $colDone,
        ]);

        CardFactory::createOne([
            'title' => 'Configure monitoring alerts',
            'description' => null,
            'position' => 3000,
            'priority' => CardPriority::MEDIUM,
            'author' => $charlie,
            'column' => $colBacklog,
        ]);

        $card10 = CardFactory::createOne([
            'title' => 'Add label filtering on board',
            'description' => 'Allow users to filter cards by one or more labels.',
            'position' => 2000,
            'priority' => CardPriority::MEDIUM,
            'author' => $admin,
            'column' => $colTodo,
        ]);
        $card10->addLabel($labelFeature);
        $card10->addAssignee($alice);
        $card10->addAssignee($bob);

        // Flush all M2M changes at once
        $this->em->flush();

        // --- Comments ---
        CommentFactory::createOne([
            'content' => 'I started working on the login form, should be ready by tomorrow.',
            'author' => $alice,
            'card' => $card1,
        ]);

        CommentFactory::createOne([
            'content' => "Don't forget to add CSRF protection on the login endpoint.",
            'author' => $admin,
            'card' => $card1,
        ]);

        CommentFactory::createOne([
            'content' => 'The issue only happens with preflight requests from localhost:3000.',
            'author' => $alice,
            'card' => $card2,
        ]);

        CommentFactory::createOne([
            'content' => 'I added Access-Control-Allow-Origin, but we still need to handle credentials.',
            'author' => $bob,
            'card' => $card2,
        ]);

        CommentFactory::createOne([
            'content' => 'SortableJS is integrated. Testing cross-column drag now.',
            'author' => $charlie,
            'card' => $card7,
        ]);
    }
}
