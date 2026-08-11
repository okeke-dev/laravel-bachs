<?php

namespace OkekeDev\Bachs\Dto;

/**
 * An upload: media attached to a product, or a Connect account document.
 */
final class Upload extends Dto
{
    /**
     * The upload id (`upl_...`). The API exposes it as `upload_id`.
     */
    public function id(): string
    {
        return $this->str('upload_id') ?? '';
    }

    public function provider(): ?string
    {
        return $this->str('provider');
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

    public function url(): ?string
    {
        return $this->str('url');
    }

    public function linkedResourceType(): ?string
    {
        return $this->str('linked_resource_type');
    }

    public function linkedResourceId(): ?string
    {
        return $this->str('linked_resource_id');
    }

    public function createdAt(): ?string
    {
        return $this->str('created_at');
    }

    public function updatedAt(): ?string
    {
        return $this->str('updated_at');
    }
}
