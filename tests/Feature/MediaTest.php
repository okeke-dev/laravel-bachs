<?php

use Illuminate\Support\Facades\Http;
use OkekeDev\Bachs\Dto\Upload;
use OkekeDev\Bachs\Exceptions\BachsInvalidArgumentException;
use OkekeDev\Bachs\Resources\Media;

it('uploads a file as multipart form data', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/utilities/uploads' => Http::response([
            'upload_id' => 'upl_1',
            'file_name' => 'upload-sample.txt',
            'mime_type' => 'text/plain',
            'url' => 'https://cdn.bachs.io/uploads/upl_1',
        ], 201),
    ]);

    $upload = Media::upload(__DIR__.'/../Fixtures/upload-sample.txt', ['purpose' => 'logo']);

    Http::assertSent(function ($request) {
        if ($request->method() !== 'POST'
            || $request->url() !== 'https://sandbox-api.bachs.io/v1/utilities/uploads') {
            return false;
        }

        $contentType = $request->header('Content-Type');
        $contentType = is_array($contentType) ? implode(',', $contentType) : (string) $contentType;
        $parts = is_array($request->data()) ? $request->data() : [];

        $hasFile = collect($parts)->contains(fn ($part) => is_array($part)
            && ($part['name'] ?? null) === 'file'
            && ($part['filename'] ?? null) === 'upload-sample.txt');

        $hasPurpose = collect($parts)->contains(fn ($part) => is_array($part)
            && ($part['name'] ?? null) === 'purpose'
            && ($part['contents'] ?? null) === 'logo');

        return $contentType !== null
            && str_starts_with($contentType, 'multipart/form-data')
            && $hasFile
            && $hasPurpose;
    });

    expect($upload)->toBeInstanceOf(Upload::class)
        ->and($upload->id())->toBe('upl_1')
        ->and($upload->fileName())->toBe('upload-sample.txt');
});

it('sends the idempotency key on uploads', function () {
    Http::fake();

    Media::upload(__DIR__.'/../Fixtures/upload-sample.txt', [], 'idem_upload_1');

    Http::assertSent(fn ($request) => $request->hasHeader('Idempotency-Key', 'idem_upload_1'));
});

it('fetches and deletes an uploaded file', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/utilities/uploads/upl_1' => Http::response([
            'upload_id' => 'upl_1',
            'file_name' => 'upload-sample.txt',
            'mime_type' => 'text/plain',
            'file_size_bytes' => 17,
            'url' => 'https://cdn.bachs.io/uploads/upl_1',
            'created_at' => '2026-07-13T14:00:00.000Z',
        ], 200),
    ]);

    $upload = Media::get('upl_1');

    expect($upload)->toBeInstanceOf(Upload::class)
        ->and($upload->id())->toBe('upl_1')
        ->and($upload->fileSizeBytes())->toBe(17);

    Http::fake([
        'sandbox-api.bachs.io/v1/utilities/uploads/upl_1' => Http::response(['upload_id' => 'upl_1', 'deleted' => true], 200),
    ]);

    Media::delete('upl_1');

    Http::assertSent(fn ($request) => $request->method() === 'DELETE'
        && $request->url() === 'https://sandbox-api.bachs.io/v1/utilities/uploads/upl_1');
});

it('rejects a missing file', function () {
    Media::upload(__DIR__.'/../Fixtures/missing.txt');
})->throws(BachsInvalidArgumentException::class);
