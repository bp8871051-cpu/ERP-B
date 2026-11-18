<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PurgeSoftDeletedRecordsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'erp:purge-soft-deleted {--days=30 : Days retention threshold before permanent deletion} {--dry-run : Only list what would be purged}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Permanently purge soft-deleted records across all models that were deleted more than 30 days ago';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $isDryRun = (bool) $this->option('dry-run');
        $cutoffDate = Carbon::now()->subDays($days);

        $this->info("Purging soft-deleted records deleted before: {$cutoffDate->toDateTimeString()} ({$days} days retention threshold)");

        $modelsPath = app_path('Models');
        $files = File::allFiles($modelsPath);

        $totalPurged = 0;

        foreach ($files as $file) {
            $class = 'App\\Models\\' . $file->getFilenameWithoutExtension();
            if (!class_exists($class)) {
                continue;
            }

            // Check if class uses SoftDeletes trait
            $traits = class_uses_recursive($class);
            if (!in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, $traits)) {
                continue;
            }

            try {
                $query = $class::onlyTrashed()->where('deleted_at', '<=', $cutoffDate);
                $count = $query->count();

                if ($count > 0) {
                    $this->line("Found {$count} record(s) in <comment>{$class}</comment> to purge.");

                    if (!$isDryRun) {
                        $deleted = $query->forceDelete();
                        $this->info(" -> Successfully permanently purged {$deleted} record(s) from {$class}.");
                        $totalPurged += $count;
                    }
                }
            } catch (\Throwable $e) {
                $this->warn("Skipped {$class}: " . $e->getMessage());
            }
        }

        $this->info("Finished. Total permanently purged records: {$totalPurged}");
        return 0;
    }
}

