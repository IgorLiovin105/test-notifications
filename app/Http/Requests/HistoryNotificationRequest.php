<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HistoryNotificationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => 'required|int|exists:users,id',
            'status' => 'nullable|in:pending,sent,failed',
            'channel' => 'nullable|in:email,telegram',
        ];
    }
}
