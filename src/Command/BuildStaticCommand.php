<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
    /**
     * Pages à pré-rendre : chemin de requête => fichier cible (relatif à dist/).
     */
    private const PAGES = [
        '/' => 'index.html',
        '/en/' => 'en/index.html',
        '/card' => 'card',
        '/card.vcf' => 'card.vcf',
    ];

    public function __construct(
        private readonly HttpKernelInterface $kernel,
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string              $projectDir,
        #[Autowire('%env(DEFAULT_URI)%')]
        private readonly string              $baseUri,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'output',
            null,
            InputOption::VALUE_REQUIRED,
            'Dossier de sortie, relatif à la racine du projet. Utiliser "public" pour un déploiement FrankenPHP (statique + /mcp).',
            'dist',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $fs = new Filesystem();

        $outputName = trim((string) $input->getOption('output'), '/');
        $distDir = $this->projectDir . '/' . $outputName;
        $publicDir = $this->projectDir . '/public';

        // When rendering straight into public/ (FrankenPHP deployment), the
        // directory already holds index.php and the compiled assets, so we must
        // NOT wipe it — we only refresh the generated pages and skip the mirror.
        $intoPublic = $distDir === $publicDir;

        $io->section(sprintf('Préparation du dossier %s/', $outputName));
        if ($intoPublic) {
            foreach (self::PAGES as $target) {
                $fs->remove($distDir . '/' . $target);
            }
        } elseif ($fs->exists($distDir)) {
            $fs->remove($distDir);
        }
        $fs->mkdir($distDir);

        $base = rtrim($this->baseUri, '/');
        foreach (self::PAGES as $path => $target) {
            $io->section(sprintf('Rendu de %s', $path));
            $request = Request::create($base . $path, 'GET');
            $response = $this->kernel->handle($request);

            if (200 !== $response->getStatusCode()) {
                $io->error(sprintf('Le rendu de %s a renvoyé un statut HTTP %d.', $path, $response->getStatusCode()));

                return Command::FAILURE;
            }

            $content = (string) $response->getContent();
            $fs->dumpFile($distDir . '/' . $target, $content);
            $io->success(sprintf('dist/%s écrit (%d octets).', $target, \strlen($content)));
        }

        if ($intoPublic) {
            $io->success('Build statique terminé dans ' . $distDir);

            return Command::SUCCESS;
        }

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
