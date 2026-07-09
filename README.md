# qrid/codec — PHP

PHP library for encoding and decoding **MergeID electronic invoice QR codes**.

MergeID QR codes embed invoice identity information (company, tax ID, contact, address) as a base64-encoded JSON payload. This library handles the encode/decode round-trip and, optionally, SVG QR image generation.

## Typical usage flow

```mermaid
sequenceDiagram
    actor Staff as Billing Staff
    participant ERP as Billing / ERP System
    participant Lib as qrid/codec
    participant QR as Invoice QR Code
    participant App as MergeID App

    Staff->>ERP: create invoice
    ERP->>Lib: Codec::encodeQRId(code, id, company, email, address)
    Lib-->>ERP: SVG QR code
    ERP->>QR: print / embed on invoice

    Note over App,QR: later, at point of scan
    App->>QR: scan with camera
    QR-->>App: base64 payload string
    App->>Lib: Codec::decodeQRId(encoded)
    Lib-->>App: { v, code, id, company, email, address }
    App-->>Staff: display verified invoice identity
```

## Installation

```bash
# Decode only (no extra dependencies)
composer require qrid/codec

# Decode + encode SVG (adds QR generation library)
composer require qrid/codec chillerlan/php-qrcode
```

## Usage

### Decode

Pass the raw string value scanned from a QR code:

```php
use QRId\Codec;

$payload = Codec::decodeQRId($encoded);

echo $payload['v'];       // Payload schema version (int, currently 1)
echo $payload['code'];    // Installation / activity code  (e.g. "ACT-001")
echo $payload['id'];      // Tax or company ID              (e.g. "3101679980")
echo $payload['company']; // Company legal name
echo $payload['email'];   // Billing e-mail address
echo $payload['address']; // Physical address
```

`decodeQRId` trims surrounding whitespace from the input before decoding, so strings
copied with accidental padding are handled transparently.

**Exceptions thrown:**

| Exception | Cause |
| --- | --- |
| `InvalidArgumentException` | Input is not valid base64 |
| `JsonException` | Decoded bytes are not valid JSON |

```php
use InvalidArgumentException;
use JsonException;
use QRId\Codec;

try {
    $payload = Codec::decodeQRId($raw);
} catch (InvalidArgumentException $e) {
    // QR data was not base64
} catch (JsonException $e) {
    // QR data decoded but was not the expected JSON structure
}
```

### Encode (requires `chillerlan/php-qrcode`)

Produce an SVG QR code from invoice identity fields:

```php
use QRId\Codec;

$svg = Codec::encodeQRId(
    code:    'ACT-001',
    id:      '3101679980',
    company: 'Acme Corp S.A.',
    email:   'billing@acme.example',
    address: '123 Main St, San José, Costa Rica',
);

// Serve inline
header('Content-Type: image/svg+xml');
echo $svg;

// Or embed in HTML
echo '<img src="data:image/svg+xml;utf8,' . rawurlencode($svg) . '">';
```

**Exceptions thrown:**

| Exception | Cause |
| --- | --- |
| `RuntimeException` | `chillerlan/php-qrcode` is not installed |
| `JsonException` | JSON encoding of the payload failed (should not occur in practice) |

## Payload format

The QR code data is a UTF-8 JSON object encoded as standard base64 (no line-breaks):

```json
{
  "v": 1,
  "code": "ACT-001",
  "id": "3101679980",
  "company": "Acme Corp S.A.",
  "email": "billing@acme.example",
  "address": "123 Main St, San José, Costa Rica"
}
```

| Field | Type | Description |
| --- | --- | --- |
| `v` | `int` | Payload schema version. Currently always `1`. |
| `code` | `string` | Installation or activity code that links the QR to an internal record. |
| `id` | `string` | Tax / company registration ID. |
| `company` | `string` | Legal company name (UTF-8, including accented characters). |
| `email` | `string` | Primary billing or contact e-mail address. |
| `address` | `string` | Physical address of the company. |

## Requirements

| Dependency | Version | Required for |
| --- | --- | --- |
| PHP | `>= 8.1` | Always |
| `chillerlan/php-qrcode` | `^5.0` | `encodeQRId()` only |

## Running tests

```bash
composer install
composer test
```

Tests cover field decoding, UTF-8 handling, whitespace trimming, error paths, and (when `chillerlan/php-qrcode` is available) the full encode-decode round-trip.

## License

MIT
