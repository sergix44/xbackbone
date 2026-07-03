<?php

namespace XBB\Console\Commands;

use Illuminate\Console\Command;

class UpdateThemesCommand extends Command
{
    protected $signature = 'update:themes';

    protected $description = 'Extract themes from node_modules/daisyui/theme and update themes.php config';

    public function handle(): int
    {
        if (! is_dir(base_path('node_modules/daisyui/theme'))) {
            $this->error('node_modules/daisyui/theme dir not found');

            return 1;
        }

        $themes = collect(glob(base_path('node_modules/daisyui/theme/*.css')))
            ->map(fn ($file) => str_replace(base_path('node_modules/daisyui/theme/'), '', $file))
            ->map(fn ($file) => basename($file, '.css'))
            ->toArray();

        if (config('themes') === $themes) {
            $this->info('Themes already up to date.');

            return 0;
        }

        $configContent = sprintf("<?php\nreturn %s;", var_export($themes, true));
        file_put_contents(config_path('themes.php'), $configContent);

        $this->info('Themes updated successfully.');

        return 0;
    }
}
