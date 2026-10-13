<?php

namespace App\Enums;

enum OrderEventType: string
{
    case Status = 'status';
    case Payment = 'payment';
    case Note = 'note';
    case Delivery = 'delivery';
}
