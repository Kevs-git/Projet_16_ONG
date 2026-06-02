<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'goal_amount' => 'required|numeric|min:0',
            'collected_amount' => 'nullable|numeric|min:0',
            'image' => 'nullable|string|max:255',
            'category_id' => 'required|exists:categories,id',
        ];
    }
}
