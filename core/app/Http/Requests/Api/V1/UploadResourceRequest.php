<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UploadResourceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['string', 'nullable'],
            'file' => ['file', 'nullable', 'required_without:data'],
            'data' => ['string', 'nullable', 'required_without:file'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
