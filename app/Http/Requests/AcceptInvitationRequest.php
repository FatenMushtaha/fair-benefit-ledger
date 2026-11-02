<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

class AcceptInvitationRequest extends FormRequest
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
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255', 'confirmed'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => Str::lower(trim((string) $this->input('email'))),
        ]);
    }

    /**
     * New accounts need a name and the stronger password policy. Existing
     * accounts must use their current password, whose legacy length is allowed.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('email')) {
                    return;
                }

                $isNewAccount = User::where('email', (string) $this->input('email'))->doesntExist();

                if (! $isNewAccount) {
                    return;
                }

                if (trim((string) $this->input('name')) === '') {
                    $validator->errors()->add('name', __('validation.required', ['attribute' => __('ui.fields.name')]));
                }

                if (mb_strlen((string) $this->input('password')) < 12) {
                    $validator->errors()->add('password', __('validation.min.string', [
                        'attribute' => __('ui.fields.password'),
                        'min' => 12,
                    ]));
                }
            },
        ];
    }
}
