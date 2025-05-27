<?php

declare(strict_types=1);

namespace Api\Schema;

enum Test17NullableEnumEnumValue: string
{
    /** You did it good */
    case Good = 'good';

    /** Next time you do it better */
    case Bad = 'bad';
}
