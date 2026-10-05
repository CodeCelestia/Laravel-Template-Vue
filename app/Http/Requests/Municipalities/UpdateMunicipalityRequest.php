<?php

namespace App\Http\Requests\Municipalities;

use App\Models\Municipality;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMunicipalityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Municipality|null $municipality */
        $municipality = $this->route('municipality');

        return $municipality ? ($this->user()?->can('update', $municipality) ?? false) : false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Municipality|null $municipality */
        $municipality = $this->route('municipality');
        $id = $municipality?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('municipalities', 'name')->ignore($id)->whereNull('deleted_at'),
            ],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('municipalities', 'code')->ignore($id)->whereNull('deleted_at'),
            ],
            'type' => ['required', 'string', 'in:city,municipality'],
            'district' => ['required', 'string', 'in:District 1,District 2'],
            'zip_code' => ['nullable', 'string', 'max:10'],
            'location_description' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Custom validation attribute names.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'municipality or city name',
            'code' => 'LGU code',
            'type' => 'LGU classification type',
            'district' => 'legislative district',
            'zip_code' => 'postal / zip code',
            'location_description' => 'location / office address',
            'latitude' => 'geographic latitude',
            'longitude' => 'geographic longitude',
            'is_active' => 'status',
        ];
    }
}
