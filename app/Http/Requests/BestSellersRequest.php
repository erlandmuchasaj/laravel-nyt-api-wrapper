<?php

namespace App\Http\Requests;

use App\Rules\Isbn;
use Illuminate\Foundation\Http\FormRequest;

class BestSellersRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->guest();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'author' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'offset' => [
                'nullable',
                'integer',
                'min:0',
                function (string $attribute, int $value, callable $fail) {
                    if ($value % 20 !== 0) {
                        $fail($attribute.' must be a multiple of 20.');
                    }
                },
            ],
            'isbn' => ['sometimes', 'array'],
            'isbn.*' => ['required', 'string', new Isbn],
            'cache' => ['sometimes', 'boolean'],
        ];
    }
}
