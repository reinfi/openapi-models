<?php

declare(strict_types=1);

namespace Api\Schema;

enum ColorXNames: string
{
    /** A wonderful red like a rose */
    case Rose = 'red';

    /** Just as green as an apple */
    case Apple = 'green';

    /** Like the white snow on the mountains */
    case Snow = 'white';
}
