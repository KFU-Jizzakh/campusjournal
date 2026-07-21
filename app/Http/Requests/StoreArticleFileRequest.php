<?php

namespace App\Http\Requests;

use App\Enums\ArticleFileLicense;
use App\Enums\ArticleFileType;
use App\Enums\ArticleFileVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreArticleFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('uploadFiles', $this->route('article'));
    }

    public function rules(): array
    {
        $fileType = $this->input('file_type');
        $mimeTypes = $fileType
            ? ArticleFileType::from($fileType)->mimeTypes()
            : [];

        $rules = [
            'file' => [
                'required',
                'file',
                'max:102400', // 100MB
            ],
            'file_type' => ['required', Rule::enum(ArticleFileType::class)],
            'visibility' => ['required', Rule::enum(ArticleFileVisibility::class)],
            'license' => ['nullable', Rule::enum(ArticleFileLicense::class)],
            'language' => ['nullable', 'string', 'size:2'],
        ];

        // Add mimetype validation only if mime types are specified
        if (! empty($mimeTypes) && $mimeTypes !== ['*/*']) {
            $rules['file'][] = 'mimetypes:'.implode(',', $mimeTypes);
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Необходимо выбрать файл для загрузки.',
            'file.file' => 'Загруженный объект должен быть файлом.',
            'file.max' => 'Размер файла не должен превышать 100 МБ.',
            'file.mimetypes' => 'Недопустимый тип файла для выбранной категории.',
            'file_type.required' => 'Необходимо указать тип файла.',
            'visibility.required' => 'Необходимо указать уровень доступа.',
        ];
    }

    public function attributes(): array
    {
        return [
            'file' => 'файл',
            'file_type' => 'тип файла',
            'visibility' => 'уровень доступа',
            'license' => 'лицензия',
            'language' => 'язык',
        ];
    }
}
