<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth middleware already guards the route
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'customer_name'   => ['required', 'string', 'max:120'],
            'phone'           => ['required', 'string', 'max:30'],
            'address'         => ['required', 'string', 'max:200'],
            'city'            => ['required', 'string', 'max:80'],
            'delivery_method' => ['required', 'in:pickup,standard,express'],
            'delivery_notes'  => ['nullable', 'string', 'max:500'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'customer_name'   => 'nombre del destinatario',
            'phone'           => 'teléfono',
            'address'         => 'dirección',
            'city'            => 'ciudad/cantón',
            'delivery_method' => 'modalidad de entrega',
            'delivery_notes'  => 'notas de entrega',
        ];
    }
}
