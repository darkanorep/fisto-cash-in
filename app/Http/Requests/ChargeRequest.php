<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChargeRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:255', Rule::unique('code')->ignore($this->charge)],
            'name' => ['required', 'string', 'max:255'],
            'company_code' => ['required', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'business_unit_code' => ['required', 'string', 'max:255'],
            'business_unit_name' => ['required', 'string', 'max:255'],
            'department_code' => ['required', 'string', 'max:255'],
            'department_name' => ['required', 'string', 'max:255'],
            'unit_code' => ['required', 'string', 'max:255'],
            'unit_name' => ['required', 'string', 'max:255'],
            'sub_unit_code' => ['required', 'string', 'max:255'],
            'sub_unit_name' => ['required', 'string', 'max:255'],
            'location_code' => ['required', 'string', 'max:255'],
            'location_name' => ['required', 'string', 'max:255'],
        ];
    }
}
