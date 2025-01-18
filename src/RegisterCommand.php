<?php

declare(strict_types=1);

namespace BeastBytes\Router\Register;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Files\FileHelper;
use Yiisoft\Files\PathMatcher\PathMatcher;

#[AsCommand(name: 'router:register', description: 'Register routes defined in attributes')]
final class RegisterCommand extends Command
{
    /** @var list<string> */
    private array $except = ['./config/**', './resources/**', './tests/**', './vendor/**'];
    /** @var list<string> */
    private array $only = ['**Controller.php'];

    public function __construct(
        private readonly Generator $generator,
        private readonly Writer $writer
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'except',
                'E',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                'Exclude path from source files.',
                []
            )
            ->addOption(
                'only',
                'O',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                'Use the Only specified pattern for matching source files.',
                []
            )
            ->addOption(
                'write',
                'W',
                InputOption::VALUE_OPTIONAL,
                'Path to Write route files to.',
                '.' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'router'
            )
            ->addArgument(
                'src',
                InputArgument::OPTIONAL,
                'Path for source files.'
            )
            ->setHelp('Registers routes specified by attributes in files within a given path.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string $src */
        $src = $input->getArgument('src') ?? getcwd();
        /** @var string[] $except */
        $except = $input->getOption('except');
        /** @var string[] $only */
        $only = $input->getOption('only');
        /** @var string $write */
        $writePath = $input->getOption('write');

        if (!empty($except)) {
            $this->except = $except;
        }

        if (!empty($only)) {
            $this->only = $only;
        }

        $files = FileHelper::findFiles($src, [
            'filter' => (new PathMatcher())
                ->only(...$this->only)
                ->except(...$this->except),
            'recursive' => true,
        ]);

        if (!is_dir($writePath)) {
            mkdir($writePath);
            mkdir($writePath . DIRECTORY_SEPARATOR . 'routes');
        }

        $groups = [];

        foreach ($files as $file) {
            [$name, $group, $routes] = $this->generator->processFile($file);

            if (!array_key_exists($name, $groups)) {
                $groups[$name]['group'] = $group;
                $groups[$name]['routes'] = $routes;
            } else {
                array_push($groups[$name]['routes'], ...$routes);
            }
        }

        $result = $this->writer->write($writePath, $groups);

        if (strlen($result) > 0) {
            $io->error($result);
            return Command::FAILURE;
        } else {
            $io->success('Routes successfully registered');
            return Command::SUCCESS;
        }
    }
}