<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Received = 'received';
    case Preparing = 'preparing';
    case ReadyForPickup = 'ready_for_pickup';
    case PickedUp = 'picked_up';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Received => 'Recibido',
            self::Preparing => 'En preparación',
            self::ReadyForPickup => 'Listo para retirar',
            self::PickedUp => 'Retirado',
            self::Cancelled => 'Cancelado',
        };
    }

    /** @return list<self> */
    public function nextStates(): array
    {
        return match ($this) {
            self::Received => [self::Preparing, self::Cancelled],
            self::Preparing => [self::ReadyForPickup, self::Cancelled],
            self::ReadyForPickup => [self::PickedUp, self::Cancelled],
            self::PickedUp, self::Cancelled => [],
        };
    }
}
