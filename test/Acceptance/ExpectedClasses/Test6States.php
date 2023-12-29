<?php

declare(strict_types=1);

namespace Api\Schema;

enum Test6States: string
{
    case Positive = 'positive';
    case Negative = 'negative';
}
