<?php

namespace XBB\Console\Commands;

use XBB\Actions\Admin\CheckForUpdate;
use XBB\Actions\Admin\UpgradeApplication;
use XBB\Support\Updater;
use Illuminate\Console\Command;
use Throwable;

class UpgradeCommand extends Command
{
    protected $signature = 'xbackbone:upgrade {--to= : Target version (defaults to the latest stable release)}';

    protected $description = 'Upgrade the XBackBone core package to the latest (or a specific) stable release';

    public function handle(Updater $updater, CheckForUpdate $check, UpgradeApplication $upgrade): int
    {
        if (! $updater->isSupported()) {
            $this->error('Self-upgrade is only available on production installations.');

            return self::FAILURE;
        }

        $version = $this->option('to') ?: $check(force: true)['latest'];

        if (! $version) {
            $this->error('Unable to determine a target version.');

            return self::FAILURE;
        }

        $updater->markRunning($version);

        $output = fn (string $type, string $buffer) => $this->output->write($buffer);

        try {
            $upgrade($version, $output);
        } catch (Throwable $e) {
            $updater->markFailed($e->getMessage());
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $updater->markDone($version);
        $this->info("Upgraded to {$version}.");

        return self::SUCCESS;
    }
}
