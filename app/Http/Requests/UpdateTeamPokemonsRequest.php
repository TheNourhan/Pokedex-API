<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Team;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateTeamPokemonsRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'pokemons' => [
                'required',
                'array',
                'max:' . Team::MAX_POKEMONS,
            ],
            'pokemons.*' => [
                'required',
                'integer',
                'distinct',
                'exists:pokemons,external_id',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'pokemons.required' => 'Pokemons list is required',
            'pokemons.array' => 'Pokemons must be an array',
            'pokemons.max' => 'Team cannot have more than ' . Team::MAX_POKEMONS . ' pokemons',
            'pokemons.*.required' => 'Each pokemon ID is required',
            'pokemons.*.integer' => 'Each pokemon ID must be an integer',
            'pokemons.*.distinct' => 'Duplicate pokemon IDs are not allowed',
            'pokemons.*.exists' => 'Pokemon with ID :input does not exist',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'error' => 'Validation Error',
                'error_message' => $validator->errors()->first(),
            ], 422)
        );
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('pokemons')) {
            // Remove duplicates and re-index
            $this->merge([
                'pokemons' => array_values(array_unique($this->pokemons)),
            ]);
        }
    }
}