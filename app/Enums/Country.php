<?php

namespace App\Enums;

/**
 * PURPOSE: Defines the list of countries available for user
 * profile and author affiliation selection.
 */
enum Country: string
{
    case Russia = 'Россия';
    case Belarus = 'Беларусь';
    case Kazakhstan = 'Казахстан';
    case Ukraine = 'Украина';
    case Uzbekistan = 'Узбекистан';
    case Azerbaijan = 'Азербайджан';
    case Armenia = 'Армения';
    case Georgia = 'Грузия';
    case Kyrgyzstan = 'Кыргызстан';
    case Moldova = 'Молдова';
    case Tajikistan = 'Таджикистан';
    case Turkmenistan = 'Туркменистан';
    case Latvia = 'Латвия';
    case Lithuania = 'Литва';
    case Estonia = 'Эстония';
    case Germany = 'Германия';
    case France = 'Франция';
    case UnitedKingdom = 'Великобритания';
    case Italy = 'Италия';
    case Spain = 'Испания';
    case Netherlands = 'Нидерланды';
    case Switzerland = 'Швейцария';
    case Sweden = 'Швеция';
    case Norway = 'Норвегия';
    case Finland = 'Финляндия';
    case Poland = 'Польша';
    case CzechRepublic = 'Чехия';
    case Austria = 'Австрия';
    case Hungary = 'Венгрия';
    case Romania = 'Румыния';
    case Bulgaria = 'Болгария';
    case Serbia = 'Сербия';
    case Turkey = 'Турция';
    case USA = 'США';
    case Canada = 'Канада';
    case Brazil = 'Бразилия';
    case Argentina = 'Аргентина';
    case Mexico = 'Мексика';
    case China = 'Китай';
    case Japan = 'Япония';
    case SouthKorea = 'Южная Корея';
    case India = 'Индия';
    case Iran = 'Иран';
    case Israel = 'Израиль';
    case Australia = 'Австралия';
    case Other = 'Другая';
}
