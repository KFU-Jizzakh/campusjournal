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
        $fileTypeInput = $this->input('file_type');
        $fileType = is_string($fileTypeInput) ? ArticleFileType::tryFrom($fileTypeInput) : null;
        $mimeTypes = $fileType?->mimeTypes() ?? [];
        $extensions = $fileType?->extensions() ?? [];

        $fileRules = [
            'required',
            'file',
            'max:102400', // 100MB
        ];

        // Defense in depth: content-based MIME check (finfo) AND the
        // uploaded file's extension must both be inside the type's
        // allowlist — a file with valid content stored under a dangerous
        // extension (.html/.svg) would otherwise be served by the public
        // disk with an executable content type.
        if ($mimeTypes !== []) {
            $fileRules[] = 'mimetypes:'.implode(',', $mimeTypes);
        }

        if ($extensions !== []) {
            $fileRules[] = 'mimes:'.implode(',', $extensions);
        }

        return [
            'file' => $fileRules,
            'file_type' => ['required', Rule::enum(ArticleFileType::class)],
            'visibility' => ['required', Rule::enum(ArticleFileVisibility::class)],
            'license' => ['nullable', Rule::enum(ArticleFileLicense::class)],
            'language' => ['nullable', 'string', 'size:2'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Необходимо выбрать файл для загрузки.',
            'file.file' => 'Загруженный объект должен быть файлом.',
            'file.max' => 'Размер файла не должен превышать 100 МБ.',
            'file.mimetypes' => 'Недопустимый тип файла для выбранной категории.',
            'file.mimes' => 'Недопустимое расширение файла для выбранной категории.',
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
