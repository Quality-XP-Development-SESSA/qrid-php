# qrid/codec — PHP

Decode and encode MergeID electronic invoice QR codes.

## Installation

```bash
# Decode only (no extra dependencies)
composer require qrid/codec

# Decode + encode (add QR generation library)
composer require qrid/codec chillerlan/php-qrcode
```

## Usage

### Decode

```php
use QRId\Codec;

// $encoded is the raw string value scanned from the QR code
$payload = Codec::decodeQRId($encoded);

echo $payload['code'];    // Installation activity code
echo $payload['id'];      // User / installation ID
echo $payload['company']; // Company legal name
echo $payload['email'];   // Email address
echo $payload['address']; // Physical address
```

### Encode (requires `chillerlan/php-qrcode`)

```php
use QRId\Codec;

$svg = Codec::encodeQRId(
    code: 'ACT-12345',
    id: '3101679980',
    company: 'Acme Corp S.A.',
    email: 'billing@acme.example',
    address: '123 Main St, San José, Costa Rica',
);

// Output as SVG image
header('Content-Type: image/svg+xml');
echo $svg;
```

## Payload format

The QR code contains a UTF-8 JSON object encoded as standard base64:

```json
{
  "v": 1,
  "code": "ACT-12345",
  "id": "3101679980",
  "company": "Acme Corp S.A.",
  "email": "billing@acme.example",
  "address": "123 Main St, San José, Costa Rica"
}
```

## Requirements

- PHP 8.0+
- `chillerlan/php-qrcode` ^5.0 (only for `encodeQRId`)

## License

MIT
