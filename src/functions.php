<?php

namespace Cryonighter\ValidRequestBundle;

function array_diff_key_recursive(array $array1, array $array2): array
{
    $difference = array_diff_key($array1, $array2);

    foreach ($array1 as $key => $value) {
        if (array_key_exists($key, $array2) && is_array($value)) {
            if (is_array($array2[$key])) {
                $recursiveDiff = array_diff_key_recursive($value, $array2[$key]);

                if (!empty($recursiveDiff)) {
                    $difference[$key] = $recursiveDiff;
                }
            } else {
                $difference[$key] = $value;
            }
        }
    }

    return $difference;
}

function empty_data_recursive(mixed $data): bool
{
    if (is_iterable($data) && !is_array($data)) {
        $data = iterator_to_array($data);
    }

    if (is_array($data)) {
        return array_all($data, empty_data_recursive(...));
    }

    return $data === null;
}
