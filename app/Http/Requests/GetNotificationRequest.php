<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetNotificationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => 'required|int|exists:users,id',
        ];
    }
}
