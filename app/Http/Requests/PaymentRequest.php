<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class PaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth + ownership checked in controller
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'method' => ['required', new Enum(PaymentMethod::class)],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'method' => 'método de pago',
        ];
    }
}
