<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:install',
    description: 'Install the application and create the first admin user',
)]
class InstallCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('JiraExporter Installation');

        // Run migrations
        $io->section('Ausführung der Datenbankmigrationen...');
        
        // Note: In real implementation, we would run migrations programmatically
        $io->info('Bitte führen Sie manuell aus: php bin/console doctrine:migrations:migrate');

        // Create admin user
        $io->section('Erstelle Admin-Benutzer');

        $helper = $this->getHelper('question');
        
        $usernameQuestion = new Question('Admin Benutzername: ');
        $usernameQuestion->setValidator(function ($answer) {
            if (empty($answer)) {
                throw new \RuntimeException('Benutzername darf nicht leer sein');
            }
            return $answer;
        });
        
        $passwordQuestion = new Question('Admin Passwort: ');
        $passwordQuestion->setHidden(true);
        $passwordQuestion->setValidator(function ($answer) {
            if (empty($answer)) {
                throw new \RuntimeException('Passwort darf nicht leer sein');
            }
            return $answer;
        });

        $username = $helper->ask($input, $output, $usernameQuestion);
        $password = $helper->ask($input, $output, $passwordQuestion);

        // Check if user already exists
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
        if ($existingUser) {
            $io->warning('Benutzer existiert bereits. Passwort wird aktualisiert.');
            $user = $existingUser;
        } else {
            $user = new User();
            $user->setUsername($username);
        }

        // Hash password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        $user->setRoles(['ROLE_ADMIN']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success('Installation abgeschlossen!');
        $io->info('Admin-Benutzer wurde erstellt: ' . $username);
        $io->info('Sie können sich nun unter http://localhost:8087/login anmelden.');

        return Command::SUCCESS;
    }
}
