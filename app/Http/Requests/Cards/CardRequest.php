<?php

namespace App\Http\Requests\Cards;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Cartão criado ou editado pelo aluno (FLA-01, FLA-06).
 */
class CardRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'front' => ['required', 'string', 'max:200'],
            'back' => ['required', 'string', 'max:300'],
            'example' => ['nullable', 'string', 'max:300'],
            'example_translation' => ['nullable', 'string', 'max:300'],
            'lesson' => ['nullable', 'string', 'size:26'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['front' => 'frente', 'back' => 'verso', 'example' => 'exemplo', 'example_translation' => 'tradução do exemplo', 'lesson' => 'aula'];
    }

    /**
     * @return array{front: string, back: string, example: string|null, example_translation: string|null}
     */
    public function content(): array
    {
        return [
            'front' => $this->string('front')->value(),
            'back' => $this->string('back')->value(),
            'example' => $this->filled('example') ? $this->string('example')->value() : null,
            'example_translation' => $this->filled('example_translation') ? $this->string('example_translation')->value() : null,
        ];
    }
}
