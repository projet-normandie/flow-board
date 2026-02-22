<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Create an admin user',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $io->ask('Email', null, function (mixed $value): string {
            if (!is_string($value) || $value === '' || filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                throw new \RuntimeException('Please enter a valid email address.');
            }

            return $value;
        });

        $fullName = $io->ask('Full name', null, function (mixed $value): string {
            if (!is_string($value) || trim($value) === '') {
                throw new \RuntimeException('Full name cannot be empty.');
            }

            return $value;
        });

        $plainPassword = $io->askHidden('Password', function (mixed $value): string {
            if (!is_string($value) || strlen($value) < 8) {
                throw new \RuntimeException('Password must be at least 8 characters.');
            }

            return $value;
        });

        /** @var string $email */
        /** @var string $fullName */
        /** @var string $plainPassword */

        $user = new User();
        $user->setEmail($email);
        $user->setFullName($fullName);
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        $user->setRoles(['ROLE_ADMIN']);
        $user->setEnabled(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('Admin user "%s" created successfully.', $email));

        return Command::SUCCESS;
    }
}
