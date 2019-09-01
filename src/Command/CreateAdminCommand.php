<?php

namespace App\Command;

use App\Entity\Subscription;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CreateAdminCommand extends Command
{
    protected static $defaultName = 'app:create-admin';
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Console admin creation')
            ->addArgument('email', InputArgument::REQUIRED, 'Admin email')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');

        $subscriptions = $this->em->getRepository(Subscription::class)->findAll();
        $subscription = $subscriptions[rand(0,count($subscriptions)-1)];
        $user = new User();
        $user->setEmail($email);
        $user->setRoles(['ROLE_USER,ROLE_ADMIN']);
        $user->setSubscription($subscription);
        $this->em->persist($user);
        $this->em->flush();
        $io->success('You successfully created a new administrator with the following email : '.$user->getEmail());
    }
}
