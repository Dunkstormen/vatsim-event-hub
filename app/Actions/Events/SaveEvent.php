<?php

namespace App\Actions\Events;

use App\Models\Event;
use Illuminate\Http\UploadedFile;

class SaveEvent
{
    /**
     * Create or update an event. EventObserver::created() already generates
     * occurrences for new events, so this only needs to re-run generation on
     * update when a recurrence-affecting field changed (safe to re-run:
     * GenerateEventOccurrences is idempotent).
     *
     * @param  array<string, mixed>  $attributes
     */
    public function handle(?Event $event, array $attributes, ?UploadedFile $banner): Event
    {
        $isUpdate = $event !== null;
        $event ??= new Event;

        $recurrenceAffectingFieldsChanged = $isUpdate && (
            $event->starts_at?->ne($attributes['starts_at'])
            || $event->ends_at?->ne($attributes['ends_at'])
            || $event->recurrence_frequency?->value !== $attributes['recurrence_frequency']
        );

        if ($banner !== null) {
            $attributes['banner_path'] = $banner->store('event-banners', 'public');
        }

        $event->fill($attributes);
        $event->save();

        if ($recurrenceAffectingFieldsChanged) {
            (new GenerateEventOccurrences)->handle($event);
        }

        return $event;
    }
}
