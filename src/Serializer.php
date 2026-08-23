<?php

declare(strict_types=1);

namespace Quillstack\Serializer;

use BackedEnum;
use DateTimeInterface;
use JsonSerializable;
use Quillstack\Serializer\Exceptions\NothingExposedException;
use Stringable;
use Traversable;

/**
 * Turns objects into what goes over the wire.
 *
 * It says what may go, never what may not. A field is on the wire because somebody wrote
 * `#[Exposed]` above it — so a column added to an entity tomorrow is not in the API today,
 * and nobody has to remember to exclude it.
 */
final class Serializer
{
    /**
     * How deep it will follow one object into another before deciding something points back
     * at itself. A cycle is a stack overflow, and this says so instead.
     */
    public const DEPTH = 32;

    /**
     * @param string[] $groups the audiences being served; empty means the fields which are
     *                         for everybody
     */
    public function __construct(private readonly array $groups = [])
    {
        //
    }

    /**
     * What this value looks like on the wire.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $value): array
    {
        $result = $this->value($value, 0);

        if (!is_array($result)) {
            throw new NothingExposedException(
                $value::class . ' has nothing anybody said may go over the wire'
            );
        }

        /** @var array<string, mixed> $result */
        return $result;
    }

    /**
     * A list of them.
     *
     * @param iterable<int, object> $values
     *
     * @return array<int, array<string, mixed>>
     */
    public function toArrays(iterable $values): array
    {
        $result = [];

        foreach ($values as $value) {
            $result[] = $this->toArray($value);
        }

        return $result;
    }

    public function toJson(object $value, int $flags = 0): string
    {
        return (string) json_encode($this->toArray($value), $flags);
    }

    private function value(mixed $value, int $depth): mixed
    {
        if ($depth > self::DEPTH) {
            throw new NothingExposedException(
                'Stopped at ' . self::DEPTH . ' deep: something here points back at itself'
            );
        }

        return match (true) {
            $value === null, is_scalar($value) => $value,
            $value instanceof BackedEnum => $value->value,
            $value instanceof DateTimeInterface => $value->format(DateTimeInterface::ATOM),
            is_array($value) => $this->list($value, $depth),
            $value instanceof Traversable => $this->list(iterator_to_array($value), $depth),
            $value instanceof JsonSerializable => $value->jsonSerialize(),
            is_object($value) => $this->object($value, $depth),
            default => null,
        };
    }

    /**
     * @param array<array-key, mixed> $values
     *
     * @return array<array-key, mixed>
     */
    private function list(array $values, int $depth): array
    {
        $result = [];

        foreach ($values as $key => $value) {
            $result[$key] = $this->value($value, $depth + 1);
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function object(object $value, int $depth): array
    {
        $fields = Fields::of($value::class);

        if ($fields === []) {
            throw new NothingExposedException(
                $value::class . ' has nothing anybody said may go over the wire'
            );
        }

        /** @var array<string, mixed> $properties */
        $properties = get_object_vars($value);
        $result = [];

        foreach ($fields as $property => $exposed) {
            if (!$exposed->isFor($this->groups)) {
                continue;
            }

            $result[$exposed->name ?? $property] = $this->value(
                $properties[$property] ?? null,
                $depth + 1
            );
        }

        return $result;
    }
}
