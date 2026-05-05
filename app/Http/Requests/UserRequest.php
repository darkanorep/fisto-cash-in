<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
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
            'employee_id' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users')->ignore($this->user)
            ],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users')->ignore($this->user)
            ],
            'password' => ['nullable', 'string', 'min:6', 'max:255'],
            'role_id' => ['required', Rule::exists('roles', 'id')],
            'charge_id' => ['required', Rule::exists('charges', 'id')],
            'charge_name' => ['required', 'string', 'max:255'],
            'transaction_type' => [
                $this->isRequestorRole(),
                'array',
            ],
            'category' => [
                $this->isRequestorRole(),
                'array',
            ]
        ];
    }

    public function attributes()
    {
        return [
            'employee_id' => 'employee ID',
            'role_id' => 'role',
            'charge_id' => 'one charging',
            'charge_name' => 'one charging name',
        ];
    }

    public function messages()
    {
        return [
            'transaction_type.required' => 'The type field is required when the selected role is Requestor.',
            'transaction_type.array' => 'The type field must be an array.',
            'category.required' => 'The category field is required when the selected role is Requestor.',
            'category.array' => 'The category field must be an array.',
        ];
    }

    private function isRequestorRole()
    {
        return Rule::requiredIf(function () {
            $requestorRoleId = Role::query()
                ->where('name', Role::REQUESTOR)
                ->value('id');

            return $this->integer('role_id') === (int) $requestorRoleId;
        });
    }
}
