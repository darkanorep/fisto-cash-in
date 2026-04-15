<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PermissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'array',
                'min:1'
            ],
            'name.*' => [
                'string',
                'max:255',
//                'distinct',
//                'unique:permissions,name'
            ],
            'role_id' => [
                'nullable',
                'exists:roles,id'
            ]
        ];
    }

    public function attributes()
    {
        return [
            'role_id' => 'role'
        ];
    }
}
