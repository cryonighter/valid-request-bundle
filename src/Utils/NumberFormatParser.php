<?php

namespace Cryonighter\ValidRequestBundle\Utils;

class NumberFormatParser
{
    public static function parseFloat(string $value): ?float {
        // Убираем все виды пробелов
        $value = preg_replace('/[\s\x{00A0}\x{202F}\x{2009}\x{2019}\'_]+/u', '', trim($value));

        // Проверяем, что строка содержит только цифры, точки и запятые
        if (!preg_match('/^-?[0-9.,]+$/', $value)) {
            return null;
        }

        // Проверяем, что строка не начинается и не заканчивается разделителем
        if (preg_match('/^[.,]|[.,]$/', $value)) {
            return null;
        }

        // Подсчитываем разделители
        $commaCount = substr_count($value, ',');
        $dotCount = substr_count($value, '.');

        // Случай 1: Нет разделителей - просто целое число
        if ($commaCount === 0 && $dotCount === 0) {
            return (float) $value;
        }

        // Случай 2: Только запятые
        if ($commaCount > 0 && $dotCount === 0) {
            if ($commaCount === 1) {
                return (float) str_replace(',', '.', $value);
            }

            // Несколько запятых - это разделители тысяч: 1,234,567
            // Проверяем правильность формата (по 3 цифры между запятыми)
            if (!preg_match('/^\d{1,3}(,\d{3})+$/', $value)) {
                return null;
            }

            return (float) str_replace(',', '', $value);
        }

        // Случай 3: Только точки
        if ($dotCount > 0 && $commaCount === 0) {
            if ($dotCount === 1) {
                return (float) $value;
            }

            // Несколько точек - это разделители тысяч: 1.234.567
            if (!preg_match('/^\d{1,3}(\.\d{3})+$/', $value)) {
                return null;
            }

            return (float) str_replace('.', '', $value);
        }

        // Случай 4: И запятые, и точки - определяем по последнему разделителю
        $lastCommaPos = strrpos($value, ',');
        $lastDotPos = strrpos($value, '.');

        if ($lastCommaPos > $lastDotPos) {
            // Последняя запятая - это дробная часть: 1.234.567,89 или 1.234,89
            // Формат: цифры с точками (тысячи), затем запятая и цифры (дробная часть)
            if (!preg_match('/^(\d{1,3}(\.\d{3})*|\d+),\d+$/', $value)) {
                return null;
            }

            // Всё ОК, нормализуем: убираем точки, заменяем запятую на точку
            return (float) str_replace(['.', ','], ['', '.'], $value);
        } else {
            // Последняя точка - это дробная часть: 1,234,567.89 или 1,234.89
            // Формат: цифры с запятыми (тысячи), затем точка и цифры (дробная часть)
            if (!preg_match('/^(\d{1,3}(,\d{3})*|\d+)\.\d+$/', $value)) {
                return null;
            }

            // Всё ОК, нормализуем: убираем запятые
            return (float) str_replace(',', '', $value);
        }
    }

    public static function parseInt(string $value): ?int
    {
        // Убираем все виды пробелов и разделители тысяч
        $value = preg_replace('/[\s\x{00A0}\x{202F}\x{2009}\x{2019}\'_]+/u', '', trim($value));

        // Проверяем, что строка содержит только цифры, точки и запятые
        if (!preg_match('/^-?[0-9.,]+$/', $value)) {
            return null;
        }

        // Проверяем, что строка не начинается и не заканчивается разделителем
        if (preg_match('/^[.,]|[.,]$/', $value)) {
            return null;
        }

        // Подсчитываем разделители
        $commaCount = substr_count($value, ',');
        $dotCount = substr_count($value, '.');

        // Случай 1: Нет разделителей - просто целое число
        if ($commaCount === 0 && $dotCount === 0) {
            // Проверяем переполнение
            if ((float) $value > PHP_INT_MAX) {
                return null;
            }

            return (int) $value;
        }

        // Случай 2: Только запятые
        if ($commaCount > 0 && $dotCount === 0) {
            // Проверяем правильность формата разделителей тысяч: 1,234,567
            if (!preg_match('/^\d{1,3}(,\d{3})+$/', $value)) {
                return null;
            }

            $normalized = str_replace(',', '', $value);

            // Проверяем переполнение
            if ((float) $normalized > PHP_INT_MAX) {
                return null;
            }

            return (int) $normalized;
        }

        // Случай 3: Только точки
        if ($dotCount > 0 && $commaCount === 0) {
            // Проверяем правильность формата разделителей тысяч: 1.234.567
            if (!preg_match('/^\d{1,3}(\.\d{3})+$/', $value)) {
                return null;
            }

            $normalized = str_replace('.', '', $value);

            // Проверяем переполнение
            if ((float) $normalized > PHP_INT_MAX) {
                return null;
            }

            return (int) $normalized;
        }

        // Случай 4: И запятые, и точки - для int это недопустимо (дробная часть)
        return null;
    }
}
