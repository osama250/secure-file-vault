<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Http\UploadedFile;

class FileEncryptionApiTest extends TestCase
{
    public function test_it_encrypts_an_uploaded_file(): void
    {
        $file = UploadedFile::fake()->createWithContent( 'test.txt',  'Hello Laravel Encryption!' );
        $response = $this->post('/api/files/encrypt', [
            'file'              => $file,
            'password'          => 'StrongPassword123!',
            'output_name'       => 'encrypted_test',
        ]);
        $response->assertOk();
        $response->assertHeader( 'Content-Type',  'application/octet-stream');
        $this->assertStringStartsWith( 'SFV1', $response->getContent() );
    }

    public function test_it_rejects_missing_password(): void
    {
        $file = UploadedFile::fake()->createWithContent('test.txt','Test Content' );
        $response = $this->postJson('/api/files/encrypt', [
            'file'              => $file,
            'password'          => '',
            'output_name'       => 'encrypted_test',
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    public function test_it_rejects_short_password(): void
    {
        $file = UploadedFile::fake()->createWithContent('test.txt', 'Test Content' );
        $response = $this->postJson('/api/files/encrypt', [
            'file'              => $file,
            'password'          => '12345',
            'output_name'       => 'encrypted_test',
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    public function test_it_rejects_missing_file(): void
    {
        $response = $this->postJson('/api/files/encrypt', [
            'password'          => 'StrongPassword123!',
            'output_name'       => 'encrypted_test',
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['file']);
    }


public function test_it_decrypts_an_encrypted_file(): void
{
    $original = 'Hello Osama, File Encryption Test!';
    $password = 'StrongPassword123!';

    $encrypted = app(\App\Services\FileEncryptionService::class)
        ->encrypt($original, $password);

    $file = \Illuminate\Http\UploadedFile::fake()
        ->createWithContent('test.enc', $encrypted);

    $response = $this->post('/api/files/decrypt', [
        'file' => $file,
        'password' => $password,
        'output_name' => 'restored.txt',
    ]);

    $response->assertOk();

    $this->assertSame($original, $response->getContent());

    $response->assertHeader(
        'Content-Type',
        'application/octet-stream'
    );
}

public function test_it_rejects_wrong_decryption_password(): void
{
    $encrypted = app(\App\Services\FileEncryptionService::class)
        ->encrypt('Secret File Content', 'CorrectPassword123!');

    $file = \Illuminate\Http\UploadedFile::fake()
        ->createWithContent('test.enc', $encrypted);

    $response = $this->post('/api/files/decrypt', [
        'file' => $file,
        'password' => 'WrongPassword123!',
        'output_name' => 'restored.txt',
    ]);

    $response->assertStatus(422);

    $this->assertSame(
        'Invalid password or corrupted file.',
        $response->getContent()
    );
}

    public function test_it_rejects_unencrypted_file(): void
    {
        $file = \Illuminate\Http\UploadedFile::fake()
                            ->createWithContent( 'invalid.txt', 'This is not an encrypted file.'  );

        $response = $this->post('/api/files/decrypt', [
            'file'          => $file,
            'password'      => 'StrongPassword123!',
            'output_name'   => 'restored.txt',
        ]);
        $response->assertStatus(422);
        $this->assertSame('Invalid password or corrupted file.', $response->getContent() );
    }



public function test_it_encrypts_and_decrypts_empty_file(): void
{
    $password = 'StrongPassword123!';

    $originalFile = \Illuminate\Http\UploadedFile::fake()
        ->createWithContent('empty.txt', '');

    $encryptResponse = $this->post('/api/files/encrypt', [
        'file' => $originalFile,
        'password' => $password,
        'output_name' => 'empty_encrypted',
    ]);

    $encryptResponse->assertOk();

    $encryptedContent = $encryptResponse->getContent();

    $encryptedFile = \Illuminate\Http\UploadedFile::fake()
        ->createWithContent('empty.enc', $encryptedContent);

    $decryptResponse = $this->post('/api/files/decrypt', [
        'file' => $encryptedFile,
        'password' => $password,
        'output_name' => 'restored.txt',
    ]);

    $decryptResponse->assertOk();

    $this->assertSame('', $decryptResponse->getContent());
}

    public function test_it_rejects_tampered_encrypted_file(): void
    {
        $password = 'StrongPassword123!';

        $encrypted = app(\App\Services\FileEncryptionService::class)
            ->encrypt('Important File Content', $password);

        $encrypted[40] = chr(ord($encrypted[40]) ^ 1);

        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('tampered.enc', $encrypted);

        $response = $this->post('/api/files/decrypt', [
            'file'          => $file,
            'password'      => $password,
            'output_name'   => 'restored.txt',
        ]);

        $response->assertStatus(422);
        $this->assertSame( 'Invalid password or corrupted file.', $response->getContent() );
    }


    public function test_it_rejects_unsafe_output_filename(): void
    {
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent(
                'test.txt',
                'Test Content'
            );

        $response = $this->postJson('/api/files/encrypt', [
            'file'          => $file,
            'password'      => 'StrongPassword123!',
            'output_name'   => '../secret',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['output_name']);
    }


    public function test_it_rejects_file_larger_than_10mb(): void
    {
        $file = \Illuminate\Http\UploadedFile::fake()->create('large-file.txt', 10241);

        $response = $this->postJson('/api/files/encrypt', [
            'file'          => $file,
            'password'      => 'StrongPassword123!',
            'output_name'   => 'large_encrypted',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['file']);
    }


    public function test_it_encrypts_and_decrypts_file_at_maximum_size(): void
    {
        $password           = 'StrongPassword123!';
        $originalContent    = str_repeat('A', 10 * 1024 * 1024);

        $file = \Illuminate\Http\UploadedFile::fake()
            ->createWithContent('maximum.txt', $originalContent);

        // Encrypt the file through the API.
        $encryptResponse = $this->post('/api/files/encrypt', [
            'file'              => $file,
            'password'          => $password,
            'output_name'       => 'maximum_encrypted',
        ]);

        $encryptResponse->assertOk();
        $encryptedContent = $encryptResponse->getContent();

        // The encrypted file contains additional metadata.
        $this->assertGreaterThan( strlen($originalContent),strlen($encryptedContent));

        $encryptedFile = \Illuminate\Http\UploadedFile::fake()
            ->createWithContent('maximum.enc', $encryptedContent);

        // Decrypt the encrypted file through the API.
        $decryptResponse = $this->post('/api/files/decrypt', [
            'file'                  => $encryptedFile,
            'password'              => $password,
            'output_name'           => 'restored.txt',
        ]);

        $decryptResponse->assertOk();
        $this->assertSame( hash('sha256', $originalContent),hash('sha256', $decryptResponse->getContent()) );
    }


}
