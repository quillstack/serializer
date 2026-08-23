<?php

declare(strict_types=1);

namespace Quillstack\Serializer\Tests\Mocks;

/**
 * Nothing on it may go over the wire, which is a thing to say rather than an empty object.
 */
final class Nothing
{
    public string $secret = 'sekret';
}
