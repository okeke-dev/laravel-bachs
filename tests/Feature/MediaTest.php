<?php

use Illuminate\Support\Facades\Http;
use OkekeDev\Bachs\Exceptions\BachsInvalidArgumentException;
use OkekeDev\Bachs\Resources\Media;

it('uploads a file as multipart form data', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/utilities/uploads' => Http::response([
            'id' => 'upl_1',
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

    expect($upload['id'])->toBe('upl_1');
});

it('sends the idempotency key on uploads', function () {
    Http::fake();

    Media::upload(__DIR__.'/../Fixtures/upload-sample.txt', [], 'idem_upload_1');

    Http::assertSent(fn ($request) => $request->hasHeader('Idempotency-Key', 'idem_upload_1'));
});

it('fetches and deletes an uploaded file', function () {
    Http::fake([
        'sandbox-api.bachs.io/v1/utilities/uploads/upl_1' => Http::sequence()
            ->push(['id' => 'upl_1', 'url' => 'https://cdn.bachs.io/uploads/upl_1'], 200)
            ->push(['id' => 'upl_1', 'deleted' => true], 200),
    ]);

    expect(Media::get('upl_1')['id'])->toBe('upl_1');
    expect(Media::delete('upl_1')['deleted'])->toBeTrue();
});

it('rejects a missing file', function () {
    Media::upload(__DIR__.'/../Fixtures/missing.txt');
})->throws(BachsInvalidArgumentException::class);
