<?php

declare(strict_types=1);

namespace Quillstack\Serializer\Attributes;

use Attribute;

/**
 * Says that this property may go over the wire.
 *
 * Nothing goes without it. That is the whole point: adding a column called `password` to an
 * entity does not put it in an API response, because nobody said it could — whereas a
 * serializer which sends everything but a list of exclusions sends the new field on the day
 * it is added, and says nothing about it.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final class Exposed
{
    /**
     * @param ?string $name what the field is called on the wire, where that differs from the
     *                      property — `createdAt` going out as `created_at`, say
     * @param string[] $groups the audiences this field is for; empty means every one of them
     */
    public function __construct(
        public readonly ?string $name = null,
        public readonly array $groups = []
    ) {
        //
    }

    /**
     * Whether this field is for the audience being served.
     *
     * @param string[] $groups
     */
    public function isFor(array $groups): bool
    {
        if ($this->groups === []) {
            return true;
        }

        foreach ($groups as $group) {
            if (in_array($group, $this->groups, true)) {
                return true;
            }
        }

        return false;
    }
}
