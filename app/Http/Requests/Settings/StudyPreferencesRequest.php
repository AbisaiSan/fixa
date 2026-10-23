<?php

namespace App\Http\Requests\Settings;

use App\Models\Enrollment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudyPreferencesRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'daily_goal_minutes' => ['required', 'integer', 'between:'.Enrollment::MIN_DAILY_GOAL.','.Enrollment::MAX_DAILY_GOAL],
            'target_language' => ['required', 'string', Rule::in(config('fixa.available_languages'))],
            'timezone' => ['required', 'string', 'timezone:all'],
        ];
    }
}
