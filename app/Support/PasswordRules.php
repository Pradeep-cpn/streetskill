<?php

namespace App\Support;

class PasswordRules
{
    /**
     * @return array<int, string>
     */
    public static function rules(): array
    {
        return [
            'required',
            'string',
            'min:8',
            'max:12',
            'regex:/[a-z]/',
            'regex:/[A-Z]/',
            'regex:/[0-9]/',
            'regex:/[^a-zA-Z0-9]/',
            'confirmed',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'password.min' => 'Password must be at least 8 characters.',
            'password.max' => 'Password must be at most 12 characters.',
            'password.regex' => 'Password must include 1 uppercase, 1 lowercase, 1 number, and 1 special character.',
        ];
    }
}
