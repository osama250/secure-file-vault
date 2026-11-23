
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Secure File Vault</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        body {
            min-height: 100vh;
            background: #0b1120;
            color: #e2e8f0;
            font-family: Arial, sans-serif;
        }

        .vault-card {
            max-width: 680px;
            margin: 70px auto;
            background: #151e30;
            border: 1px solid #2b3950;
            border-radius: 18px;
            padding: 32px;
        }

        .subtitle {
            color: #94a3b8;
        }

        .upload-area {
            border: 2px dashed #475569;
            border-radius: 12px;
            padding: 28px;
            text-align: center;
            background: #111827;
        }

        .file-details {
            background: #0f172a;
            border-radius: 10px;
            padding: 16px;
            margin-top: 16px;
        }

        .form-control {
            background: #0f172a;
            color: #fff;
            border-color: #334155;
        }

        .form-control:focus {
            background: #0f172a;
            color: #fff;
            border-color: #6366f1;
            box-shadow: none;
        }

        .form-control::placeholder {
            color: #64748b;
        }

        .form-control::file-selector-button {
            background: #334155;
            color: white;
            border: 0;
        }

        .form-control:hover::file-selector-button {
            background: #475569;
            color: white;
        }

        .form-label {
            font-weight: 600;
        }

        .operation-btn {
            width: 50%;
            padding: 12px;
        }

        .operation-btn.active {
            background: #4f46e5;
            color: #fff;
            border-color: #4f46e5;
        }

        .operation-btn:not(.active) {
            background: #0f172a;
            color: #cbd5e1;
            border-color: #334155;
        }

        .submit-btn {
            background: #4f46e5;
            border: none;
            padding: 13px;
            font-weight: bold;
        }

        .submit-btn:hover {
            background: #4338ca;
        }

        .submit-btn:disabled {
            background: #475569;
        }

        @media (max-width: 576px) {
            .vault-card {
                margin: 25px auto;
                padding: 20px;
            }
        }
    </style>
</head>

<body>

<div class="container">
    <div class="vault-card">

        <div class="text-center mb-4">
            <h2 class="fw-bold">Secure File Vault</h2>
            <p class="subtitle">
                Secure File Encryption & Decryption
            </p>
        </div>

        <div class="btn-group w-100 mb-4" role="group">
            <button
                type="button"
                class="btn operation-btn active"
                id="encryptBtn"
            >
                Encrypt
            </button>

            <button
                type="button"
                class="btn operation-btn"
                id="decryptBtn"
            >
                Decrypt
            </button>
        </div>

        <form id="fileForm">

            <div class="upload-area mb-3">
                <label for="fileInput" class="form-label">
                    Select a file
                </label>

                <input
                    type="file"
                    class="form-control"
                    id="fileInput"
                    name="file"
                    required
                >

                <small class="subtitle d-block mt-2">
                    Maximum file size: 10 MiB
                </small>
            </div>

            <div id="fileDetails" class="file-details d-none mb-3">
                <h6 class="mb-3">File Information</h6>

                <p>
                    <strong>Name:</strong>
                    <span id="fileName"></span>
                </p>

                <p>
                    <strong>Size:</strong>
                    <span id="fileSize"></span>
                </p>

                <p class="mb-0">
                    <strong>Extension:</strong>
                    <span id="fileExtension"></span>
                </p>
            </div>

            <div class="mb-3">
                <label for="passwordInput" class="form-label">
                    Password
                </label>

                <input
                    type="password"
                    class="form-control"
                    id="passwordInput"
                    name="password"
                    minlength="12"
                    autocomplete="new-password"
                    required
                >

                <small id="passwordHint" class="subtitle">
                    Minimum 12 characters
                </small>
            </div>

            <div class="mb-4">
                <label for="outputName" class="form-label">
                    Output File Name
                </label>

                <input
                    type="text"
                    class="form-control"
                    id="outputName"
                    name="output_name"
                    placeholder="Enter output file name"
                    maxlength="150"
                    required
                >
            </div>

            <div
                id="message"
                class="alert d-none"
                role="alert"
                aria-live="polite"
            ></div>

            <button
                type="submit"
                class="btn btn-primary submit-btn w-100"
                id="submitBtn"
            >
                Encrypt & Download
            </button>

        </form>

        <p class="subtitle text-center mt-4 mb-0">
            AES-256-CBC Encryption
        </p>

    </div>
</div>

