<?php

namespace App\Http\Requests\Settings;

use App\Domain\Plan\ReminderRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccountPreferencesRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'theme' => ['required', 'in:system,light,dark'],
            'notify_daily_reminder' => ['required', 'boolean'],
            // Hora do lembrete diário no fuso do aluno (J5-B10).
            'daily_reminder_hour' => ['sometimes', 'integer', Rule::in(ReminderRule::HOURS)],
            'notify_weekly_summary' => ['required', 'boolean'],
            'notify_streak' => ['required', 'boolean'],
        ];
    }
}
