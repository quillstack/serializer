<?php

declare(strict_types=1);

namespace Quillstack\Serializer\Tests\Mocks;

enum Status: string
{
    case Active = 'active';
    case Suspended = 'suspended';
}
