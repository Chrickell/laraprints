<?php

namespace Chrickell\Laraprints\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'laraprints:install
                            {--no-migrate : Skip running migrations}
                            {--no-components : Skip publishing Vue components}
                            {--subdomain : Subdomain/read-only install — publishes config, provider, and components but skips migrations}';

    protected $description = 'Install Laraprints: publish assets, config, and run migrations';

    public function handle(): int
    {
        $subdomain = $this->option('subdomain');

        $this->components->info($subdomain ? 'Installing Laraprints (subdomain mode)...' : 'Installing Laraprints...');
        $this->newLine();

        // ── 1. Config ─────────────────────────────────────────────────────────
        $this->publishConfig();

        // ── 2. Authorization provider + email ─────────────────────────────────
        $email = $this->askForEmail();
        $this->publishProvider($email);

        // ── 3. Vue components ─────────────────────────────────────────────────
        if (! $this->option('no-components')) {
            $this->publishComponents();
        }

        // ── 4. Middleware ─────────────────────────────────────────────────────
        if (! $subdomain) {
            $this->offerMiddlewareSetup();
        }

        // ── 5. Migrations ─────────────────────────────────────────────────────
        if (! $subdomain && ! $this->option('no-migrate')) {
            $this->runMigrations();
        } elseif ($subdomain) {
            $this->components->twoColumnDetail('Migrations', '<fg=yellow;options=bold>SKIPPED</> (subdomain mode)');
        }

        // ── 6. Summary ────────────────────────────────────────────────────────
        $this->newLine();
        $this->components->info('Laraprints installed successfully.');
        $this->newLine();

        if ($subdomain) {
            $this->components->bulletList([
                'Set <comment>database.connection</comment> in <comment>config/laraprints.php</comment> to point at your primary app\'s database.',
                'Do <comment>not</comment> run migrations here — the primary application owns the Laraprints tables.',
                'Do <comment>not</comment> apply <comment>track.requests</comment> middleware — tracking is handled by the primary app.',
            ]);
        } else {
            $this->printJsInstructions();
        }

        $this->newLine();

        return self::SUCCESS;
    }

    protected function askForEmail(): ?string
    {
        $email = $this->ask(
            'Email address for dashboard access <fg=gray>(leave blank to configure later)</>',
            null
        );

        return ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) ? $email : null;
    }

    protected function publishProvider(?string $email): void
    {
        $providerPath = app_path('Providers/LaraprintsServiceProvider.php');

        if (file_exists($providerPath)) {
            $this->components->twoColumnDetail(
                'Authorization provider',
                '<fg=yellow;options=bold>SKIPPED</> (already exists)'
            );
            return;
        }

        $this->callSilently('vendor:publish', ['--tag' => 'laraprints-provider']);

        if ($email && file_exists($providerPath)) {
            $content = file_get_contents($providerPath);
            $patched = str_replace(
                "    protected array \$emails = [\n        //\n    ];",
                "    protected array \$emails = [\n        '{$email}',\n    ];",
                $content,
                $count
            );

            if ($count > 0) {
                file_put_contents($providerPath, $patched);
                $this->components->twoColumnDetail('Authorization provider', "<fg=green;options=bold>DONE</> — access granted to {$email}");
            } else {
                $this->components->twoColumnDetail('Authorization provider', '<fg=green;options=bold>DONE</>');
                $this->components->warn("  Could not inject email automatically — add '{$email}' to \$emails in app/Providers/LaraprintsServiceProvider.php");
            }
        } else {
            $this->components->twoColumnDetail('Authorization provider', '<fg=green;options=bold>DONE</>');
            if (! $email) {
                $this->components->warn('  Add your email to app/Providers/LaraprintsServiceProvider.php to enable dashboard access.');
            }
        }
    }

    protected function publishConfig(): void
    {
        if (file_exists(config_path('laraprints.php'))) {
            $this->components->twoColumnDetail(
                'Config file',
                '<fg=yellow;options=bold>SKIPPED</> (already exists)'
            );
            return;
        }

        $this->callSilently('vendor:publish', ['--tag' => 'laraprints-config']);
        $this->components->twoColumnDetail('Config file', '<fg=green;options=bold>DONE</>');
    }

    protected function publishComponents(): void
    {
        $this->callSilently('vendor:publish', ['--tag' => 'laraprints-components']);
        $this->components->twoColumnDetail('Vue components', '<fg=green;options=bold>DONE</>');
    }

    protected function offerMiddlewareSetup(): void
    {
        $choices = [
            'Automatically on all web routes (recommended)',
            'I\'ll add it manually to specific routes',
            'Skip for now',
        ];

        $choice = $this->choice(
            'How would you like to register the page-view tracking middleware?',
            $choices,
            0
        );

        if ($choice === $choices[0]) {
            $this->enableAutoMiddleware();
        } elseif ($choice === $choices[1]) {
            $this->components->twoColumnDetail('Middleware', '<fg=cyan;options=bold>MANUAL</>');
            $this->line('  Add <comment>track.requests</comment> to the routes you want to track, or append it to the web group in <comment>bootstrap/app.php</comment>.');
        } else {
            $this->components->twoColumnDetail('Middleware', '<fg=gray>SKIPPED</>');
        }
    }

    protected function enableAutoMiddleware(): void
    {
        $configPath = config_path('laraprints.php');

        if (file_exists($configPath)) {
            $content = file_get_contents($configPath);
            $patched = str_replace(
                "'auto_register_middleware' => false,",
                "'auto_register_middleware' => true,",
                $content,
                $count
            );

            if ($count > 0) {
                file_put_contents($configPath, $patched);
                $this->components->twoColumnDetail('Middleware', '<fg=green;options=bold>AUTO-REGISTERED</>');
                return;
            }
        }

        // Config not published yet or already true — patch bootstrap/app.php for L11
        if ($this->tryPatchBootstrapApp()) {
            return;
        }

        $this->components->twoColumnDetail('Middleware', '<fg=yellow;options=bold>MANUAL NEEDED</>');
        $this->line('  Set <comment>auto_register_middleware => true</comment> in <comment>config/laraprints.php</comment>, or add:');
        $this->line('  <comment>$m->appendToGroup(\'web\', \Chrickell\Laraprints\Http\Middleware\TrackPageViews::class);</comment>');
    }

    protected function tryPatchBootstrapApp(): bool
    {
        $bootstrapPath = base_path('bootstrap/app.php');

        if (! file_exists($bootstrapPath)) {
            return false;
        }

        $content = file_get_contents($bootstrapPath);

        // Already registered
        if (str_contains($content, 'TrackPageViews')) {
            $this->components->twoColumnDetail('Middleware', '<fg=yellow;options=bold>SKIPPED</> (already registered)');
            return true;
        }

        $middlewareLine = "\n        \$middleware->appendToGroup('web', \\Chrickell\\Laraprints\\Http\\Middleware\\TrackPageViews::class);";

        // If there's already a withMiddleware block, inject into it
        if (preg_match('/->withMiddleware\(function\s*\(.*?\)\s*\{/', $content)) {
            $patched = preg_replace(
                '/(->withMiddleware\(function\s*\(.*?\)\s*\{)/',
                '$1' . $middlewareLine,
                $content,
                1
            );

            if ($patched && $patched !== $content) {
                file_put_contents($bootstrapPath, $patched);
                $this->components->twoColumnDetail('Middleware', '<fg=green;options=bold>INJECTED</> into bootstrap/app.php');
                return true;
            }
        }

        // No withMiddleware block — add one before the closing ->create()
        $block = "\n    ->withMiddleware(function (\\Illuminate\\Foundation\\Configuration\\Middleware \$middleware) {{$middlewareLine}\n    })";
        $patched = preg_replace('/(\s*->create\(\))/', "{$block}$1", $content, 1);

        if ($patched && $patched !== $content) {
            file_put_contents($bootstrapPath, $patched);
            $this->components->twoColumnDetail('Middleware', '<fg=green;options=bold>INJECTED</> into bootstrap/app.php');
            return true;
        }

        return false;
    }

    protected function runMigrations(): void
    {
        $conn = config('laraprints.database.connection');

        $existing = collect(['laraprints_page_views', 'laraprints_clicks', 'laraprints_daily_stats', 'laraprints_sessions', 'laraprints_events'])
            ->filter(fn ($t) => \Illuminate\Support\Facades\Schema::connection($conn)->hasTable($t));

        if ($existing->count() === 5) {
            $this->components->twoColumnDetail('Migrations', '<fg=yellow;options=bold>SKIPPED</> (tables already exist)');
            return;
        }

        if ($existing->isNotEmpty()) {
            $this->components->twoColumnDetail(
                'Migrations',
                '<fg=yellow;options=bold>PARTIAL</> — some tables exist, running migrate for the rest'
            );
        }

        $this->callSilently('migrate');
        $this->components->twoColumnDetail('Migrations', '<fg=green;options=bold>DONE</>');
    }

    protected function printJsInstructions(): void
    {
        $inertia = $this->confirm('Are you using Inertia.js?', false);

        $this->newLine();
        $this->line('  <fg=cyan;options=bold>Add click tracking to your JS entry point:</> <fg=gray>(resources/js/app.ts or app.js)</>');
        $this->newLine();

        if ($inertia) {
            $this->line("  <fg=gray>import</> { patchClickListeners, setupClickTracking } <fg=gray>from</> <comment>'@/vendor/laraprints/composables/useAnalyticsTracking'</comment>");
            $this->newLine();
            $this->line("  createInertiaApp({");
            $this->line("    setup({ el, App, props, plugin }) {");
            $this->line("      patchClickListeners()  <fg=gray>// call BEFORE mount to catch @click directives</>");
            $this->line("      const app = createApp({ render: () => h(App, props) }).use(plugin)");
            $this->line("      app.mount(el)");
            $this->line("      setupClickTracking({ inertia: true })");
            $this->line("    }");
            $this->line("  })");
        } else {
            $this->line("  <fg=gray>import</> { setupClickTracking } <fg=gray>from</> <comment>'@/vendor/laraprints/composables/useAnalyticsTracking'</comment>");
            $this->newLine();
            $this->line("  <fg=gray>// Call once after your app is mounted</>");
            $this->line("  setupClickTracking()");
        }

        $this->newLine();
        $this->components->bulletList([
            'Run <comment>php artisan queue:work</comment> (or set <comment>QUEUE_CONNECTION=database</comment> in dev).',
            'Run <comment>php artisan laraprints:check</comment> to verify your setup.',
        ]);
    }
}
