<?php

namespace Chrickell\Laraprints\Console\Commands;

use Chrickell\Laraprints\Models\Click;
use Chrickell\Laraprints\Models\PageView;
use Illuminate\Console\Command;

class AnonymizeData extends Command
{
    protected $signature = 'laraprints:anonymize
                            {--days=30 : Anonymize records older than this many days}
                            {--force   : Skip confirmation prompt}';

    protected $description = 'Null out IP addresses and user agents on records older than N days (GDPR-friendly)';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        if ($days < 1) {
            $this->components->error('--days must be a positive integer.');
            return self::FAILURE;
        }

        $cutoff = now()->subDays($days)->startOfDay();

        $pvCount    = PageView::where('created_at', '<', $cutoff)
            ->where(fn ($q) => $q->whereNotNull('ip_address')->orWhereNotNull('user_agent'))
            ->count();

        $clickCount = Click::where('created_at', '<', $cutoff)->count();

        if ($pvCount === 0 && $clickCount === 0) {
            $this->components->info('No records need anonymizing.');
            return self::SUCCESS;
        }

        $this->components->twoColumnDetail('Page views to anonymize', (string) $pvCount);
        $this->components->twoColumnDetail('Clicks to anonymize', (string) $clickCount);
        $this->components->twoColumnDetail('Cutoff date', $cutoff->toDateString());

        if (! $this->option('force') && ! $this->confirm('Proceed? This cannot be undone.', false)) {
            $this->components->warn('Aborted.');
            return self::SUCCESS;
        }

        $this->newLine();

        if ($pvCount > 0) {
            PageView::where('created_at', '<', $cutoff)
                ->where(fn ($q) => $q->whereNotNull('ip_address')->orWhereNotNull('user_agent'))
                ->chunkById(500, function ($chunk) {
                    foreach ($chunk as $pv) {
                        $pv->update(['ip_address' => null, 'user_agent' => null]);
                    }
                });

            $this->components->twoColumnDetail('Page views', '<fg=green;options=bold>ANONYMIZED</>');
        }

        if ($clickCount > 0) {
            // Clicks table stores session/visit/element data but no IP/UA — nothing to null.
            // This is a no-op for now but kept for future fields.
            $this->components->twoColumnDetail('Clicks', '<fg=green;options=bold>OK (no PII fields)</>');
        }

        $this->newLine();
        $this->components->info('Anonymization complete.');

        return self::SUCCESS;
    }
}
