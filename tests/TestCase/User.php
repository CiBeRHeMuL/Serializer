<?php

namespace AndrewGos\Serializer\Tests\TestCase;

class User
{
    public function __construct(
        private int $id,
        private string $name,
        private string $email,
        private bool $active,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function isActive(): bool
    {
        return $this->active;
    }
}
