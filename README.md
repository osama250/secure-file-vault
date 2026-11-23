# Secure File Vault

A secure web-based file encryption and decryption application built with PHP 8.2+, Laravel 12, and OpenSSL.

This project was developed as a Laravel Backend Technical Assessment. It allows users to upload files, view file information, encrypt files using AES-256-CBC, and decrypt previously encrypted files using a password.

## Features

- Encrypt and decrypt files through a web interface.
- AES-256-CBC encryption using PHP OpenSSL.
- Password-based key derivation using PBKDF2-HMAC-SHA256.
- Random salt and initialization vector (IV) for every encryption.
- HMAC-SHA256 integrity verification before decryption.
- File information display: name, size, and extension.
- Custom output filename.
- Binary file download without storing processed files permanently.
- Input validation using Laravel Form Requests.
- Custom exception handling for invalid encrypted files.
- API rate limiting.
- Responsive Blade and Bootstrap interface.
- Automatic form reset after successful processing.
- Automated Unit and Feature Tests.

## Technology Stack

| Technology | Purpose |
|---|---|
| PHP 8.2+ | Backend language |
| Laravel 12 | Web application framework |
| OpenSSL | Encryption and decryption |
| Blade | Server-rendered interface |
| Bootstrap 5 | Responsive UI |
| JavaScript Fetch API | Asynchronous file uploads and downloads |
| PHPUnit | Automated testing |

## Installation

### Requirements

- PHP 8.2 or higher
- Composer
- OpenSSL PHP extension
- Git
- A modern web browser

### Setup

Clone the repository:

```bash
git clone https://github.com/osama250/secure-file-vault.git
cd secure-file-vault
```

Install dependencies:

```bash
composer install
```

Create the environment file:

```bash
cp .env.example .env
```

Generate the Laravel application key:

```bash
php artisan key:generate
```

Start the development server:

```bash
php artisan serve
```

Open:

`http://127.0.0.1:8000`

The encryption and decryption workflow does not require a database.

## How to Use

### Encrypt a File

1. Open the application.
2. Select **Encrypt**.
3. Choose a file from your computer.
4. Review its name, size, and extension.
5. Enter a password containing at least 12 characters.
6. Specify the output filename.
7. Click **Encrypt & Download**.
8. Save the generated `.enc` file.

### Decrypt a File

1. Select **Decrypt**.
2. Upload a file previously encrypted by Secure File Vault.
3. Enter its original encryption password.
4. Specify the output filename, including the appropriate extension.
5. Click **Decrypt & Download**.
6. Save the restored file.

The application does not store or recover user passwords. Losing the encryption password may make the encrypted file unrecoverable.

## Encryption Architecture

The application separates HTTP handling, validation, and cryptographic operations.

```text
Browser / API Client
        |
        v
Laravel Route
        |
        v
Form Request Validation
        |
        v
FileController
        |
        v
FileEncryptionService
        |
        v
PHP OpenSSL
        |
        v
Binary Download Response
```

### Main Components

**FileController**

Handles uploaded files, delegates cryptographic operations to the service, and returns downloadable binary responses.

**EncryptFileRequest / DecryptFileRequest**

Validate uploaded files, passwords, file sizes, and output filenames.

**FileEncryptionService**

Contains the encryption and decryption logic independently of HTTP handling.

**InvalidEncryptedFileException**

Distinguishes invalid encrypted files and authentication failures from unexpected application errors.

## Cryptographic Design

### Encryption

1. Accept the uploaded file and password.
2. Generate a random 16-byte salt.
3. Generate a random 16-byte IV.
4. Derive 64 bytes using PBKDF2-HMAC-SHA256 with 600,000 iterations.
5. Split the derived material into a 32-byte encryption key and a 32-byte HMAC key.
6. Encrypt the file content using AES-256-CBC.
7. Construct the encrypted file payload.
8. Calculate HMAC-SHA256 over the payload.
9. Append the HMAC and return the encrypted file.

### Encrypted File Format

The application uses the following binary format:

```text
MAGIC | SALT | IV | CIPHERTEXT | HMAC
```

| Component | Size |
|---|---|
| Magic header (`SFV1`) | 4 bytes |
| Salt | 16 bytes |
| IV | 16 bytes |
| Ciphertext | Variable |
| HMAC-SHA256 | 32 bytes |

The minimum encrypted file size is 84 bytes.

### Decryption

