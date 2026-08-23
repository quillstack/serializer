<?php

declare(strict_types=1);

namespace Quillstack\Serializer\Tests\Mocks;

use Quillstack\Serializer\Attributes\Exposed;

final class Loop
{
    #[Exposed]
    public ?Loop $next = null;
}
