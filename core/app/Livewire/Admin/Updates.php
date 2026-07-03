<?php

namespace XBB\Livewire\Admin;

use XBB\Actions\Admin\CheckForUpdate;
use XBB\Support\Updater;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Process;
use Livewire\Component;
use Mary\Traits\Toast;

use function Illuminate\Support\php_binary;

class Updates extends Component
{
    use Toast;

    public bool $supported = false;

    public string $current = '';

    public ?string $latest = null;

    public bool $updateAvailable = false;

    public string $state = 'idle';

    public string $logTail = '';

    public function mount(CheckForUpdate $check, Updater $updater): void
    {
        abort_unless(Gate::allows('administrate'), 403);

        $this->supported = $updater->isSupported();

        $this->applyCheck($check());
        $this->refreshStatus($updater);
    }

    public function checkNow(CheckForUpdate $check): void
    {
        $this->applyCheck($check(force: true));

        if ($this->latest === null) {
            $this->warning(__('Could not reach Packagist to check for updates.'));

            return;
        }

        $this->updateAvailable
            ? $this->success(__('A new version is available: :version', ['version' => $this->latest]))
            : $this->success(__('You are running the latest version.'));
    }

    public function startUpgrade(Updater $updater): void
    {
        abort_unless(Gate::allows('administrate'), 403);

        if (! $this->supported || ! $this->updateAvailable || $this->latest === null) {
            return;
        }

        if ($updater->isRunning()) {
            $this->warning(__('An upgrade is already in progress.'));

            return;
        }

        $appRoot = $updater->appRoot();

        $updater->resetLog();
        $updater->markRunning($this->latest);
        $this->state = 'running';

        // Detach the upgrade so it survives the end of this request; nohup + "&"
        // returns immediately while Composer keeps running in the background.
        $command = sprintf(
            'nohup %s %s xbackbone:upgrade --to=%s >> %s 2>&1 &',
            escapeshellarg(php_binary()),
            escapeshellarg($appRoot.'/xbb'),
            escapeshellarg($this->latest),
            escapeshellarg($updater->logPath()),
        );

        Process::path($appRoot)->run($command);

        $this->info(__('Upgrade started. This may take a few minutes.'));
    }

    public function pollStatus(Updater $updater): void
    {
        $this->refreshStatus($updater);
    }

    /**
     * @param  array{current: string, latest: ?string, updateAvailable: bool}  $result
     */
    private function applyCheck(array $result): void
    {
        $this->current = $result['current'];
        $this->latest = $result['latest'];
        $this->updateAvailable = $result['updateAvailable'];
    }

    private function refreshStatus(Updater $updater): void
    {
        $status = $updater->status();
        $previous = $this->state;

        $this->state = $status['state'];
        $this->logTail = $updater->tailLog();

        if ($previous !== 'running') {
            return;
        }

        if ($this->state === 'done') {
            $this->current = $status['target'] ?? $this->current;
            $this->updateAvailable = false;
            $this->success($status['message'] ?? __('Upgrade complete.'));
        }

        if ($this->state === 'failed') {
            $this->error(__('Upgrade failed. Check the log below.'));
        }
    }

    public function render(): object
    {
        return view('livewire.admin.updates');
    }
}
