<?php

namespace App\Command;

use App\Repository\JobRepository;
use App\Service\FranceTravailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:jobs:fetch',
    description: 'Récupère les offres depuis les sources externes et met à jour le statut des offres existantes.',
)]
class FetchJobsCommand extends Command
{
    public function __construct(
        private FranceTravailService $franceTravailService,
        private JobRepository $jobRepository,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->section('Récupération des offres France Travail');
        $result = $this->franceTravailService->fetchAndStoreAlternances();
        $io->writeln(sprintf('Créées : %d / Mises à jour : %d', $result['created'], $result['updated']));

        $io->section('Rafraîchissement des statuts');
        $stale = $this->jobRepository->findStaleJobs(olderThanMinutes: 30);

        foreach ($stale as $job) {
            $job->refreshStatus();
        }
        $this->entityManager->flush();

        $io->writeln(sprintf('%d offre(s) ré-évaluée(s)', count($stale)));

        $io->success('Synchronisation terminée.');

        return Command::SUCCESS;
    }
}