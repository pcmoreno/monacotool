<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:create-super-admin',
    description: 'Creates the super admin user. Can only be run once.',
)]
class CreateSuperAdminCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($this->userRepository->hasSuperAdmin()) {
            $io->error('A super admin already exists. Only one super admin is allowed.');

            return Command::FAILURE;
        }

        $io->title('Create Super Admin');

        $email = $io->ask('Email', validator: function (string $value): string {
            $value = mb_strtolower(trim($value));
            $violations = $this->validator->validate($value, [new NotBlank(), new Email()]);
            if (count($violations) > 0) {
                throw new \RuntimeException((string) $violations->get(0)->getMessage());
            }
            if ($this->userRepository->findOneBy(['email' => $value]) !== null) {
                throw new \RuntimeException('A user with this email already exists.');
            }

            return $value;
        });

        $name = $io->ask('Name', validator: fn (string $value): string => match (true) {
            trim($value) === '' => throw new \RuntimeException('Name cannot be blank.'),
            default => trim($value),
        });

        $password = $io->askHidden('Password (hidden)', validator: function (string $value): string {
            if (strlen($value) < 8) {
                throw new \RuntimeException('Password must be at least 8 characters.');
            }
            if (strlen($value) > 72) {
                throw new \RuntimeException('Password must be 72 characters or fewer.');
            }

            return $value;
        });

        $io->askHidden('Confirm password (hidden)', validator: function (string $value) use ($password): string {
            if ($value !== $password) {
                throw new \RuntimeException('Passwords do not match.');
            }

            return $value;
        });

        $user = new User();
        $user->setEmail($email);
        $user->setName($name);
        $user->setRoles(['ROLE_SUPER_ADMIN']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setIsVerified(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('Super admin "%s" created successfully.', $email));

        return Command::SUCCESS;
    }
}
