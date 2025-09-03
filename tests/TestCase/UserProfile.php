<?php

namespace AndrewGos\Serializer\Tests\TestCase;

class UserProfile
{
    public function __construct(
        private User $user,
        private Address $address,
    ) {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }
}
