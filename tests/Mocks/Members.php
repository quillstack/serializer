<?php

declare(strict_types=1);

namespace Quillstack\Serializer\Tests\Mocks;

use ArrayIterator;
use IteratorAggregate;
use Quillstack\Serializer\Attributes\Exposed;
use Traversable;

/**
 * Something which is walked rather than held — which is what an ORM's relations are.
 *
 * @implements IteratorAggregate<int, User>
 */
final class Members implements IteratorAggregate
{
    /**
     * @param User[] $members
     */
    public function __construct(private readonly array $members = [])
    {
        //
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->members);
    }
}
