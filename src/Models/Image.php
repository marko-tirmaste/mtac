<?php

declare(strict_types=1);

namespace Seeru\Mtac\Models;

defined('VDAI_PATH') or die;

use Vdisain\Plugins\Interfaces\Support\Contracts\Models\AttachmentContract;
use Vdisain\Plugins\Interfaces\Support\Model\Model;

class Image extends Model implements AttachmentContract
{
    public function getIdentifier(): int|string|null
    {
        return $this->attributes['url'];
    }

    public function getIdentifierKey(): string
    {
        return '_mtac_id';
    }

    public function getIdAttribute(): int|string|null
    {
        return $this->attributes['url'];
    }

    public function getNameAttribute(): array|string|null
    {
        return null;
    }

    public function getDescriptionAttribute(): array|string|null
    {
        return null;
    }

    public function getOrderAttribute(): ?int
    {
        return null;
    }

    public function getTypeAttribute(): ?int
    {
        return null;
    }

    public function getStatusAttribute(): ?string
    {
        return 'publish';
    }

    public function getUrlAttribute(): ?string
    {
        return $this->attributes['url'];
    }
}
