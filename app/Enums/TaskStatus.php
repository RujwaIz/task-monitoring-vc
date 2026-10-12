<?php

namespace App\Enums;

enum TaskStatus: string
{
    case NotStarted = 'Not started';
    case InProgress = 'In progress';
    case Complete = 'Complete';
}
