<?php

namespace AndrewGos\Serializer\Tests\TestCase;

class Person
{
    public function __construct(private string $name)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }
}
