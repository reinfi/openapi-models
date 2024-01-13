<?php

declare(strict_types=1);

namespace Api\Schema;

enum Color: string
{
    case Red = 'red';
    case Green = 'green';
    case White = 'white';
}
