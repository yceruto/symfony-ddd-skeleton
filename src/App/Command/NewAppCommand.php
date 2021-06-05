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
class NewAppCommand extends Command
{
    protected static $defaultName = 'new-app';
    protected static $defaultDescription = 'Create a new app skeleton';

    public function configure(): void
    {
        $this->getDefinition()
            ->addArgument(new InputArgument('name', InputArgument::REQUIRED, 'The application name'))
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Application Skeleton');

        $projectDir = \dirname(__DIR__, 3);
        $name = ucfirst($input->getArgument('name'));

        // config/<name>/bundles.php
        $nameLower = strtolower($name);
        if (!mkdir($appConfigDir = $projectDir.'/config/'.$nameLower) && !is_dir($appConfigDir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $appConfigDir));
        }
        file_put_contents($appConfigDir.'/bundles.php', "<?php\n\nreturn [];");
        $io->writeln(sprintf('Created <fg=green>"%s"</>', 'config/'.$nameLower.'/bundles.php'));

        // src/<Name>/Controller/.gitignore
        if (!mkdir($appSrcDir = $projectDir.'/src/'.$name.'/Controller', 0777, true) && !is_dir($appSrcDir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $appSrcDir));
        }
        file_put_contents($appSrcDir.'/.gitignore', '');
        $io->writeln(sprintf('Created <fg=green>"%s"</>', 'src/'.$name.'/Controller/.gitignore'));

        // tests/<Name>/<Name>WebTestCase.php
        if (!mkdir($appTestDir = $projectDir.'/tests/'.$name) && !is_dir($appTestDir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $appTestDir));
        }
        file_put_contents($appTestDir.'/'.$name.'WebTestCase.php', <<<PHP
<?php

namespace {$name}\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class {$name}WebTestCase extends WebTestCase
{
    protected static function createKernel(array \$options = array())
    {
        return new \Kernel(
            \$options['environment'] ?? 'test',
            \$options['debug'] ?? true,
            '{$nameLower}'
        );
    }
}

PHP
        );
        $io->writeln(sprintf('Created <fg=green>"%s"</>', 'tests/'.$name.'/'.$name.'WebTestCase.php'));

        if (is_file($projectDir.'/composer.json') && is_readable($projectDir.'/composer.json')) {
            $composerJson = json_decode(file_get_contents($projectDir.'/composer.json'), true);
            $composerJson['autoload']['psr-4'][$name.'\\'] = 'src/'.$name.'/';
            $composerJson['autoload-dev']['psr-4'][$name.'\\Tests\\'] = 'tests/'.$name.'/';
            file_put_contents($projectDir.'/composer.json', json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
        }
        $io->writeln('Updated autoload PSR-4 config in <fg=green>"composer.json"</>');
        $io->comment('You need to update the autoloader file: <comment>composer dump-autoload</>');

        $io->success(sprintf('The new application "%s" was successfully created.', $name));
        $io->comment(sprintf('Try it out: <comment>bin/console about --kernel=%s</>.', $nameLower));

        return 0;
    }
}
