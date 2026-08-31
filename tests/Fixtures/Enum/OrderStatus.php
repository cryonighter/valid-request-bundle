<?php

namespace Cryonighter\ValidRequestBundle\Tests\Fixtures\Enum;

enum OrderStatus: string
{
    case Draft = 'draft';
    case Paid = 'paid';
    case Shipped = 'shipped';
}
