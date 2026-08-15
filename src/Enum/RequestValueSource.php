<?php

namespace Cryonighter\ValidRequestBundle\Enum;

enum RequestValueSource
{
    case attributes;
    case cookies;
    case files;
    case headers;
    case payload;
    case server;
    case query;
}
