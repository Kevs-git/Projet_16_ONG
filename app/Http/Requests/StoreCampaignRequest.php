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
            'category' => 'nullable|string|max:255',
            'image_url' => 'nullable|string|max:255',
            'montant_collecte' => 'nullable|integer|min:0',
            'montant_objectif' => 'nullable|integer|min:0',
            'goal_amount' => 'nullable|numeric|min:0',
            'collected_amount' => 'nullable|numeric|min:0',
            'image' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'is_urgent' => 'sometimes|boolean',
        ];
    }
}
