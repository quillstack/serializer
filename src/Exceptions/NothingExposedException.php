<?php

declare(strict_types=1);

namespace Quillstack\Serializer\Exceptions;

/**
 * An object was asked to go over the wire and nothing on it may.
 *
 * Answering with `{}` would look like an object which happens to be empty, and would keep
 * looking like one for as long as nobody noticed the attribute was missing.
 */
class NothingExposedException extends SerializerException
{
    //
}
