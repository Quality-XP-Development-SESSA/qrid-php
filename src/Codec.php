<?php

declare(strict_types=1);

namespace QRId;

use InvalidArgumentException;
use JsonException;
use RuntimeException;

final class Codec
{
    /**
     * Decode a base64-encoded QR ID string into its structured payload.
     *
     * @return array{v: int, code: string, id: string, company: string, email: string, address: string}
     *
     * @throws InvalidArgumentException When the base64 input is malformed.
     * @throws JsonException            When the decoded content is not valid JSON.
     */
    public static function decodeQRId(string $encoded): array
    {
        $json = base64_decode(trim($encoded), strict: true);

        if ($json === false) {
            throw new InvalidArgumentException(
                'Invalid base64 input: could not decode the provided string.'
            );
        }

        return json_decode($json, associative: true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * Encode invoice identity fields and return an SVG QR code string.
     *
     * Requires: chillerlan/php-qrcode ^5.0
     *   composer require chillerlan/php-qrcode
     *
     * @throws RuntimeException When chillerlan/php-qrcode is not installed.
     * @throws JsonException    When JSON encoding fails.
     */
    public static function encodeQRId(
        string $code,
        string $id,
        string $company,
        string $email,
        string $address,
    ): string {
        if (!class_exists(\chillerlan\QRCode\QRCode::class)) {
            throw new RuntimeException(
                'chillerlan/php-qrcode is required for encodeQRId(). ' .
                'Install with: composer require chillerlan/php-qrcode'
            );
        }

        $payload = [
            'v'       => 1,
            'code'    => $code,
            'id'      => $id,
            'company' => $company,
            'email'   => $email,
            'address' => $address,
        ];

        $encoded = base64_encode(
            json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)
        );

        $options = new \chillerlan\QRCode\QROptions([
            'outputType' => \chillerlan\QRCode\QRCode::OUTPUT_MARKUP_SVG,
            'imageBase64' => false,
        ]);

        return (new \chillerlan\QRCode\QRCode($options))->render($encoded);
    }
}
