<?php

namespace App\Enums;

/**
 * PURPOSE: Defines Creative Commons and copyright licence types
 * for supplementary files with labels, descriptions, and URLs.
 *
 * SPECIFICATION: SPEC-07/AC-1
 */
enum ArticleFileLicense: string
{
    case CcBy = 'cc_by';
    case CcBySa = 'cc_by_sa';
    case CcByNc = 'cc_by_nc';
    case CcByNcSa = 'cc_by_nc_sa';
    case CcByNd = 'cc_by_nd';
    case CcByNcNd = 'cc_by_nc_nd';
    case Cc0 = 'cc0';
    case Copyright = 'copyright';

    public function label(): string
    {
        return match ($this) {
            self::CcBy => 'CC BY (Attribution)',
            self::CcBySa => 'CC BY-SA (Attribution-ShareAlike)',
            self::CcByNc => 'CC BY-NC (Attribution-NonCommercial)',
            self::CcByNcSa => 'CC BY-NC-SA (Attribution-NonCommercial-ShareAlike)',
            self::CcByNd => 'CC BY-ND (Attribution-NoDerivatives)',
            self::CcByNcNd => 'CC BY-NC-ND (Attribution-NonCommercial-NoDerivatives)',
            self::Cc0 => 'CC0 (Public Domain)',
            self::Copyright => 'Все права защищены',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::CcBy => 'Разрешено копирование, распространение и адаптация при указании авторства',
            self::CcBySa => 'Разрешено копирование и адаптация при указании авторства и на тех же условиях',
            self::CcByNc => 'Разрешено некоммерческое использование при указании авторства',
            self::CcByNcSa => 'Разрешено некоммерческое использование при указании авторства и на тех же условиях',
            self::CcByNd => 'Разрешено распространение без изменений при указании авторства',
            self::CcByNcNd => 'Разрешено некоммерческое распространение без изменений при указании авторства',
            self::Cc0 => 'Передача в общественное достояние, безусловное отказ от прав',
            self::Copyright => 'Все права защищены, требуется разрешение автора',
        };
    }

    public function url(): ?string
    {
        return match ($this) {
            self::CcBy => 'https://creativecommons.org/licenses/by/4.0/',
            self::CcBySa => 'https://creativecommons.org/licenses/by-sa/4.0/',
            self::CcByNc => 'https://creativecommons.org/licenses/by-nc/4.0/',
            self::CcByNcSa => 'https://creativecommons.org/licenses/by-nc-sa/4.0/',
            self::CcByNd => 'https://creativecommons.org/licenses/by-nd/4.0/',
            self::CcByNcNd => 'https://creativecommons.org/licenses/by-nc-nd/4.0/',
            self::Cc0 => 'https://creativecommons.org/publicdomain/zero/1.0/',
            self::Copyright => null,
        };
    }
}
