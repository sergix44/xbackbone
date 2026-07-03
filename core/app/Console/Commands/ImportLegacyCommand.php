<?php

namespace XBB\Console\Commands;

use XBB\Actions\Import\ImportLegacyResource;
use XBB\Actions\Import\ImportLegacyUser;
use XBB\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class ImportLegacyCommand extends Command
{
    protected $signature = 'xbackbone:import
        {--driver=mysql : Legacy database driver: mysql or sqlite}
        {--db-host=127.0.0.1 : Legacy MySQL host}
        {--db-port=3306 : Legacy MySQL port}
        {--db-database= : Legacy MySQL schema name}
        {--db-username= : Legacy MySQL username}
        {--db-password= : Legacy MySQL password}
        {--db-file= : Path to the legacy SQLite database file}
        {--storage-path= : Absolute path to the legacy storage root}
        {--with-previews : Queue preview generation jobs for imported media (requires a worker)}
        {--on-missing-file=skip : When a referenced file is missing on disk: skip or fail}
        {--orphans=skip : Uploads without an owner (user_id NULL): skip or admin}
        {--admin-id= : Target user id for orphaned uploads when --orphans=admin}
        {--chunk=500 : Rows fetched per chunk from the legacy database}
        {--dry-run : Report what would happen without writing anything}';

    protected $description = 'Import users and media from a legacy XBackBone instance';

    public function handle(ImportLegacyUser $importUser, ImportLegacyResource $importResource): int
    {
        $error = $this->validateOptions();

        if ($error !== null) {
            $this->error($error);

            return self::FAILURE;
        }

        try {
            $this->configureLegacyConnection();
            $legacy = DB::connection('legacy');
            $legacy->getPdo();
        } catch (Throwable $e) {
            $this->error('Could not connect to the legacy database: '.$e->getMessage());

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Dry-run mode: no data will be written and no files copied.');
        }

        try {
            $userMap = $this->importUsers($legacy, $importUser, $dryRun);
            // Resolved after users so a just-imported admin can receive orphaned uploads.
            $orphanOwnerId = $this->resolveOrphanOwnerId($dryRun);
            $this->importResources($legacy, $importResource, $userMap, $orphanOwnerId, $dryRun);
        } catch (Throwable $e) {
            $this->error('Import aborted: '.$e->getMessage());

            return self::FAILURE;
        } finally {
            DB::purge('legacy');
        }

        $this->newLine();

        if ($dryRun) {
            $this->info('Dry-run complete. Re-run without --dry-run to apply.');

            return self::SUCCESS;
        }

        $this->info('Legacy import complete.');
        $this->line('Imported passwords are bcrypt: set HASH_VERIFY=false so migrated users can sign in; '
            .'Laravel rehashes each password to the current algorithm on first login.');
        $this->line('Not migrated: legacy API tokens, tags and per-user preferences.');

        return self::SUCCESS;
    }

    private function validateOptions(): ?string
    {
        $driver = (string) $this->option('driver');

        if (! in_array($driver, ['mysql', 'sqlite'], true)) {
            return "Invalid --driver '{$driver}'. Use 'mysql' or 'sqlite'.";
        }

        if (! in_array((string) $this->option('on-missing-file'), ['skip', 'fail'], true)) {
            return "Invalid --on-missing-file. Use 'skip' or 'fail'.";
        }

        if (! in_array((string) $this->option('orphans'), ['skip', 'admin'], true)) {
            return "Invalid --orphans. Use 'skip' or 'admin'.";
        }

        $storagePath = (string) $this->option('storage-path');

        if ($storagePath === '' || ! is_dir($storagePath)) {
            return 'The --storage-path option must point to an existing directory.';
        }

        if ($driver === 'sqlite') {
            $file = (string) $this->option('db-file');

            if ($file === '' || ! is_file($file)) {
                return "For the 'sqlite' driver, --db-file must point to an existing file.";
            }
        } elseif ((string) $this->option('db-database') === '' || $this->option('db-username') === null) {
            return "For the 'mysql' driver, --db-database and --db-username are required.";
        }

        return null;
    }

    private function configureLegacyConnection(): void
    {
        $config = (string) $this->option('driver') === 'sqlite'
            ? [
                'driver' => 'sqlite',
                'database' => (string) $this->option('db-file'),
                'prefix' => '',
                'foreign_key_constraints' => false,
            ]
            : [
                'driver' => 'mysql',
                'host' => (string) $this->option('db-host'),
                'port' => (string) $this->option('db-port'),
                'database' => (string) $this->option('db-database'),
                'username' => (string) $this->option('db-username'),
                'password' => (string) $this->option('db-password'),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => false,
            ];

        config(['database.connections.legacy' => $config]);
        DB::purge('legacy');
    }

    private function resolveOrphanOwnerId(bool $dryRun): ?int
    {
        if ((string) $this->option('orphans') !== 'admin') {
            return null;
        }

        $adminId = $this->option('admin-id');

        if ($adminId !== null) {
            $user = User::query()->find($adminId);

            if ($user === null) {
                throw new RuntimeException("No user found with id {$adminId} for --admin-id.");
            }

            return (int) $user->id;
        }

        $admin = User::query()->where('is_admin', true)->orderBy('id')->first();

        if ($admin === null) {
            // During a dry-run the imported admins were not persisted, so fall back to
            // treating orphans as skipped rather than aborting the preview.
            if ($dryRun) {
                return null;
            }

            throw new RuntimeException('No admin user found for orphaned uploads. Provide --admin-id or import an admin first.');
        }

        return (int) $admin->id;
    }

    /**
     * @return array<int, int> Map of legacy user id to current-application user id.
     */
    private function importUsers(Connection $legacy, ImportLegacyUser $importUser, bool $dryRun): array
    {
        $total = $legacy->table('users')->count();
        $this->info("Importing {$total} users...");

        $bar = $this->output->createProgressBar($total);
        $map = [];
        $created = 0;
        $skipped = 0;

        $legacy->table('users')->orderBy('id')->chunk(
            (int) $this->option('chunk'),
            function ($rows) use ($importUser, $dryRun, &$map, &$created, &$skipped, $bar): void {
                foreach ($rows as $row) {
                    $result = $importUser($row, $dryRun);

                    if ($result['user'] !== null) {
                        $map[(int) $row->id] = (int) $result['user']->id;
                    } elseif ($result['action'] === 'would-create') {
                        // No real id during a dry-run; mark as resolvable so the
                        // user's uploads are not later counted as orphans.
                        $map[(int) $row->id] = 0;
                    }

                    $result['action'] === 'skipped' ? $skipped++ : $created++;
                    $bar->advance();
                }
            }
        );

        $bar->finish();
        $this->newLine();
        $this->line("  Users: {$created} ".($dryRun ? 'would be created' : 'created').", {$skipped} skipped (already present).");

        return $map;
    }

    /**
     * @param  array<int, int>  $userMap
     */
    private function importResources(
        Connection $legacy,
        ImportLegacyResource $importResource,
        array $userMap,
        ?int $orphanOwnerId,
        bool $dryRun
    ): void {
        $storageRoot = (string) $this->option('storage-path');
        $onMissingFile = (string) $this->option('on-missing-file');
        $withPreviews = (bool) $this->option('with-previews');

        $total = $legacy->table('uploads')->count();
        $this->info("Importing {$total} uploads...");

        $bar = $this->output->createProgressBar($total);
        $counts = [
            'created' => 0,
            'would-create' => 0,
            'skipped-duplicate' => 0,
            'skipped-missing' => 0,
            'orphan' => 0,
        ];

        $legacy->table('uploads')->orderBy('id')->chunk(
            (int) $this->option('chunk'),
            function ($rows) use (
                $importResource, $userMap, $orphanOwnerId, $storageRoot, $onMissingFile, $withPreviews, $dryRun, &$counts, $bar
            ): void {
                foreach ($rows as $row) {
                    $ownerId = $this->resolveOwner($row, $userMap, $orphanOwnerId);

                    if ($ownerId === null) {
                        $counts['orphan']++;
                        $bar->advance();

                        continue;
                    }

                    $result = $importResource($row, $ownerId, $storageRoot, $dryRun, $onMissingFile, $withPreviews);
                    $counts[$result['action']]++;
                    $bar->advance();
                }
            }
        );

        $bar->finish();
        $this->newLine();

        $this->table(['Resources', 'Count'], [
            [$dryRun ? 'Would be created' : 'Created', $dryRun ? $counts['would-create'] : $counts['created']],
            ['Skipped (already imported)', $counts['skipped-duplicate']],
            ['Skipped (file missing)', $counts['skipped-missing']],
            ['Skipped (orphaned)', $counts['orphan']],
        ]);

        if ($withPreviews && ! $dryRun) {
            $this->line('Preview jobs were queued; ensure a queue worker is running to process them.');
        }
    }

    /**
     * @param  array<int, int>  $userMap
     */
    private function resolveOwner(object $row, array $userMap, ?int $orphanOwnerId): ?int
    {
        $legacyUserId = $row->user_id !== null ? (int) $row->user_id : null;

        if ($legacyUserId !== null && array_key_exists($legacyUserId, $userMap)) {
            return $userMap[$legacyUserId];
        }

        return $orphanOwnerId; // null when --orphans=skip
    }
}
