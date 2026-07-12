<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Package;

use Aicrion\Tandroid\Config\FrameworkConfig;

/**
 * Equivalent of Android's PackageManagerService. On every boot it
 * scans the configured plugins directory for manifest.php files,
 * aggregates their Activities into a single IntentFilter registry,
 * and — critically — drives MigrationRunner so that each plugin's
 * Doctrine entities get their schema created/updated automatically,
 * with zero manual `doctrine migrations:migrate` command. This is
 * what makes drop-in installation on shared hosting possible.
 */
final class PackageManager
{
    /** @var list<Manifest> */
    private array $manifests = [];

    public function __construct(
        private readonly FrameworkConfig $config,
    ) {}

    public function discover(): void
    {
        $pattern = rtrim($this->config->pluginsPath, '/') . '/*/manifest.php';

        foreach (glob($pattern) ?: [] as $manifestFile) {
            $this->registerPluginAutoloader(\dirname($manifestFile) . '/src');

            $manifest = require $manifestFile;

            if ($manifest instanceof Manifest) {
                $this->manifests[] = $manifest;
            }
        }
    }

    /**
     * Composer never sees a plugin's `src/` directory (plugins are
     * dropped into place, not composer-required), so nothing would
     * otherwise be able to load Greeter\StartActivity et al. This
     * registers a lightweight PSR-4-style autoloader per plugin,
     * following the same convention Composer itself uses: the
     * plugin's root namespace segment maps 1:1 to its `src/`
     * directory (e.g. `Greeter\Entity\Subscriber` resolves to
     * `plugins/greeter/src/Entity/Subscriber.php`).
     */
    private function registerPluginAutoloader(string $srcDir): void
    {
        if (!is_dir($srcDir)) {
            return;
        }

        spl_autoload_register(static function (string $class) use ($srcDir): void {
            $segments = explode('\\', $class);

            if (\count($segments) < 2) {
                return;
            }

            array_shift($segments); // drop the plugin's root namespace segment

            $file = $srcDir . '/' . implode('/', $segments) . '.php';

            if (is_file($file)) {
                require_once $file;
            }
        });
    }

    public function runPendingMigrations(): void
    {
        $runner = new MigrationRunner($this->config);

        foreach ($this->manifests as $manifest) {
            $runner->migrate($manifest);
        }
    }

    /** @return array<class-string, list<\Aicrion\Tandroid\Attribute\IntentFilter>> */
    public function intentFilterRegistry(): array
    {
        $registry = [];

        foreach ($this->manifests as $manifest) {
            $registry = [...$registry, ...$manifest->intentFilters()];
        }

        return $registry;
    }

    /** @return list<class-string> */
    public function activityClasses(): array
    {
        return array_merge(...array_map(
            static fn (Manifest $m) => $m->activities,
            $this->manifests,
        ) ?: [[]]);
    }

    /** @return list<class-string> every #[BroadcastFilter]-annotated receiver declared across all installed plugins */
    public function receiverClasses(): array
    {
        return array_merge(...array_map(
            static fn (Manifest $m) => $m->receivers,
            $this->manifests,
        ) ?: [[]]);
    }

    /** @return list<class-string> every Doctrine entity declared across all installed plugins */
    public function entityClasses(): array
    {
        return array_merge(...array_map(
            static fn (Manifest $m) => $m->entities,
            $this->manifests,
        ) ?: [[]]);
    }
}
