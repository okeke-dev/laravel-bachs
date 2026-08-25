<?php

use OkekeDev\Bachs\Exceptions\BachsApiException;
use OkekeDev\Bachs\Exceptions\BachsAuthenticationException;
use OkekeDev\Bachs\Exceptions\BachsAuthorizationException;
use OkekeDev\Bachs\Exceptions\BachsConflictException;
use OkekeDev\Bachs\Exceptions\BachsInvalidArgumentException;
use OkekeDev\Bachs\Exceptions\BachsNetworkException;
use OkekeDev\Bachs\Exceptions\BachsNotFoundException;
use OkekeDev\Bachs\Exceptions\BachsRateLimitException;
use OkekeDev\Bachs\Exceptions\BachsValidationException;

it('creates a BachsApiException with all properties', function () {
    $exception = new BachsApiException(
        status: 400,
        errorCode: 'BAD_REQUEST',
        message: 'Something went wrong',
        requestId: 'req_123',
        body: ['detail' => 'Bad request'],
        headers: ['content-type' => ['application/json']],
        docUrl: 'https://docs.bachs.io/errors',
    );

    expect($exception->status())->toBe(400)
        ->and($exception->errorCode())->toBe('BAD_REQUEST')
        ->and($exception->getMessage())->toBe('Something went wrong')
        ->and($exception->requestId())->toBe('req_123')
        ->and($exception->body())->toBe(['detail' => 'Bad request'])
        ->and($exception->docUrl())->toBe('https://docs.bachs.io/errors');
});

it('creates a BachsApiException with nullable defaults', function () {
    $exception = new BachsApiException(
        status: 500,
        errorCode: null,
        message: 'Server error',
    );

    expect($exception->status())->toBe(500)
        ->and($exception->errorCode())->toBeNull()
        ->and($exception->requestId())->toBeNull()
        ->and($exception->docUrl())->toBeNull()
        ->and($exception->body())->toBe([])
        ->and($exception->headers)->toBe([]);
});

it('BachsAuthenticationException extends BachsApiException', function () {
    $exception = new BachsAuthenticationException(
        status: 401,
        errorCode: 'UNAUTHORIZED',
        message: 'Invalid API key',
    );

    expect($exception)->toBeInstanceOf(BachsApiException::class)
        ->and($exception->status())->toBe(401);
});

it('BachsAuthorizationException extends BachsApiException', function () {
    $exception = new BachsAuthorizationException(
        status: 403,
        errorCode: 'FORBIDDEN',
        message: 'Access denied',
    );

    expect($exception)->toBeInstanceOf(BachsApiException::class)
        ->and($exception->status())->toBe(403);
});

it('BachsNotFoundException extends BachsApiException', function () {
    $exception = new BachsNotFoundException(
        status: 404,
        errorCode: 'NOT_FOUND',
        message: 'Resource not found',
    );

    expect($exception)->toBeInstanceOf(BachsApiException::class)
        ->and($exception->status())->toBe(404);
});

it('BachsConflictException extends BachsApiException', function () {
    $exception = new BachsConflictException(
        status: 409,
        errorCode: 'CONFLICT',
        message: 'Resource conflict',
    );

    expect($exception)->toBeInstanceOf(BachsApiException::class)
        ->and($exception->status())->toBe(409);
});

it('BachsValidationException returns field errors from body', function () {
    $exception = new BachsValidationException(
        status: 422,
        errorCode: 'VALIDATION_ERROR',
        message: 'Validation failed',
        body: [
            'errors' => [
                ['field' => 'email', 'message' => 'Required', 'type' => 'value_error'],
                ['field' => 'name', 'message' => 'Too short', 'type' => 'value_error'],
            ],
        ],
    );

    expect($exception->fieldErrors())->toHaveCount(2)
        ->and($exception->fieldErrors()[0]['field'])->toBe('email')
        ->and($exception->fieldErrors()[1]['field'])->toBe('name');
});

it('BachsValidationException returns empty array when no errors in body', function () {
    $exception = new BachsValidationException(
        status: 422,
        errorCode: 'VALIDATION_ERROR',
        message: 'Validation failed',
        body: [],
    );

    expect($exception->fieldErrors())->toBe([]);
});

it('BachsRateLimitException extracts retry-after from Retry-After header', function () {
    $exception = new BachsRateLimitException(
        status: 429,
        errorCode: 'TOO_MANY_REQUESTS',
        message: 'Rate limited',
        headers: ['Retry-After' => ['5']],
    );

    expect($exception->retryAfter())->toBe(5);
});

it('BachsRateLimitException extracts retry-after from X-RateLimit-Reset header', function () {
    $resetTime = (string) (time() + 10);
    $exception = new BachsRateLimitException(
        status: 429,
        errorCode: 'TOO_MANY_REQUESTS',
        message: 'Rate limited',
        headers: ['X-RateLimit-Reset' => [$resetTime]],
    );

    expect($exception->retryAfter())->toBeGreaterThanOrEqual(9)
        ->and($exception->retryAfter())->toBeLessThanOrEqual(11);
});

it('BachsRateLimitException returns null when no retry headers', function () {
    $exception = new BachsRateLimitException(
        status: 429,
        errorCode: 'TOO_MANY_REQUESTS',
        message: 'Rate limited',
    );

    expect($exception->retryAfter())->toBeNull();
});

it('BachsNetworkException extends BachsException', function () {
    $exception = new BachsNetworkException('Connection failed');

    expect($exception->getMessage())->toBe('Connection failed');
});

it('BachsInvalidArgumentException extends BachsException', function () {
    $exception = new BachsInvalidArgumentException('Invalid configuration');

    expect($exception->getMessage())->toBe('Invalid configuration');
});
