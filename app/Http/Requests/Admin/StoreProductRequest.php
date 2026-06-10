<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:200'],
            'article' => ['required', 'string', 'min:2', 'max:50', 'regex:/^[A-Za-z0-9\-_]+$/', 'unique:products,article'],
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'price' => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'quantity' => ['required', 'integer', 'min:0', 'max:99999'],
            'main_image' => ['required', 'string', 'url', 'max:500'],
            'care_difficulty' => ['nullable', 'in:easy,medium,hard'],
            'size' => ['nullable', 'in:small,medium,large,extra_large'],
            'age_group' => ['nullable', 'in:young,mature,old'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'article.regex' => 'Артикул может содержать только латинские буквы, цифры, дефис и подчёркивание.',
            'category_ids.min' => 'Выберите хотя бы одну категорию.',
            'main_image.url' => 'Укажите корректную ссылку на изображение (начинается с http:// или https://).',
            'price.min' => 'Цена должна быть больше 0.',
        ];
    }
}
