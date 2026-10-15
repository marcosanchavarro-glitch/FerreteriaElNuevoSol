<?php

namespace App\Enums;

enum PurchaseMode: string
{
    case Calculated = 'calculated';
    case Inquiry = 'inquiry';
}
