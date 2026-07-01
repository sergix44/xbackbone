<?php

namespace App\Actions\Admin;

use App\Support\Updater;
use Illuminate\Support\Facades\Process;
use RuntimeException;

use function Illuminate\Support\php_binary;

class UpgradeApplication
{
    public function __construct(private readonly Updater $updater) {}

    /**
     * Upgrade the core package to the given version, then run post-upgrade
     * maintenance. Designed to run inside the detached upgrade process, where
     * blocking on Composer is fine because it is not the web request.
     *
     * @param  (callable(string, string):void)|null  $output  Streams Composer/process output; defaults to STDOUT.
     */
    public function __invoke(string $version, ?callable $output = null): void
    {
        abort_unless($this->updater->isSupported(), 403);

        $output ??= static function (string $type, string $buffer): void {
            fwrite(STDOUT, $buffer);
        };

        $appRoot = $this->updater->appRoot();
        $package = config('updater.package');

        $output('out', "Requiring {$package}:{$version}...\n");

        $succeeded = app('composer')
            ->setWorkingPath($appRoot)
            ->requirePackages(
                ["{$package}:{$version}"],
                false,
                $output,
                config('updater.composer_binary'),
            );

        if (! $succeeded) {
            throw new RuntimeException("Composer failed to install {$package}:{$version}.");
        }

        $this->runPostUpgradeSteps($appRoot, $output);
    }

    /**
     * Run maintenance steps as fresh subprocesses so they execute the newly
     * installed code rather than the classes already loaded in this process.
     *
     * @param  callable(string, string):void  $output
     */
    private function runPostUpgradeSteps(string $appRoot, callable $output): void
    {
        $steps = [
            ['migrate', '--force'],
            ['optimize:clear'],
        ];

        foreach ($steps as $arguments) {
            $output('out', "\nRunning xbb ".implode(' ', $arguments)."...\n");

            Process::path($appRoot)
                ->timeout(300)
                ->run([php_binary(), $appRoot.'/xbb', ...$arguments], $output);
        }
    }
}
