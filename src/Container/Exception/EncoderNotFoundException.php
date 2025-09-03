<?php

namespace AndrewGos\Serializer\Container\Exception;

use Psr\Container\NotFoundExceptionInterface;
use UnexpectedValueException;

class EncoderNotFoundException extends UnexpectedValueException implements NotFoundExceptionInterface
{
    public static function new(string $type): self
    {
        return new self(sprintf('Encoder for type "%s" not found', $type));
    }
}
