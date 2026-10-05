<?php

namespace App\Http\Requests\Notes;

use App\Models\Note;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateNoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $note = $this->route('note');

        return $note instanceof Note
            ? ($this->user()?->can('update', $note) ?? false)
            : false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'color' => ['nullable', 'string', 'in:slate,orange,yellow,cyan,rose,purple'],
            'is_pinned' => ['nullable', 'boolean'],
            'order_column' => ['nullable', 'integer'],
        ];
    }
}
