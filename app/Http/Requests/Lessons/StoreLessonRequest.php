<?php

namespace App\Http\Requests\Lessons;

use App\Domain\Lessons\LessonSource;
use App\Domain\Lessons\Rules\PdfPageLimit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Envio de aula (MAT-02, MAT-07, UPL-01): texto colado ou PDF com texto.
 * O tipo é conferido pelo conteúdo real do arquivo, não pela extensão.
 */
class StoreLessonRequest extends FormRequest
{
    public const MAX_FILE_KILOBYTES = 10 * 1024;

    public const MAX_PAGES = 20;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'source' => ['required', Rule::enum(LessonSource::class)],
            'title' => ['required', 'string', 'max:120'],
            'lesson_date' => ['required', 'date', 'before_or_equal:tomorrow'],
            'rights' => ['accepted'],
            'text' => ['required_if:source,text', 'nullable', 'string', 'max:100000'],
            'pdf' => [
                'bail', 'required_if:source,pdf', 'nullable', 'file',
                'mimetypes:application/pdf', 'max:'.self::MAX_FILE_KILOBYTES,
                new PdfPageLimit(self::MAX_PAGES),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rights.accepted' => 'Para enviar, declare que tem direito de usar este material.',
            'pdf.mimetypes' => 'Este arquivo não é um PDF de verdade.',
            'pdf.max' => 'O arquivo passa de 10 MB.',
            'text.required_if' => 'Cole o texto da aula.',
            'pdf.required_if' => 'Escolha o PDF da aula.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['title' => 'título', 'lesson_date' => 'data da aula', 'text' => 'texto', 'pdf' => 'PDF'];
    }
}
