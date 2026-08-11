<?php

namespace OkekeDev\Bachs\Resources;

use OkekeDev\Bachs\Exceptions\BachsInvalidArgumentException;

/**
 * The Bachs media / uploads resource.
 */
class Media extends BachsResource
{
    /**
     * Upload a file, sent as `multipart/form-data` with the file on the
     * `file` field. Additional `$params` are sent as extra form fields.
     *
     * @param  string  $file  A readable local file path.
     * @param  array<mixed>  $params
     * @return array<mixed>
     */
    public static function upload(string $file, array $params = [], ?string $idempotencyKey = null): array
    {
        if (! is_file($file) || ! is_readable($file)) {
            throw new BachsInvalidArgumentException(sprintf('The file [%s] does not exist or is not readable.', $file));
        }

        $contents = file_get_contents($file);

        if ($contents === false) {
            throw new BachsInvalidArgumentException(sprintf('The file [%s] could not be read.', $file));
        }

        return static::defaultClient()->upload('utilities/uploads', [
            [
                'name' => 'file',
                'contents' => $contents,
                'filename' => basename($file),
            ],
        ], $params, $idempotencyKey)->toArray();
    }

    /**
     * Fetch an uploaded file's details.
     *
     * @return array<mixed>
     */
    public static function get(string $id): array
    {
        return static::defaultClient()->get("utilities/uploads/{$id}")->toArray();
    }

    /**
     * Delete an uploaded file.
     *
     * @return array<mixed>
     */
    public static function delete(string $id, ?string $idempotencyKey = null): array
    {
        return static::defaultClient()->delete("utilities/uploads/{$id}", [], $idempotencyKey)->toArray();
    }
}
