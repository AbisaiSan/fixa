<?php

namespace App\Http\Requests\Lessons;

use Illuminate\Foundation\Http\FormRequest;

/**
 * O aluno pode editar título e data da aula (MAT-01).
 */
class UpdateLessonRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'lesson_date' => ['required', 'date', 'before_or_equal:tomorrow'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['title' => 'título', 'lesson_date' => 'data da aula'];
    }
}
