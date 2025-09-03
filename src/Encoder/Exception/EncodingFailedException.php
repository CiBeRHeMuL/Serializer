<?php

namespace AndrewGos\Serializer\Encoder\Exception;

use AndrewGos\Serializer\Helper\HClosure;
use Closure;
use RuntimeException;

class EncodingFailedException extends RuntimeException
{
    public static function new(string $type, Closure $encoder, string $reason): self
    {
        return new self(
            sprintf(
                'Encoder %s cannot encode value of type "%s" due to error: %s',
                HClosure::toString($encoder),
                $type,
                $reason,
            ),
        );
    }
}
