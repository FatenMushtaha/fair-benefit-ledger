<?php

namespace App\Http\Requests;

use App\Enums\UserStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => Str::lower(trim((string) $this->input('email'))),
        ]);
    }

    public function authenticate(): void
    {
        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))
            || Auth::user()?->status !== UserStatus::Active) {
            Auth::logout();
            $this->session()->invalidate();
            $this->session()->regenerateToken();

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        $this->session()->regenerate();
    }
}
