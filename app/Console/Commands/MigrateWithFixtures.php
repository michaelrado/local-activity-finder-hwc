<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MigrateWithFixtures extends Command {
    protected $signature = 'migrate:with-fixtures 
        {--clean : Remove destination before copying}';

    protected $description = 'Copy tests/Fixtures → storage/app/private/fixtures (for Mock Mode).';

    public function handle(): int {
        $src = base_path('tests/Fixtures');
        $dst = storage_path('app/private/fixtures');

        if (! File::exists($src)) {
            $this->error("Source not found: {$src}");

            return self::FAILURE;
        }

        // Ensure parent exists
        if (! File::exists(dirname($dst))) {
            File::makeDirectory(dirname($dst), 0755, true);
        }

        if ($this->option('clean') && File::exists($dst)) {
            File::deleteDirectory($dst);
        }

        // Create destination and copy
        File::ensureDirectoryExists($dst, 0755);
        if (! File::copyDirectory($src, $dst)) {
            $this->error("Copy failed: {$src} → {$dst}");

            return self::FAILURE;
        }

        // Nice: add a .gitignore so storage stays untracked
        $gitignore = $dst . DIRECTORY_SEPARATOR . '.gitignore';
        if (! File::exists($gitignore)) {
            File::put($gitignore, "*\n!.gitignore\n");
        }

        $this->info("Fixtures synced: {$src} → {$dst}");

        return self::SUCCESS;
    }
}
