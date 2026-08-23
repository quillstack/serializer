<?php

declare(strict_types=1);

namespace Quillstack\Serializer\Tests\Mocks;

use Quillstack\Serializer\Attributes\Exposed;

final class Post
{
    public function __construct(
        #[Exposed] public int $id = 1,
        #[Exposed] public string $title = 'Hello',
        public string $draft = 'not for anybody'
    ) {
        //
    }
}
