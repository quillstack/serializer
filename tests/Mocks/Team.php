<?php

declare(strict_types=1);

namespace Quillstack\Serializer\Tests\Mocks;

use Quillstack\Serializer\Attributes\Exposed;

final class Team
{
    public function __construct(
        #[Exposed] public string $name = 'Quillstack',
        #[Exposed] public ?Members $members = null
    ) {
        //
    }
}
