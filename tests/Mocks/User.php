<?php

declare(strict_types=1);

namespace Quillstack\Serializer\Tests\Mocks;

use DateTimeImmutable;
use Quillstack\Serializer\Attributes\Exposed;

final class User
{
    /**
     * @param Post[] $posts
     */
    public function __construct(
        #[Exposed] public int $id = 1,
        #[Exposed] public string $email = 'ada@example.com',
        /** Nobody said this may go, so it does not. */
        public string $password = 'sekret',
        #[Exposed(name: 'created_at')] public ?DateTimeImmutable $createdAt = null,
        #[Exposed(groups: ['admin'])] public ?string $note = null,
        #[Exposed] public array $posts = [],
        #[Exposed] public ?Status $status = null
    ) {
        //
    }
}
