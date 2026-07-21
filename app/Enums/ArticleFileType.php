<?php

namespace App\Enums;

/**
 * PURPOSE: Defines supplementary file categories with their
 * accepted MIME types, file extensions, and display icons.
 *
 * SPECIFICATION: SPEC-07/AC-1
 */
enum ArticleFileType: string
{
    case ResearchData = 'research_data';
    case Image = 'image';
    case Video = 'video';
    case Audio = 'audio';
    case Code = 'code';
    case Document = 'document';
    case JatsXml = 'jats_xml';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::ResearchData => 'Исследовательские данные',
            self::Image => 'Изображение',
            self::Video => 'Видео',
            self::Audio => 'Аудио',
            self::Code => 'Исходный код',
            self::Document => 'Документ',
            self::JatsXml => 'JATS XML',
            self::Other => 'Другое',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::ResearchData => 'chart-bar',
            self::Image => 'photograph',
            self::Video => 'video-camera',
            self::Audio => 'music-note',
            self::Code => 'code',
            self::Document => 'document-text',
            self::JatsXml => 'code',
            self::Other => 'paper-clip',
        };
    }

    /**
     * @return array<string>
     */
    public function mimeTypes(): array
    {
        return match ($this) {
            self::ResearchData => [
                'text/csv',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/json',
                'application/xml',
                'text/xml',
                'text/plain',
                'text/tab-separated-values',
            ],
            self::Image => [
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/tiff',
                'image/webp',
            ],
            self::Video => [
                'video/mp4',
                'video/webm',
                'video/avi',
                'video/quicktime',
                'video/x-msvideo',
            ],
            self::Audio => [
                'audio/mpeg',
                'audio/wav',
                'audio/ogg',
                'audio/flac',
                'audio/aac',
            ],
            self::Code => [
                'application/zip',
                'application/x-zip-compressed',
                'application/x-tar',
                'application/gzip',
                'text/plain',
            ],
            self::Document => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.oasis.opendocument.text',
                'text/plain',
                'text/rtf',
            ],
            self::JatsXml => [
                'application/xml',
                'text/xml',
            ],
            self::Other => ['*/*'],
        };
    }

    /**
     * @return array<string>
     */
    public function extensions(): array
    {
        return match ($this) {
            self::ResearchData => ['csv', 'xls', 'xlsx', 'json', 'xml', 'txt', 'tsv'],
            self::Image => ['jpg', 'jpeg', 'png', 'gif', 'tiff', 'tif', 'webp'],
            self::Video => ['mp4', 'webm', 'avi', 'mov'],
            self::Audio => ['mp3', 'wav', 'ogg', 'flac', 'aac'],
            self::Code => ['zip', 'tar', 'gz', 'txt'],
            self::Document => ['pdf', 'doc', 'docx', 'odt', 'txt', 'rtf'],
            self::JatsXml => ['xml'],
            self::Other => ['*'],
        };
    }
}
