<?php

namespace AndrewGos\Serializer\Tests\TestCase;

class Address
{
    public function __construct(
        private string $street,
        private string $city,
        private string $zipCode,
    ) {
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getZipCode(): string
    {
        return $this->zipCode;
    }
}
