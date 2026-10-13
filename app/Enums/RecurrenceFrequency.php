<?php

namespace App\Enums;

use Carbon\CarbonInterface;

enum RecurrenceFrequency: string
{
    case Weekly = 'weekly';
    case Biweekly = 'biweekly';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';

    /**
     * Advance a date to the next occurrence start for this frequency.
     */
    public function next(CarbonInterface $date): CarbonInterface
    {
        return match ($this) {
            self::Weekly => $date->copy()->addWeek(),
            self::Biweekly => $date->copy()->addWeeks(2),
            self::Monthly => $date->copy()->addMonthNoOverflow(),
            self::Quarterly => $date->copy()->addMonthsNoOverflow(3),
        };
    }
}
