<?php

namespace AndrewGos\Serializer\Container\Exception;

use Psr\Container\ContainerExceptionInterface;
use UnexpectedValueException;

class InvalidTypeException extends UnexpectedValueException implements ContainerExceptionInterface
{
}
