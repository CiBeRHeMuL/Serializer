<?php

namespace AndrewGos\Serializer\Tests\TestCase;

class Employee extends Person
{
    public function __construct(string $name, private string $position)
    {
        parent::__construct($name);
    }

    public function getPosition(): string
    {
        return $this->position;
    }
}
