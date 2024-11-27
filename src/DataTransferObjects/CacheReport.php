<?php

namespace Seeru\Mtac\DataTransferObjects;

class CacheReport
{
    public function __construct(
        public bool $exists = false,
        public string $updatedAt = '',
        public int $size = 0,
    )
    {
    }
}