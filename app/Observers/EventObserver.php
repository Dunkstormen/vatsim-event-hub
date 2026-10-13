<?php

namespace App\Observers;

use App\Actions\Events\GenerateEventOccurrences;
use App\Models\Event;

class EventObserver
{
    public function created(Event $event): void
    {
        (new GenerateEventOccurrences)->handle($event);
    }
}
