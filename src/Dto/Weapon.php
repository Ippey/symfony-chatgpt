<?php

namespace App\Dto;

class Weapon
{
    public function __construct(
        public readonly string $key,
        public readonly string $name,
        public readonly string $type,
        public readonly string $subWeapon,
        public readonly string $specialWeapon
    ) {
    }

    public function __toString(): string
    {
        return sprintf('%s|%s|%s|%s', $this->name, $this->type, $this->subWeapon, $this->specialWeapon);
    }
}
