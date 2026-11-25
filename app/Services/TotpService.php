<?php

namespace App\Services;

class TotpService
{
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    // Generate a 160-bit (32-char) Base32 secret key
    public function generateSecret(int $length = 32): string
    {
        $bytes = random_bytes(20);
        $secret = '';
        $buffer = 0;
        $bitsLeft = 0;

        for ($i = 0; $i < strlen($bytes); $i++) {
            $buffer = ($buffer << 8) | ord($bytes[$i]);
            $bitsLeft += 8;

            while ($bitsLeft >= 5) {
                $bitsLeft -= 5;
                $index = ($buffer >> $bitsLeft) & 0x1F;
                $secret .= self::BASE32_ALPHABET[$index];
            }
        }

        return substr($secret, 0, $length);
    }

    // Build otpauth:// URI for Microsoft Authenticator QR scanning
    public function getProvisioningUri(string $issuer, string $accountLabel, string $secret): string
    {
        $encodedIssuer = rawurlencode($issuer);
        $encodedLabel = rawurlencode($accountLabel);

        return "otpauth://totp/{$encodedIssuer}:{$encodedLabel}?secret={$secret}&issuer={$encodedIssuer}&algorithm=SHA1&digits=6&period=30";
    }

    // Verify a 6-digit TOTP code (allows +/- 30s clock drift via $window = 1)
    public function verifyCode(string $secret, string $code, int $window = 1): bool
    {
        $cleanCode = preg_replace('/\s+/', '', trim($code));
        if (!preg_match('/^\d{6}$/', $cleanCode)) {
            return false;
        }

        $currentSlice = (int) floor(time() / 30);

        for ($offset = -$window; $offset <= $window; $offset++) {
            $calculated = $this->calculateCode($secret, $currentSlice + $offset);
            if (hash_equals($calculated, $cleanCode)) {
                return true;
            }
        }

        return false;
    }

    private function calculateCode(string $secret, int $timeSlice): string
    {
        $secretKey = $this->base32Decode($secret);

        $high = ($timeSlice >> 32) & 0xFFFFFFFF;
        $low = $timeSlice & 0xFFFFFFFF;
        $timeBytes = pack('NN', $high, $low);

        $hmac = hash_hmac('sha1', $timeBytes, $secretKey, true);
        $offset = ord(substr($hmac, -1)) & 0x0F;
        $part = substr($hmac, $offset, 4);

        $value = unpack('N', $part)[1] & 0x7FFFFFFF;
        $otp = $value % 1000000;

        return str_pad((string) $otp, 6, '0', STR_PAD_LEFT);
    }

    private function base32Decode(string $secret): string
    {
        $clean = strtoupper(preg_replace('/[^A-Z2-7]/i', '', $secret));
        $buffer = 0;
        $bitsLeft = 0;
        $output = '';

        for ($i = 0; $i < strlen($clean); $i++) {
            $val = strpos(self::BASE32_ALPHABET, $clean[$i]);
            if ($val === false) {
                continue;
            }

            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $output;
    }
}