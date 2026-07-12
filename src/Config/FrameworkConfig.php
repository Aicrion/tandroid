<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Config;

use Symfony\Component\Yaml\Yaml;

/**
 * Central, immutable configuration object built from
 * config/aicrion.yaml with environment-variable overrides
 * (e.g. AICRION_BOT_TOKEN, AICRION_REDIS_DSN). Every subsystem
 * (Cache, Database, PackageManager, Update sources) reads from
 * this single source of truth instead of scattering getenv() calls.
 */
final class FrameworkConfig
{
    private function __construct(
        public readonly string $botToken,
        public readonly string $updateMode,
        public readonly ?string $redisDsn,
        public readonly array $database,
        public readonly array $plugins,
        public readonly string $locale,
        public readonly string $pluginsPath,
    ) {}

    public static function fromFile(string $path): self
    {
        $raw = Yaml::parseFile($path);

        return new self(
            botToken: self::resolveEnv($raw['bot']['token'] ?? ''),
            updateMode: $raw['bot']['mode'] ?? 'webhook',
            redisDsn: self::resolveEnv($raw['cache']['redis_dsn'] ?? null),
            database: $raw['database'] ?? ['driver' => 'pdo_sqlite', 'path' => 'var/data.sqlite'],
            plugins: $raw['plugins'] ?? [],
            locale: $raw['locale'] ?? 'fa',
            pluginsPath: $raw['plugins_path'] ?? 'plugins',
        );
    }

    private static function resolveEnv(?string $value): ?string
    {
        if ($value !== null && str_starts_with($value, '%env(') && str_ends_with($value, ')%')) {
            $name = substr($value, 5, -2);

            return getenv($name) ?: null;
        }

        return $value;
    }

    /**
     * Returns a new config sharing every setting except the bot
     * token — used by Managed\BotFactory-based setups to boot an
     * independent Kernel per child bot without duplicating YAML files.
     */
    public function withBotToken(string $botToken): self
    {
        return new self(
            botToken: $botToken,
            updateMode: $this->updateMode,
            redisDsn: $this->redisDsn,
            database: $this->database,
            plugins: $this->plugins,
            locale: $this->locale,
            pluginsPath: $this->pluginsPath,
        );
    }
}
