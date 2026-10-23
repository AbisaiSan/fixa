<?php

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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
            'notify_weekly_summary' => ['required', 'boolean'],
            'notify_streak' => ['required', 'boolean'],
        ];
    }
}
