<?php

namespace Tests\Unit;

use App\Services\FileEncryptionService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class FileEncryptionServiceTest extends TestCase
{
    private FileEncryptionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FileEncryptionService();
    }

    public function test_it_encrypts_and_decrypts_data(): void
    {
        $original = 'Hello Laravel Encryption!';
        $password = 'StrongPassword123!';

        $encrypted = $this->service->encrypt( $original, $password );
        $decrypted = $this->service->decrypt( $encrypted, $password  );

        $this->assertNotEquals($original, $encrypted);
        $this->assertSame($original, $decrypted);
    }

    public function test_it_rejects_wrong_password(): void
    {
        $encrypted = $this->service->encrypt('Secret Data', 'CorrectPassword123!');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid password or corrupted file.');
        $this->service->decrypt( $encrypted,'WrongPassword123!');
    }

    public function test_it_detects_tampered_data(): void
    {
        $password   = 'StrongPassword123!';
        $encrypted  = $this->service->encrypt('Important Information',   $password);

        $encrypted[40] = chr(ord($encrypted[40]) ^ 1);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid password or corrupted file.');
        $this->service->decrypt($encrypted, $password);
    }

    public function test_it_rejects_invalid_file_format(): void
    {
        $this->expectException(RuntimeException::class);
        $this->service->decrypt('This is not an encrypted file.',  'StrongPassword123!');
    }

    public function test_it_generates_different_ciphertexts(): void
    {
        $original = 'Same Content';
        $password = 'StrongPassword123!';
        $first      = $this->service->encrypt($original, $password);
        $second     = $this->service->encrypt($original, $password);
        $this->assertNotSame($first, $second);
    }
}
