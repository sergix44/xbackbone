<?php

namespace App\Installer\Livewire;

use App\Installer\Actions\CountLegacyRecords;
use App\Installer\Actions\FinalizeInstallation;
use App\Installer\Actions\TestDatabaseConnection;
use App\Installer\Actions\TestStorageDisk;
use App\Installer\Exceptions\InstallationException;
use App\Installer\Support\DatabaseDriver;
use App\Installer\Support\StorageDriver;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Mary\Traits\Toast;

class Installer extends Component
{
    use Toast;

    public int $step = 0;

    public bool $dbConnectionVerified = false;

    public bool $storageVerified = false;

    /* Step 0: application */
    public string $appUrl = '';

    /* Step 1: database */
    public string $dbDriver = 'sqlite';

    public string $dbSqlitePath = '';

    public string $dbHost = '127.0.0.1';

    public ?int $dbPort = 3306;

    public string $dbDatabase = '';

    public string $dbUsername = '';

    public string $dbPassword = '';

    /* Step 2: storage */
    public string $storageDriver = 'local';

    public string $localRoot = '';

    public string $s3Key = '';

    public string $s3Secret = '';

    public string $s3Region = '';

    public string $s3Bucket = '';

    public string $s3Endpoint = '';

    public bool $s3PathStyle = false;

    public string $ftpHost = '';

    public ?int $ftpPort = null;

    public string $ftpUsername = '';

    public string $ftpPassword = '';

    public string $ftpRoot = '';

    /* Step 3: admin */
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    /* Step 4: legacy import */
    public bool $importLegacy = false;

    public string $legacyDriver = 'mysql';

    public string $legacyDbHost = '127.0.0.1';

    public ?int $legacyDbPort = 3306;

    public string $legacyDbDatabase = '';

    public string $legacyDbUsername = '';

    public string $legacyDbPassword = '';

    public string $legacyDbFile = '';

    public string $legacyStoragePath = '';

    public string $legacyOrphans = 'skip';

    public bool $legacyWithPreviews = false;

    /** @var array{users: int, uploads: int}|null */
    public ?array $legacyPreview = null;

    public function mount(): void
    {
        $this->appUrl = request()->getSchemeAndHttpHost() ?: (string) config('app.url');
        $this->dbSqlitePath = rtrim((string) config('app.root', base_path()), '/').'/xbb.db';
        $this->localRoot = storage_path('app');
    }

