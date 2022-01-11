<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command sample to create a new app skeleton to your current project.
 */
class NewAppContextCommand extends Command
{
    protected static $defaultName = 'new-context';
    protected static $defaultDescription = 'Create a new app context skeleton';

    public function configure(): void
    {
        $this->getDefinition()
            ->addArgument(new InputArgument('context', InputArgument::REQUIRED, 'The application context'))
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Application Skeleton');

        $projectDir = \dirname(__DIR__, 3);
        $context = ucfirst($input->getArgument('context'));

        // config/<context>/bundles.php
        $contextLower = strtolower($context);
        if (!mkdir($appConfigDir = $projectDir.'/config/'.$contextLower) && !is_dir($appConfigDir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $appConfigDir));
        }
        file_put_contents($appConfigDir.'/bundles.php', "<?php\n\nreturn [];");
        $io->writeln(sprintf('Created <fg=green>"%s"</>', 'config/'.$contextLower.'/bundles.php'));

        // src/<Name>/Controller/.gitignore
        if (!mkdir($appSrcDir = $projectDir.'/src/'.$context.'/Controller', 0777, true) && !is_dir($appSrcDir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $appSrcDir));
        }
        file_put_contents($appSrcDir.'/.gitignore', '');
        $io->writeln(sprintf('Created <fg=green>"%s"</>', 'src/'.$context.'/Controller/.gitignore'));

        // tests/<Name>/<Name>WebTestCase.php
        if (!mkdir($appTestDir = $projectDir.'/tests/'.$context) && !is_dir($appTestDir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $appTestDir));
        }
        file_put_contents($appTestDir.'/'.$context.'WebTestCase.php', <<<PHP
<?php

namespace {$context}\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class {$context}WebTestCase extends WebTestCase
{
    protected static function createKernel(array \$options = array())
    {
        return new \Kernel(
            \$options['environment'] ?? 'test',
            \$options['debug'] ?? true,
            '{$contextLower}'
        );
    }
}

PHP
        );
        $io->writeln(sprintf('Created <fg=green>"%s"</>', 'tests/'.$context.'/'.$context.'WebTestCase.php'));

        if (is_file($projectDir.'/composer.json') && is_readable($projectDir.'/composer.json')) {
            $composerJson = json_decode(file_get_contents($projectDir.'/composer.json'), true);
            $composerJson['autoload']['psr-4'][$context.'\\'] = 'src/'.$context.'/';
            $composerJson['autoload-dev']['psr-4'][$context.'\\Tests\\'] = 'tests/'.$context.'/';
            file_put_contents($projectDir.'/composer.json', json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
        }
        $io->writeln('Updated autoload PSR-4 config in <fg=green>"composer.json"</>');
        $io->comment('You need to update the autoloader file: <comment>composer dump-autoload</>');

        $io->success(sprintf('The new application "%s" was successfully created.', $context));
        $io->comment(sprintf('Try it out: <comment>bin/console about --kernel=%s</>.', $contextLower));

        return 0;
    }
}
