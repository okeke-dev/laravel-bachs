<?php

namespace OkekeDev\Bachs\Exceptions;

use Illuminate\Http\Client\Response;

final class Map
{
    /**
     * Map a failed HTTP response to a typed Bachs exception.
     *
     * The error payload follows the verified Bachs error object:
     * `{ detail, error_code, doc_url, errors?, details? }`.
     */
    public static function fromResponse(Response $response): BachsApiException
    {
        $status = $response->status();
        $body = $response->json() ?? [];
        $requestId = $response->header('x-request-id') ?: null;

        $errorCode = isset($body['error_code']) && is_string($body['error_code'])
            ? $body['error_code']
            : null;

        $detail = (string) ($body['detail'] ?? $body['message'] ?? 'Unknown Bachs API error.');

        $message = sprintf('Bachs API error %d [%s]: %s', $status, $errorCode ?? 'UNKNOWN', $detail);

        if ($requestId !== null) {
            $message .= sprintf(' (request id: %s)', $requestId);
        }

        $arguments = [
            'status' => $status,
            'errorCode' => $errorCode,
            'message' => $message,
            'requestId' => $requestId,
            'body' => $body,
            'headers' => $response->headers(),
            'docUrl' => isset($body['doc_url']) && is_string($body['doc_url']) ? $body['doc_url'] : null,
        ];

        return match ($status) {
            401 => new BachsAuthenticationException(...$arguments),
            403 => new BachsAuthorizationException(...$arguments),
            404 => new BachsNotFoundException(...$arguments),
            409 => new BachsConflictException(...$arguments),
            422 => new BachsValidationException(...$arguments),
            429 => new BachsRateLimitException(...$arguments),
            default => new BachsApiException(...$arguments),
        };
    }
}
