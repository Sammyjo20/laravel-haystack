<?php

declare(strict_types=1);

namespace Sammyjo20\LaravelHaystack\Enums;

enum FinishStatus: string
{
    case Success = 'success';
    case Failure = 'failure';
    case Cancelled = 'cancelled';
}
