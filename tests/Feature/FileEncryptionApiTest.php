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



}
