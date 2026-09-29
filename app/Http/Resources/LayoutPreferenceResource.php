<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LayoutPreferenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'sidebar_mode' => $this->sidebar_mode ?? 'default',
            'menu_behavior' => $this->menu_behavior ?? 'click',
            'content_width' => $this->content_width ?? 'default',
            'direction' => $this->direction ?? 'ltr',
            'sidebar_visibility' => $this->sidebar_visibility ?? 'visible',
            'sidebar_state' => $this->sidebar_state ?? 'expanded',
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
