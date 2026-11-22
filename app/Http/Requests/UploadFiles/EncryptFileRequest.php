<?php

namespace App\Http\Requests\UploadFiles;

use Illuminate\Foundation\Http\FormRequest;

class EncryptFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file'           => ['required','file', 'max:10240',],
            'password'       => ['required', 'string','min:12', ],
            'output_name'    => [ 'required', 'string', 'max:150','regex:/^[a-zA-Z0-9_-]+$/',],
        ];
    }
}
