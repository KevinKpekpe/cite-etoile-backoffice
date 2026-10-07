<?php

namespace App\Security;

use Illuminate\Support\Str;

class Totp
{
    /**
     * Build an otpauth:// URI for use in a QR code.
     *
     * @param  string  $secret  Base32 TOTP secret
     * @param  string  $accountName  Label shown in the authenticator app (e.g. user email)
     * @param  string  $issuer  Service name shown in the authenticator app
     */
    public function otpauthUri(string $secret, string $accountName, string $issuer): string
    {
        return sprintf(
            'otpauth://totp/%s%%3A%s?secret=%s&issuer=%s&algorithm=SHA1&digits=6&period=30',
            rawurlencode($issuer),
            rawurlencode($accountName),
            rawurlencode($secret),
            rawurlencode($issuer)
        );
    }

    public function generateSecret(): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';

        foreach (str_split(random_bytes(20)) as $byte) {
            $secret .= $alphabet[ord($byte) & 31];
        }

        return $secret;
    }

    public function verify(string $secret, string $code, ?int $timestamp = null): bool
    {
        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $counter = intdiv($timestamp ?? time(), 30);

        for ($offset = -1; $offset <= 1; $offset++) {
            if (hash_equals($this->code($secret, $counter + $offset), $code)) {
                return true;
            }
        }

        return false;
    }

    public function code(string $secret, int $counter): string
    {
        $binaryCounter = pack('N2', intdiv($counter, 4294967296), $counter % 4294967296);
        $hash = hash_hmac('sha1', $binaryCounter, $this->decodeBase32($secret), true);
        $offset = ord($hash[19]) & 15;
        $value = ((ord($hash[$offset]) & 127) << 24) | ((ord($hash[$offset + 1]) & 255) << 16)
            | ((ord($hash[$offset + 2]) & 255) << 8) | (ord($hash[$offset + 3]) & 255);

        return Str::padLeft((string) ($value % 1000000), 6, '0');
    }

    private function decodeBase32(string $secret): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';

        foreach (str_split(strtoupper($secret)) as $character) {
            $position = strpos($alphabet, $character);

            if ($position !== false) {
                $bits .= Str::padLeft(decbin($position), 5, '0');
            }
        }

        $decoded = '';

        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $decoded .= chr(bindec($byte));
            }
        }

        return $decoded;
    }
}
