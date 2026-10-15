<?php

namespace App\Enums;

enum SaleUnit: string
{
    case Unit = 'unit';
    case Meter = 'meter';
    case Kilogram = 'kilogram';
    case Gram = 'gram';
    case Liter = 'liter';
    case Milliliter = 'milliliter';
    case Box = 'box';
    case Package = 'package';
    case Bundle = 'bundle';
    case Roll = 'roll';
    case Other = 'other';
}
