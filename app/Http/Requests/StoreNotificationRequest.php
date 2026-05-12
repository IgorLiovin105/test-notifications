<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNotificationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => 'required|int|exists:users,id',
            'message' => 'required|string|max:500',
            'channel' => 'required|in:email,telegram',
        ];
    }
}
