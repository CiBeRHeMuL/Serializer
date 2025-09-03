<?php

namespace AndrewGos\Serializer\Normalizer\Exception;

use AndrewGos\Serializer\Helper\HClosure;
use Closure;
use RuntimeException;

class NormalizationFailedException extends RuntimeException
{
    public static function new(string $type, Closure $normalizer): self
    {
        return new self(
            sprintf(
                'Normalizer %s cannot normalize value of type "%s"',
                HClosure::toString($normalizer),
                $type,
            ),
        );
    }
}
