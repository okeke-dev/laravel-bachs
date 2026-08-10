<?php

namespace OkekeDev\Bachs\Exceptions;

class BachsValidationException extends BachsApiException
{
    /**
     * Field-level validation errors, when present on 422 responses.
     *
     * @return array<int, array<string, mixed>>
     */
    public function fieldErrors(): array
    {
        $errors = $this->body['errors'] ?? [];

        return is_array($errors) ? array_values($errors) : [];
    }
}
