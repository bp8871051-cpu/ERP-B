<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLayoutPreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'sidebar_mode' => 'sometimes|string|in:default,mini,hover,hidden',
            'menu_behavior' => 'sometimes|string|in:click,hover',
            'content_width' => 'sometimes|string|in:default,full',
            'direction' => 'sometimes|string|in:ltr,rtl',
            'sidebar_visibility' => 'sometimes|string|in:visible,hidden',
            'sidebar_state' => 'sometimes|string|in:expanded,collapsed',
        ];
    }

    public function messages(): array
    {
        return [
            'sidebar_mode.in' => 'The sidebar mode must be one of: default, mini, hover, hidden.',
            'menu_behavior.in' => 'The menu behavior must be either click or hover.',
            'content_width.in' => 'The content width must be either default or full.',
            'direction.in' => 'The direction must be either ltr or rtl.',
            'sidebar_visibility.in' => 'The sidebar visibility must be either visible or hidden.',
            'sidebar_state.in' => 'The sidebar state must be either expanded or collapsed.',
        ];
    }
}
