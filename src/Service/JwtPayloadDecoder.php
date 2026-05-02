<?php

namespace App\Service;

final class JwtPayloadDecoder
{
    public function decode(string $token): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new \InvalidArgumentException('Invalid JWT token.');
        }

        $payload = $parts[1];

        $payload = strtr($payload, '-_', '+/');
        $payload .= str_repeat('=', (4 - strlen($payload) % 4) % 4);

        $decoded = base64_decode($payload, true);

        if ($decoded === false) {
            throw new \InvalidArgumentException('Invalid JWT payload.');
        }

        $data = json_decode($decoded, true);

        if (!is_array($data)) {
            throw new \InvalidArgumentException('Invalid JWT payload data.');
        }

        return $data;
    }
}
