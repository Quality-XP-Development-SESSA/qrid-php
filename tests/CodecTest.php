<?php

declare(strict_types=1);

namespace QRId\Tests;

use InvalidArgumentException;
use JsonException;
use PHPUnit\Framework\TestCase;
use QRId\Codec;

final class CodecTest extends TestCase
{
    // ── Helpers ───────────────────────────────────────────────────────────────

    /** @param array<string, mixed> $payload */
    private function encode(array $payload): string
    {
        return base64_encode(
            json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)
        );
    }

    /** @return array<string, mixed> */
    private function samplePayload(array $overrides = []): array
    {
        return array_merge([
            'v'       => 1,
            'code'    => 'ACT-001',
            'id'      => '3101679980',
            'company' => 'Acme Corp S.A.',
            'email'   => 'billing@acme.example',
            'address' => '123 Main St, San José, Costa Rica',
        ], $overrides);
    }

    // ── decodeQRId ────────────────────────────────────────────────────────────

    public function testDecodeReturnsAllFields(): void
    {
        $payload = $this->samplePayload();
        $result  = Codec::decodeQRId($this->encode($payload));

        $this->assertSame(1,              $result['v']);
        $this->assertSame('ACT-001',      $result['code']);
        $this->assertSame('3101679980',   $result['id']);
        $this->assertSame('Acme Corp S.A.',            $result['company']);
        $this->assertSame('billing@acme.example',      $result['email']);
        $this->assertSame('123 Main St, San José, Costa Rica', $result['address']);
    }

    public function testDecodeHandlesUtf8(): void
    {
        $payload = $this->samplePayload([
            'company' => 'Société Générale',
            'address' => 'Paseo Colón, San José, Costa Rica',
        ]);

        $result = Codec::decodeQRId($this->encode($payload));

        $this->assertSame('Société Générale', $result['company']);
        $this->assertSame('Paseo Colón, San José, Costa Rica', $result['address']);
    }

    public function testDecodeTrimsWhitespace(): void
    {
        $encoded = '  ' . $this->encode($this->samplePayload()) . '  ';
        $result  = Codec::decodeQRId($encoded);

        $this->assertSame('ACT-001', $result['code']);
    }

    public function testDecodeThrowsOnInvalidBase64(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Codec::decodeQRId('not-valid-base64!!!');
    }

    public function testDecodeThrowsOnNonJsonPayload(): void
    {
        $this->expectException(JsonException::class);
        Codec::decodeQRId(base64_encode('not json'));
    }

    // ── round-trip ────────────────────────────────────────────────────────────

    public function testEncodeDecodeRoundTrip(): void
    {
        if (!class_exists(\chillerlan\QRCode\QRCode::class)) {
            $this->markTestSkipped('chillerlan/php-qrcode not installed.');
        }

        $svg = Codec::encodeQRId(
            code:    'ACT-001',
            id:      '3101679980',
            company: 'Acme Corp S.A.',
            email:   'billing@acme.example',
            address: '123 Main St',
        );

        // SVG output contains a base64 data block — extract and decode it.
        preg_match('/"([A-Za-z0-9+\/=]{20,})"/', $svg, $matches);
        $this->assertNotEmpty($matches[1], 'No base64 string found in SVG output.');

        $decoded = Codec::decodeQRId($matches[1]);
        $this->assertSame('ACT-001',     $decoded['code']);
        $this->assertSame('3101679980',  $decoded['id']);
        $this->assertSame('Acme Corp S.A.', $decoded['company']);
    }
}
