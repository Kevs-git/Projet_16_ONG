<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCampaignRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'goal_amount' => 'sometimes|required|numeric|min:0',
            'collected_amount' => 'sometimes|numeric|min:0',
            'image' => 'sometimes|nullable|string|max:255',
            'category_id' => 'sometimes|required|exists:categories,id',
        ];
    }
}
