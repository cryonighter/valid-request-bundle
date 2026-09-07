<?php

namespace Cryonighter\ValidRequestBundle;

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
