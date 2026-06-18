<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

#[AsCommand(
    name: 'app:build-static',
    description: 'Pré-rend le site dans dist/ pour un déploiement statique.'
)]
final class BuildStaticCommand extends Command
{
    public function __construct(
        private readonly HttpKernelInterface $kernel,
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string              $projectDir,
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $fs = new Filesystem();

        $distDir = $this->projectDir . '/dist';
        $publicDir = $this->projectDir . '/public';

        $io->section('Préparation du dossier dist/');
        if ($fs->exists($distDir)) {
            $fs->remove($distDir);
        }
        $fs->mkdir($distDir);

        $io->section('Rendu de la page d\'accueil');
        $request = Request::create('/', 'GET');
        $response = $this->kernel->handle($request);

        if (200 !== $response->getStatusCode()) {
            $io->error(sprintf('Le rendu a renvoyé un statut HTTP %d.', $response->getStatusCode()));

            return Command::FAILURE;
        }

        $fs->dumpFile($distDir . '/index.html', (string)$response->getContent());
        $io->success(sprintf('dist/index.html écrit (%d octets).', \strlen((string)$response->getContent())));

        $io->section('Copie des fichiers public/');
        $finder = (new Finder())
            ->in($publicDir)
            ->depth('== 0')
            ->notName('index.php')
            ->notName('.htaccess')
            ->ignoreDotFiles(false);

        foreach ($finder as $item) {
            $target = $distDir . '/' . $item->getFilename();
            if ($item->isDir()) {
                $fs->mirror($item->getPathname(), $target);
                $io->writeln('  ↳ dossier ' . $item->getFilename() . '/');
            } else {
                $fs->copy($item->getPathname(), $target, true);
                $io->writeln('  ↳ fichier ' . $item->getFilename());
            }
        }

        $io->success('Build statique terminé dans ' . $distDir);

        return Command::SUCCESS;
    }
}
