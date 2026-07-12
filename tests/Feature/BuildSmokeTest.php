<?php
declare(strict_types=1);

namespace Aicrion\Tandroid\Tests\Feature;

use PHPUnit\Framework\TestCase;

final class BuildSmokeTest extends TestCase
{
    public function test_docs_and_tests_exist(): void
    {
        $this->assertFileExists(__DIR__ . '/../../docs/index.html');
        $this->assertFileExists(__DIR__ . '/../../docs/guide/00-index.md');
        $this->assertFileExists(__DIR__ . '/../../phpunit.xml');
    }

    public function test_composer_json_is_valid_and_declares_required_dependencies(): void
    {
        $composer = json_decode(
            file_get_contents(__DIR__ . '/../../composer.json') ?: '',
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );

        foreach (['symfony/dependency-injection', 'doctrine/orm', 'doctrine/migrations'] as $package) {
            $this->assertArrayHasKey($package, $composer['require'], "composer.json باید به {$package} وابسته باشد.");
        }
    }

    public function test_every_plugin_manifest_is_loadable(): void
    {
        foreach (glob(__DIR__ . '/../../plugins/*/manifest.php') ?: [] as $manifestFile) {
            $manifest = require $manifestFile;

            $this->assertInstanceOf(\Aicrion\Tandroid\Package\Manifest::class, $manifest, "{$manifestFile} باید یک Manifest برگرداند.");

            foreach ($manifest->activities as $activityClass) {
                $this->assertTrue(class_exists($activityClass), "{$activityClass} در manifest اعلام شده ولی کلاسی با این نام وجود ندارد.");
            }
        }
    }
}