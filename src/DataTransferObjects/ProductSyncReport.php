<?php

namespace Seeru\Mtac\DataTransferObjects;

class ProductSyncReport
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 100,
        public int $pages = 0,
        public int $added = 0,
        public int $updated = 0,
        public int $deleted = 0,
        public int $total = 0,
        public string $updatedAt = '',
        public string $executionTime = '',
        public int $memoryUsage = 0,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['page'] ?? 1,
            $data['per_page'] ?? 100,
            $data['pages'] ?? 0,
            0, // $data['added'] ?? 0,
            $data['processed'] ?? 0, // $data['updated'] ?? 0,
            0, // $data['deleted'] ?? 0,
            $data['total'] ?? 0,
            $data['updated_at'] ?? __('Never', 'seeru-mtac'),
            $data['time'] ?? '',
            $data['memory'] ?? 0,
        );
    }

    public static function fromConfig(string $key): self
    {
        return self::fromArray((array) get_option($key, []));
    }
}