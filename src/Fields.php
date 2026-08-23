<?php

declare(strict_types=1);

namespace Quillstack\Serializer;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionProperty;
use Quillstack\Serializer\Attributes\Exposed;

/**
 * Which properties of a class may go over the wire, worked out once and kept.
 *
 * Reflection is not cheap enough to do per object in a list of a thousand, and a class does
 * not change while the process runs.
 */
final class Fields
{
    /**
     * @var array<class-string, array<string, Exposed>>
     */
    private static array $known = [];

    /**
     * The exposed properties of a class, keyed by the property name.
     *
     * @param class-string $class
     *
     * @return array<string, Exposed>
     */
    public static function of(string $class): array
    {
        if (isset(self::$known[$class])) {
            return self::$known[$class];
        }

        $reflection = new ReflectionClass($class);
        $fields = [];

        // Promoted constructor parameters carry the attribute on the parameter, and are
        // properties all the same — an entity written the usual way here has all of them.
        foreach ($reflection->getConstructor()?->getParameters() ?? [] as $parameter) {
            $exposed = self::attribute($parameter->getAttributes(Exposed::class));

            if ($exposed !== null) {
                $fields[$parameter->getName()] = $exposed;
            }
        }

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if (isset($fields[$property->getName()])) {
                continue;
            }

            $exposed = self::attribute($property->getAttributes(Exposed::class));

            if ($exposed !== null) {
                $fields[$property->getName()] = $exposed;
            }
        }

        return self::$known[$class] = $fields;
    }

    /**
     * Forgets what was worked out, which only a test has any reason to do.
     */
    public static function forget(): void
    {
        self::$known = [];
    }

    /**
     * @param ReflectionAttribute<Exposed>[] $attributes
     */
    private static function attribute(array $attributes): ?Exposed
    {
        return isset($attributes[0]) ? $attributes[0]->newInstance() : null;
    }
}
