<?php

namespace App\Http\Requests\UploadFiles;

use Illuminate\Foundation\Http\FormRequest;

class DecryptFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file'          => [ 'required', 'file', 'max:10241',],
            'password'      => [ 'required', 'string',],
            'output_name'   => [ 'required', 'string','max:150','regex:/^[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)*$/',],
        ];
    }
}