    public function updated(string $property): void
    {
        // Editing any database field invalidates a previous successful test
        // (but not the flag itself).
        if (str_starts_with($property, 'db') && $property !== 'dbConnectionVerified') {
            $this->dbConnectionVerified = false;
        }

        if ($property === 'dbDriver') {
            $this->dbPort = DatabaseDriver::from($this->dbDriver)->defaultPort();
            $this->resetValidation();
        }

        if (in_array($property, ['storageDriver', 'localRoot', 'ftpRoot'], true) || str_starts_with($property, 's3') || str_starts_with($property, 'ftp')) {
            $this->storageVerified = false;
        }

        if (in_array($property, ['storageDriver', 'legacyDriver'], true)) {
            $this->resetValidation();
        }
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    #[Computed]
    public function databaseDrivers(): array
    {
        return DatabaseDriver::options();
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    #[Computed]
    public function storageDrivers(): array
    {
        return StorageDriver::options();
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    #[Computed]
    public function legacyDrivers(): array
    {
        return [
            ['id' => 'mysql', 'name' => 'MySQL / MariaDB'],
            ['id' => 'sqlite', 'name' => 'SQLite'],
        ];
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    #[Computed]
    public function orphanStrategies(): array
    {
        return [
            ['id' => 'skip', 'name' => __('Skip uploads without an owner')],
            ['id' => 'admin', 'name' => __('Assign them to this admin')],
        ];
    }

    /**
     * The read-only environment requirements checklist for step 0.
     *
     * @return list<array{label: string, ok: bool}>
     */
    #[Computed]
    public function requirements(): array
    {
        return [
            ['label' => 'PHP >= 8.4', 'ok' => version_compare(PHP_VERSION, '8.4.0', '>=')],
            ['label' => 'PDO extension', 'ok' => extension_loaded('pdo')],
            ['label' => 'OpenSSL extension', 'ok' => extension_loaded('openssl')],
            ['label' => 'Mbstring extension', 'ok' => extension_loaded('mbstring')],
            ['label' => 'GD or Imagick extension', 'ok' => extension_loaded('gd') || extension_loaded('imagick')],
            ['label' => 'Storage directory writable', 'ok' => is_writable(storage_path())],
            ['label' => 'Application key generated', 'ok' => (string) config('app.key') !== ''],
        ];
    }

    public function nextStep(): void
    {
        $this->validateStep($this->step);

        if ($this->step === 1 && ! $this->dbConnectionVerified) {
            $this->error(__('Please test the database connection before continuing.'));

            return;
        }

        $this->step = min($this->step + 1, 4);
    }

    public function previousStep(): void
    {
        $this->resetValidation();
        $this->step = max($this->step - 1, 0);
    }

    public function testDatabase(TestDatabaseConnection $test): void
    {
        $this->validateStep(1);

        $result = $test($this->databasePayload());

        if ($result['ok']) {
            $this->dbConnectionVerified = true;
            $this->success($result['message']);

            return;
        }

        $this->dbConnectionVerified = false;
        $this->error($result['message']);
    }

    public function testStorage(TestStorageDisk $test): void
    {
        $this->validateStep(2);

        $result = $test($this->storagePayload());

        if ($result['ok']) {
            $this->storageVerified = true;
            $this->success($result['message']);

            return;
        }

        $this->storageVerified = false;
        $this->error($result['message']);
    }

    public function previewLegacy(CountLegacyRecords $count): void
    {
        $this->validateStep(4);

        $result = $count($this->legacyPayload());

        if ($result['ok']) {
            $this->legacyPreview = ['users' => $result['users'], 'uploads' => $result['uploads']];
            $this->success(__(':users users and :uploads uploads found.', ['users' => $result['users'], 'uploads' => $result['uploads']]));

            return;
        }

        $this->legacyPreview = null;
        $this->error($result['message']);
    }

    public function install(FinalizeInstallation $finalize)
    {
        foreach ([0, 1, 2, 3, 4] as $step) {
            $this->validateStep($step);
        }

        if (! $this->dbConnectionVerified) {
            $this->step = 1;
            $this->error(__('Please re-test the database connection.'));

            return null;
        }

        try {
            $finalize($this->payload());
        } catch (InstallationException $e) {
            if ($e->step !== null) {
                $this->step = $e->step;
            }

            $this->error($e->getMessage());

            return null;
        }

        return redirect()->route('login');
    }

    public function render(): object
    {
        return view('installer::wizard')
            ->layout('installer::layout')
            ->title(__('Install XBackBone'));
    }

    /**
     * Validate a single step, skipping steps that have no rules (e.g. the
     * legacy import step when import is disabled) so Livewire does not raise a
     * MissingRulesException on an empty rule set.
     */
    private function validateStep(int $step): void
    {
        $rules = $this->rulesForStep($step);

        if ($rules !== []) {
            $this->validate($rules);
        }
    }

    /**
     * Validation rules for a single step, so navigation validates only the
     * fields the user is currently editing.
     *
     * @return array<string, mixed>
     */
    private function rulesForStep(int $step): array
    {
        return match ($step) {
            0 => [
                'appUrl' => ['required', 'url'],
            ],
            1 => $this->dbDriver === 'sqlite'
                ? [
                    'dbDriver' => ['required', Rule::in(['sqlite', 'mysql', 'mariadb', 'pgsql'])],
                    'dbSqlitePath' => ['required', 'string'],
                ]
                : [
                    'dbDriver' => ['required', Rule::in(['sqlite', 'mysql', 'mariadb', 'pgsql'])],
                    'dbHost' => ['required', 'string'],
                    'dbPort' => ['required', 'integer', 'between:1,65535'],
                    'dbDatabase' => ['required', 'string'],
                    'dbUsername' => ['required', 'string'],
                    'dbPassword' => ['nullable', 'string'],
                ],
            2 => match ($this->storageDriver) {
                's3' => [
                    'storageDriver' => ['required', Rule::in(['local', 's3', 'ftp', 'sftp'])],
                    's3Key' => ['required', 'string'],
                    's3Secret' => ['required', 'string'],
                    's3Region' => ['required', 'string'],
                    's3Bucket' => ['required', 'string'],
                    's3Endpoint' => ['nullable', 'string'],
                    's3PathStyle' => ['boolean'],
                ],
                'ftp', 'sftp' => [
                    'storageDriver' => ['required', Rule::in(['local', 's3', 'ftp', 'sftp'])],
                    'ftpHost' => ['required', 'string'],
                    'ftpPort' => ['required', 'integer', 'between:1,65535'],
                    'ftpUsername' => ['required', 'string'],
                    'ftpPassword' => ['nullable', 'string'],
                    'ftpRoot' => ['nullable', 'string'],
                ],
                default => [
                    'storageDriver' => ['required', Rule::in(['local', 's3', 'ftp', 'sftp'])],
                    'localRoot' => ['required', 'string'],
                ],
            },
            3 => [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255'],
                'password' => ['required', 'string', 'confirmed', Password::default()],
            ],
            4 => $this->importLegacy
                ? [
                    'legacyDriver' => ['required', Rule::in(['mysql', 'sqlite'])],
                    'legacyStoragePath' => ['required', 'string'],
                    'legacyOrphans' => ['required', Rule::in(['skip', 'admin'])],
                    'legacyWithPreviews' => ['boolean'],
                    ...$this->legacyDriver === 'sqlite'
                        ? ['legacyDbFile' => ['required', 'string']]
                        : [
                            'legacyDbHost' => ['required', 'string'],
                            'legacyDbPort' => ['required', 'integer', 'between:1,65535'],
                            'legacyDbDatabase' => ['required', 'string'],
                            'legacyDbUsername' => ['required', 'string'],
                            'legacyDbPassword' => ['nullable', 'string'],
                        ],
                ]
                : [],
            default => [],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function databasePayload(): array
    {
        return [
            'driver' => $this->dbDriver,
            'sqlitePath' => $this->dbSqlitePath,
            'host' => $this->dbHost,
            'port' => $this->dbPort,
            'database' => $this->dbDatabase,
            'username' => $this->dbUsername,
            'password' => $this->dbPassword,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function storagePayload(): array
    {
        return match ($this->storageDriver) {
            's3' => [
                'driver' => 's3',
                'key' => $this->s3Key,
                'secret' => $this->s3Secret,
                'region' => $this->s3Region,
                'bucket' => $this->s3Bucket,
                'endpoint' => $this->s3Endpoint,
                'usePathStyle' => $this->s3PathStyle,
            ],
            'ftp', 'sftp' => [
                'driver' => $this->storageDriver,
                'host' => $this->ftpHost,
                'port' => $this->ftpPort ?? ($this->storageDriver === 'sftp' ? 22 : 21),
                'username' => $this->ftpUsername,
                'password' => $this->ftpPassword,
                'root' => $this->ftpRoot,
            ],
            default => [
                'driver' => 'local',
                'root' => $this->localRoot,
            ],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function legacyPayload(): array
    {
        return [
            'driver' => $this->legacyDriver,
            'host' => $this->legacyDbHost,
            'port' => $this->legacyDbPort,
            'database' => $this->legacyDbDatabase,
            'username' => $this->legacyDbUsername,
            'password' => $this->legacyDbPassword,
            'file' => $this->legacyDbFile,
            'storagePath' => $this->legacyStoragePath,
            'orphans' => $this->legacyOrphans,
            'withPreviews' => $this->legacyWithPreviews,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'appUrl' => $this->appUrl,
            'database' => $this->databasePayload(),
            'storage' => $this->storagePayload(),
            'admin' => [
                'name' => $this->name,
                'email' => $this->email,
                'password' => $this->password,
            ],
            'import' => $this->importLegacy ? $this->legacyPayload() : null,
        ];
    }
}
