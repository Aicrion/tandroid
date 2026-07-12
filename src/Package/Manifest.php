<?php

declare(strict_types=1);

namespace Aicrion\Tandroid\Package;

use Aicrion\Tandroid\Attribute\IntentFilter;

/**
 * Declarative descriptor for a plugin ("app") installed into the
 * framework, loaded from each plugin's manifest.php. Directly
 * analogous to AndroidManifest.xml: declares identity, version,
 * Activities, required Permissions, and Doctrine Entities owned
 * by the plugin so the Kernel can wire everything automatically.
 */
final class Manifest
{
    private function __construct(
        public readonly string $package,
        public readonly string $version,
        public readonly array $activities,
        public readonly array $permissions,
        public readonly array $entities,
        public readonly array $receivers,
    ) {}

    public static function define(
        string $package,
        string $version,
        array $activities = [],
        array $permissions = [],
        array $entities = [],
        array $receivers = [],
    ): self {
        return new self($package, $version, $activities, $permissions, $entities, $receivers);
    }

    /**
     * Reads every #[IntentFilter] attribute declared on this
     * manifest's Activities, keyed by Activity FQCN.
     *
     * @return array<class-string, list<IntentFilter>>
     */
    public function intentFilters(): array
    {
        $registry = [];

        foreach ($this->activities as $activityClass) {
            $reflection = new \ReflectionClass($activityClass);
            $filters = array_map(
                static fn (\ReflectionAttribute $attr) => $attr->newInstance(),
                $reflection->getAttributes(IntentFilter::class),
            );

            $registry[$activityClass] = $filters;
        }

        return $registry;
    }
}
