<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
class LoginRequest extends FormRequest
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
            'type' => 'required|string|in:google,email',
            'email' => 'required_if:type,email|email',
            'password' => 'required_if:type,email|string',
            'google_token' => 'required_if:type,google|string',
        ];
    }
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(error('Validation error occurred', $validator->errors(), 422));
    }
}
