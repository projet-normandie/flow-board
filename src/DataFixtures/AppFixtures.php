<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Domain\Entity\Board;
use App\Domain\Entity\Card;
use App\Domain\Entity\Column;
use App\Domain\Entity\Enum\CardPriority;
use App\Domain\Entity\Enum\JobTitle;
use App\Domain\Entity\Label;
use App\Domain\Entity\Project;
use App\Domain\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // --- Users ---
        $fixturePassword = 'FlowBoard2026!';

        $admin = $this->createUser('admin@flowboard.dev', 'Admin', ['ROLE_ADMIN'], JobTitle::PRODUCT_OWNER);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, $fixturePassword));
        $manager->persist($admin);

        $superAdmin = $this->createUser('superadmin@flowboard.dev', 'Super Admin', ['ROLE_SUPER_ADMIN'], null);
        $superAdmin->setPassword($this->passwordHasher->hashPassword($superAdmin, $fixturePassword));
        $manager->persist($superAdmin);

        $alice = $this->createUser('alice@flowboard.dev', 'Alice Martin', ['ROLE_USER'], JobTitle::DEVELOPER);
        $alice->setPassword($this->passwordHasher->hashPassword($alice, $fixturePassword));
        $manager->persist($alice);

        $bob = $this->createUser('bob@flowboard.dev', 'Bob Dupont', ['ROLE_USER'], JobTitle::TESTER);
        $bob->setPassword($this->passwordHasher->hashPassword($bob, $fixturePassword));
        $manager->persist($bob);

        $charlie = $this->createUser('charlie@flowboard.dev', 'Charlie Durand', ['ROLE_USER'], JobTitle::SYS_ADMIN);
        $charlie->setPassword($this->passwordHasher->hashPassword($charlie, $fixturePassword));
        $manager->persist($charlie);

        // --- Projects ---
        $projectApi = (new Project())->setName('API Backend')->setColor('#3b82f6');
        $projectFront = (new Project())->setName('Frontend App')->setColor('#22c55e');
        $projectInfra = (new Project())->setName('Infrastructure')->setColor('#f97316');
        $manager->persist($projectApi);
        $manager->persist($projectFront);
        $manager->persist($projectInfra);

        // --- Boards ---
        $boardApi = (new Board())->setName('Main Board')->setProject($projectApi);
        $boardFront = (new Board())->setName('Main Board')->setProject($projectFront);
        $boardInfra = (new Board())->setName('Main Board')->setProject($projectInfra);
        $manager->persist($boardApi);
        $manager->persist($boardFront);
        $manager->persist($boardInfra);

        // --- Columns ---
        $colBacklog = (new Column())->setName('Backlog')->setPosition(1000)->setBoard($boardApi);
        $colTodo = (new Column())->setName('To Do')->setPosition(2000)->setBoard($boardApi);
        $colInProgress = (new Column())->setName('In Progress')->setPosition(3000)->setBoard($boardApi);
        $colReview = (new Column())->setName('Review')->setPosition(4000)->setBoard($boardApi);
        $colDone = (new Column())->setName('Done')->setPosition(5000)->setBoard($boardApi);
        $manager->persist($colBacklog);
        $manager->persist($colTodo);
        $manager->persist($colInProgress);
        $manager->persist($colReview);
        $manager->persist($colDone);

        // --- Labels ---
        $labelBug = (new Label())->setName('Bug')->setColor('#ef4444');
        $labelFeature = (new Label())->setName('Feature')->setColor('#3b82f6');
        $labelHotfix = (new Label())->setName('Hotfix')->setColor('#f97316');
        $labelRefactor = (new Label())->setName('Refactor')->setColor('#8b5cf6');
        $labelDocs = (new Label())->setName('Docs')->setColor('#6b7280');
        $manager->persist($labelBug);
        $manager->persist($labelFeature);
        $manager->persist($labelHotfix);
        $manager->persist($labelRefactor);
        $manager->persist($labelDocs);

        // --- Cards ---
        $card1 = (new Card())
            ->setTitle('Set up authentication')
            ->setDescription('Implement Symfony Security with form login, firewall and access control.')
            ->setPosition(1000)
            ->setPriority(CardPriority::HIGH)
            ->setAuthor($admin)
            ->setColumn($colTodo)
            ->addLabel($labelFeature)
            ->addAssignee($alice);
        $manager->persist($card1);

        $card2 = (new Card())
            ->setTitle('Fix CORS headers')
            ->setDescription('API responses are missing CORS headers for the frontend app.')
            ->setPosition(2000)
            ->setPriority(CardPriority::CRITICAL)
            ->setAuthor($alice)
            ->setColumn($colInProgress)
            ->addLabel($labelBug)
            ->addLabel($labelHotfix)
            ->addAssignee($alice)
            ->addAssignee($bob);
        $manager->persist($card2);

        $card3 = (new Card())
            ->setTitle('Design board layout')
            ->setDescription('Create the main Kanban board view with responsive columns.')
            ->setPosition(1000)
            ->setPriority(CardPriority::MEDIUM)
            ->setAuthor($admin)
            ->setColumn($colReview)
            ->addLabel($labelFeature)
            ->addAssignee($alice);
        $manager->persist($card3);

        $card4 = (new Card())
            ->setTitle('Set up CI/CD pipeline')
            ->setPosition(1000)
            ->setPriority(CardPriority::HIGH)
            ->setAuthor($charlie)
            ->setColumn($colBacklog)
            ->addLabel($labelFeature)
            ->addAssignee($charlie);
        $manager->persist($card4);

        $card5 = (new Card())
            ->setTitle('Refactor User entity')
            ->setDescription('Extract validation logic into a dedicated service.')
            ->setPosition(3000)
            ->setPriority(CardPriority::LOW)
            ->setAuthor($alice)
            ->setColumn($colTodo)
            ->addLabel($labelRefactor);
        $manager->persist($card5);

        $card6 = (new Card())
            ->setTitle('Write API documentation')
            ->setDescription('Document all REST endpoints with examples.')
            ->setPosition(2000)
            ->setAuthor($admin)
            ->setColumn($colBacklog)
            ->addLabel($labelDocs)
            ->addAssignee($bob);
        $manager->persist($card6);

        $card7 = (new Card())
            ->setTitle('Implement drag & drop')
            ->setDescription('Use SortableJS to enable card reordering within and between columns.')
            ->setPosition(1000)
            ->setPriority(CardPriority::HIGH)
            ->setDueDate(new \DateTimeImmutable('+7 days'))
            ->setAuthor($admin)
            ->setColumn($colInProgress)
            ->addLabel($labelFeature)
            ->addAssignee($alice)
            ->addAssignee($charlie);
        $manager->persist($card7);

        $card8 = (new Card())
            ->setTitle('Fix login redirect loop')
            ->setDescription('Users are stuck in a redirect loop after login when session expires.')
            ->setPosition(1000)
            ->setPriority(CardPriority::CRITICAL)
            ->setDueDate(new \DateTimeImmutable('+1 day'))
            ->setAuthor($bob)
            ->setColumn($colDone)
            ->addLabel($labelBug)
            ->addAssignee($bob);
        $manager->persist($card8);

        $card9 = (new Card())
            ->setTitle('Configure monitoring alerts')
            ->setPosition(3000)
            ->setPriority(CardPriority::MEDIUM)
            ->setAuthor($charlie)
            ->setColumn($colBacklog)
            ->addAssignee($charlie);
        $manager->persist($card9);

        $card10 = (new Card())
            ->setTitle('Add label filtering on board')
            ->setDescription('Allow users to filter cards by one or more labels.')
            ->setPosition(2000)
            ->setPriority(CardPriority::MEDIUM)
            ->setDueDate(new \DateTimeImmutable('+14 days'))
            ->setAuthor($admin)
            ->setColumn($colTodo)
            ->addLabel($labelFeature)
            ->addAssignee($alice)
            ->addAssignee($bob);
        $manager->persist($card10);

        $manager->flush();
    }

    /**
     * @param list<string> $roles
     */
    private function createUser(string $email, string $fullName, array $roles, ?JobTitle $jobTitle): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setFullName($fullName);
        $user->setRoles($roles);
        $user->setJobTitle($jobTitle);

        return $user;
    }
}
