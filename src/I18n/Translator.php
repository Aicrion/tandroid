<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\I18n;

/**
 * Loads PHP-based language files (not flat arrays of strings) so
 * each translation entry can be a closure that receives parameters
 * and returns a computed string — enabling pluralization, gender
 * agreement, or any arbitrary Expression, unlike static ICU-style
 * placeholders. Falls back to the framework's default locale when
 * a key is missing in the requested one.
 */
final class Translator
{
    /** @var array<string, array<string, \Closure|string>> */
    private array $catalog = [];

    private function __construct(
        private readonly string $langPath,
        private readonly string $defaultLocale,
    ) {}

    public static function create(string $langPath, string $defaultLocale = 'fa'): self
    {
        return new self($langPath, $defaultLocale);
    }

    public function trans(string $key, array $params = [], ?string $locale = null): string
    {
        $locale ??= $this->defaultLocale;

        $entry = $this->resolve($key, $locale) ?? $this->resolve($key, $this->defaultLocale);

        if ($entry === null) {
            return $key;
        }

        if ($entry instanceof \Closure) {
            return (string) $entry(...$params);
        }

        return $this->interpolate($entry, $params);
    }

    private function resolve(string $key, string $locale): \Closure|string|null
    {
        $this->loadLocale($locale);

        [$file, $entryKey] = str_contains($key, '.')
            ? explode('.', $key, 2)
            : [$key, null];

        $entries = $this->catalog[$locale][$file] ?? null;

        if ($entries === null) {
            return null;
        }

        return $entryKey !== null ? ($entries[$entryKey] ?? null) : $entries;
    }

    private function loadLocale(string $locale): void
    {
        if (isset($this->catalog[$locale])) {
            return;
        }

        $this->catalog[$locale] = [];

        foreach (glob("{$this->langPath}/{$locale}/*.php") ?: [] as $file) {
            $name = basename($file, '.php');
            $this->catalog[$locale][$name] = require $file;
        }
    }

    private function interpolate(string $template, array $params): string
    {
        foreach ($params as $key => $value) {
            $template = str_replace(':' . $key, (string) $value, $template);
        }

        return $template;
    }
}