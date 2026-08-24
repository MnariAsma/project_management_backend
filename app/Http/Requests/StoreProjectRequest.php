<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
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
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'repositories' => 'required|array|min:1',
            'repositories.*.github_repo_id' => 'required|string',
            'repositories.*.name' => 'required|string',
            'repositories.*.owner' => 'required|string',
            'repositories.*.github_url' => 'required|string',
            'repositories.*.github_created_at' => 'nullable|date',
            'repositories.*.github_updated_at' => 'nullable|date',
        ];
    }

     public function messages(): array
    {
        return [
            'name.required' => 'The project name is required.',
            'repositories.required' => 'At least one repository is required.',
            'repositories.min' => 'The project must have at least one repository.',

            'repositories.*.github_repo_id.required' => 'The GitHub repository ID is required.',
            'repositories.*.name.required' => 'The repository name is required.',

            'repositories.*.owner.required' => 'The repository owner is required.',
            'repositories.*.github_url.required' => 'The GitHub repository URL is required.',
            'repositories.*.github_created_at.date' => 'The GitHub creation date must be a valid date.',
            'repositories.*.github_updated_at.date' => 'The GitHub update date must be a valid date.',
        ];
    }
}
