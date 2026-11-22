<?php

namespace App\Services;

use RuntimeException;

class FileEncryptionService
{
    private const CIPHER            = 'aes-256-cbc';
    private const MAGIC             = 'SFV1';
    private const SALT_LENGTH       = 16;
    private const IV_LENGTH         = 16;
    private const HMAC_LENGTH       = 32;
    private const PBKDF2_ITERATIONS = 600000;

    public function encrypt(string $data, string $password): string
    {
        if ($password === '') {
            throw new RuntimeException('Password is required.');
        }

        // Generate a unique salt and IV for each encryption.
        $salt   = random_bytes(self::SALT_LENGTH);
        $iv     = random_bytes(self::IV_LENGTH);

        // Derive separate encryption and authentication keys.
        [$encryptionKey, $hmacKey] = $this->deriveKeys( $password, $salt );

        $encryptedData = openssl_encrypt( $data,self::CIPHER, $encryptionKey,  OPENSSL_RAW_DATA,  $iv );

        if ($encryptedData === false) {
            throw new RuntimeException('Encryption failed.');
        }

        // Format: MAGIC + SALT + IV + CIPHERTEXT + HMAC
        $payload    = self::MAGIC . $salt . $iv . $encryptedData;
        $hmac       = hash_hmac('sha256', $payload, $hmacKey, true);
        return $payload . $hmac;
    }

    public function decrypt(string $encryptedFile, string $password): string
    {
        if ($password === '') {
            throw new RuntimeException('Password is required.');
        }

        $headerLength = strlen(self::MAGIC) + self::SALT_LENGTH + self::IV_LENGTH;

        if (strlen($encryptedFile) < $headerLength + 16 + self::HMAC_LENGTH) {
            throw new RuntimeException('Invalid encrypted file.');
        }

        $magic = substr($encryptedFile, 0, strlen(self::MAGIC));

        if ($magic !== self::MAGIC) {
            throw new RuntimeException('Unsupported encrypted file format.');
        }

        $offset = strlen(self::MAGIC);

        $salt   = substr($encryptedFile, $offset, self::SALT_LENGTH);
        $offset += self::SALT_LENGTH;

        $iv     = substr($encryptedFile, $offset, self::IV_LENGTH);
        $offset += self::IV_LENGTH;

        $payload        = substr($encryptedFile, 0, -self::HMAC_LENGTH);
        $storedHmac     = substr($encryptedFile, -self::HMAC_LENGTH);

        [$encryptionKey, $hmacKey] = $this->deriveKeys( $password, $salt );

        // Verify integrity before decrypting.
        $calculatedHmac = hash_hmac( 'sha256', $payload, $hmacKey, true  );

        if (!hash_equals($storedHmac, $calculatedHmac)) {
            throw new RuntimeException(
                'Invalid password or corrupted file.'
            );
        }

        $ciphertext     = substr( $encryptedFile, $offset, -self::HMAC_LENGTH );
        $decryptedData  = openssl_decrypt( $ciphertext, self::CIPHER, $encryptionKey, OPENSSL_RAW_DATA, $iv);

        if ($decryptedData === false) {
            throw new RuntimeException('Decryption failed.');
        }
        return $decryptedData;
    }

    private function deriveKeys(string $password, string $salt): array
    {
        $derivedKey = hash_pbkdf2('sha256', $password,$salt, self::PBKDF2_ITERATIONS, 64, true );

        return [
            substr($derivedKey, 0, 32),
            substr($derivedKey, 32, 32),
        ];
    }
}