1. Validate the encrypted file format and minimum length.
2. Extract the salt, IV, ciphertext, and stored HMAC.
3. Derive the encryption and HMAC keys from the supplied password.
4. Recalculate HMAC-SHA256.
5. Compare authentication tags using `hash_equals()`.
6. Reject invalid or modified data.
7. Decrypt the ciphertext using AES-256-CBC.
8. Return the restored file.

The application uses an Encrypt-then-MAC approach to detect encrypted-file modification before attempting decryption.

## API Endpoints

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/files/encrypt` | Encrypt an uploaded file |
| POST | `/api/files/decrypt` | Decrypt an encrypted file |

Both endpoints accept `multipart/form-data`.

### Encrypt Request

| Parameter | Type | Description |
|---|---|---|
| `file` | File | File to encrypt |
| `password` | String | Password, minimum 12 characters |
| `output_name` | String | Output filename without `.enc` |

### Decrypt Request

| Parameter | Type | Description |
|---|---|---|
| `file` | File | Encrypted file |
| `password` | String | Original encryption password |
| `output_name` | String | Restored filename with extension |

Successful requests return a binary file using `application/octet-stream` and `Content-Disposition: attachment`.

Invalid input or encrypted-file errors return HTTP 422. Exceeding the configured API rate limit returns HTTP 429.

## File Size Limits

The application uses the following upload validation limits:

- Encryption: 10 MiB.
- Decryption: 10,241 KiB, allowing for encryption overhead.

PHP upload and POST limits must also support these request sizes.

## Testing

Run the complete test suite:

```bash
php artisan test
```

Latest local test result:

**19 tests passed.**

Tests cover encryption/decryption, wrong passwords, modified ciphertext, invalid file formats, upload validation, unsafe filenames, empty files, and file-size boundaries.

Manual testing was also performed using Postman and Laravel Tinker, including SHA-256 comparisons between original and restored files.

## Performance

Local benchmarking was performed using a 1 MiB test payload.

| Operation | Measured Time |
|---|---:|
| Full encryption | 1.807 seconds |
| PBKDF2 key derivation | 1.774 seconds |
| AES-256-CBC encryption | 0.002037 seconds |

The measurements indicate that password-based key derivation is the dominant computational cost in this local test.

Peak PHP memory usage observed during the Tinker session was approximately 60 MiB, including the Laravel and Tinker environments.

These results are single-run local measurements and should not be interpreted as production benchmarks.

### Design Tradeoffs

The application currently processes uploaded file contents in memory. A conservative upload-size limit is used to reduce memory consumption.

For larger workloads, a future implementation could use carefully designed streamed encryption and authentication, along with additional performance and security testing.

## Security Considerations

- Random salt and IV for each encryption operation.
- Separate encryption and HMAC keys.
- HMAC verification before decryption.
- Constant-time HMAC comparison with `hash_equals()`.
- Validation of uploaded file sizes and output filenames.
- HTTP rate limiting.
- No permanent storage of processed files.
- Binary responses use `Cache-Control: no-store`.
- Decryption failures return a generic error message.

For deployment, configure `APP_DEBUG=false`, use HTTPS, protect environment secrets, and review server capacity, rate limits, and logging.

This project is a technical assessment implementation and has not undergone an independent security audit.

## Known Limitations

- The encryption workflow uses a 10 MiB upload limit.
- File contents are processed in memory.
- The original filename and extension are not stored inside the encrypted payload.
- The user must provide the correct original password and desired output filename for decryption.
- Browser download location selection depends on browser capabilities and settings.
- The custom `SFV1` format is application-specific and is not intended as a general-purpose encryption standard.

## Technical References

- PHP OpenSSL functions: https://www.php.net/manual/en/ref.openssl.php
- PHP `hash_pbkdf2()`: https://www.php.net/manual/en/function.hash-pbkdf2.php
- PHP `hash_hmac()`: https://www.php.net/manual/en/function.hash-hmac.php
- PHP `hash_equals()`: https://www.php.net/manual/en/function.hash-equals.php
- Laravel 12 Validation: https://laravel.com/docs/12.x/validation
- Laravel 12 HTTP Responses: https://laravel.com/docs/12.x/responses
- Laravel 12 Testing: https://laravel.com/docs/12.x/testing
- Laravel 12 Rate Limiting: https://laravel.com/docs/12.x/rate-limiting
- NIST SP 800-38A — Block Cipher Modes of Operation: https://csrc.nist.gov/pubs/sp/800/38/a/final
- OWASP Cryptographic Storage Cheat Sheet: https://cheatsheetseries.owasp.org/cheatsheets/Cryptographic_Storage_Cheat_Sheet.html

## License

This project was developed for a technical assessment and demonstration purposes.
