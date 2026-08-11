<?php

namespace OkekeDev\Bachs\Dto;

/**
 * A media item (image) attached to a product.
 */
final class MediaItem extends Dto
{
    public function id(): string
    {
        return $this->str('id') ?? '';
    }

    public function url(): ?string
    {
        return $this->str('url');
    }

    public function fileName(): ?string
    {
        return $this->str('file_name');
    }

    public function mimeType(): ?string
    {
        return $this->str('mime_type');
    }

    public function fileSizeBytes(): ?int
    {
        return $this->int('file_size_bytes');
    }

    public function createdAt(): ?string
    {
        return $this->str('created_at');
    }
}