<script>
    const form = document.getElementById('fileForm');
    const fileInput = document.getElementById('fileInput');
    const passwordInput = document.getElementById('passwordInput');
    const outputInput = document.getElementById('outputName');
    const submitBtn = document.getElementById('submitBtn');

    const encryptBtn = document.getElementById('encryptBtn');
    const decryptBtn = document.getElementById('decryptBtn');

    const fileDetails = document.getElementById('fileDetails');
    const message = document.getElementById('message');
    const passwordHint = document.getElementById('passwordHint');

    let operation = 'encrypt';

    // Display success or error messages.
    function showMessage(text, type = 'danger') {
        message.textContent = text;
        message.className = `alert alert-${type}`;
    }

    function clearMessage() {
        message.textContent = '';
        message.className = 'alert d-none';
    }

    // Clear form values without refreshing the page.
    function resetFileForm() {
        form.reset();

        // Clear selected file and its displayed information.
        fileInput.value = '';
        fileDetails.classList.add('d-none');

        document.getElementById('fileName').textContent = '';
        document.getElementById('fileSize').textContent = '';
        document.getElementById('fileExtension').textContent = '';

        // Clear password and output filename.
        passwordInput.value = '';
        outputInput.value = '';
    }

    // Switch between encryption and decryption.
    function setOperation(type) {
        operation = type;

        encryptBtn.classList.toggle(
            'active',
            type === 'encrypt'
        );

        decryptBtn.classList.toggle(
            'active',
            type === 'decrypt'
        );

        submitBtn.textContent = type === 'encrypt'
            ? 'Encrypt & Download'
            : 'Decrypt & Download';

        passwordInput.minLength =
            type === 'encrypt' ? 12 : 1;

        passwordHint.textContent = type === 'encrypt'
            ? 'Minimum 12 characters'
            : 'Enter the original encryption password';

        clearMessage();
        updateFileDetails();
    }

    encryptBtn.addEventListener('click', () => {
        setOperation('encrypt');
    });

    decryptBtn.addEventListener('click', () => {
        setOperation('decrypt');
    });

    // Show the selected file's information.
    function updateFileDetails() {
        const file = fileInput.files[0];

        if (!file) {
            fileDetails.classList.add('d-none');
            outputInput.value = '';
            return;
        }

        fileDetails.classList.remove('d-none');

        document.getElementById('fileName').textContent =
            file.name;

        document.getElementById('fileSize').textContent =
            (file.size / 1024 / 1024).toFixed(2) + ' MiB';

        const extension = file.name.includes('.')
            ? file.name.split('.').pop()
            : 'None';

        document.getElementById('fileExtension').textContent =
            extension;

        const baseName = file.name.replace(/\.[^/.]+$/, '');

        const safeName = baseName
            .replace(/[^a-zA-Z0-9_-]/g, '_')
            .slice(0, 140) || 'file';

        outputInput.value = operation === 'encrypt'
            ? safeName + '_encrypted'
            : safeName.replace(/_encrypted$/, '') + '.txt';
    }

    fileInput.addEventListener('change', () => {
        clearMessage();
        updateFileDetails();
    });

    // Extract download filename from the response.
    function getDownloadName(disposition, fallback) {
        if (!disposition) {
            return fallback;
        }

        const match = disposition.match(
            /filename="([^"]+)"/i
        );

        return match ? match[1] : fallback;
    }

    // Submit the file to Laravel.
    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        clearMessage();

        const file = fileInput.files[0];

        if (!file) {
            showMessage('Please select a file.');
            return;
        }

        const maxBytes = operation === 'encrypt'
            ? 10 * 1024 * 1024
            : 10 * 1024 * 1024 + 1024;

        if (file.size > maxBytes) {
            showMessage('File exceeds the allowed size.');
            return;
        }

        const formData = new FormData();

        formData.append('file', file);
        formData.append('password', passwordInput.value);
        formData.append('output_name', outputInput.value);

        submitBtn.disabled = true;
        submitBtn.textContent = 'Processing...';

        try {
            const endpoint = operation === 'encrypt'
                ? '/api/files/encrypt'
                : '/api/files/decrypt';

            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json'
                },
                body: formData
            });

            // Handle validation and processing errors.
            if (!response.ok) {
                const contentType =
                    response.headers.get('Content-Type') || '';

                let errorMessage = 'Operation failed.';

                if (contentType.includes('application/json')) {
                    const error = await response.json();

                    errorMessage =
                        error.message || errorMessage;

                    if (error.errors) {
                        errorMessage = Object.values(
                            error.errors
                        ).flat().join(' ');
                    }
                } else {
                    errorMessage = await response.text();
                }

                throw new Error(errorMessage);
            }

            // Read the binary file returned by Laravel.
            const blob = await response.blob();

            const fallback = operation === 'encrypt'
                ? outputInput.value + '.enc'
                : outputInput.value;

            const downloadName = getDownloadName(
                response.headers.get('Content-Disposition'),
                fallback
            );

            // Create a temporary download link.
            const url = URL.createObjectURL(blob);

            const link = document.createElement('a');
            link.href = url;
            link.download = downloadName;

            document.body.appendChild(link);
            link.click();
            link.remove();

            // Release the temporary object URL.
            setTimeout(() => {
                URL.revokeObjectURL(url);
            }, 1000);

            // NEW: Clear the form after successful processing.
            resetFileForm();

            showMessage(
                'File processed successfully. Download started.',
                'success'
            );

        } catch (error) {
            // Keep the form values if processing fails.
            showMessage(error.message);

        } finally {
            submitBtn.disabled = false;

            submitBtn.textContent = operation === 'encrypt'
                ? 'Encrypt & Download'
                : 'Decrypt & Download';
        }
    });
</script>

</body>
</html>
