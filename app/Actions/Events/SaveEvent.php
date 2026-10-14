<?php

namespace App\Actions\Events;

use App\Models\Event;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

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
            $attributes['banner_path'] = $this->convertToWebp($banner->store('event-banners', 'public'));
        }

        $event->fill($attributes);
        $event->save();

        if ($recurrenceAffectingFieldsChanged) {
            (new GenerateEventOccurrences)->handle($event);
        }

        return $event;
    }

    /**
     * Re-encode the stored banner as WebP via the `cwebp` CLI (no GD/Imagick
     * extension is available in this environment). Falls back to the
     * original upload if the binary is missing or conversion fails, so a
     * banner upload never breaks because of this step.
     */
    private function convertToWebp(string $storedPath): string
    {
        $disk = Storage::disk('public');
        $webpPath = Str::beforeLast($storedPath, '.').'.webp';

        $process = new Process([
            'cwebp', '-quiet', '-q', '80', $disk->path($storedPath), '-o', $disk->path($webpPath),
        ]);
        $process->run();

        if (! $process->isSuccessful() || ! $disk->exists($webpPath)) {
            Log::warning('Banner WebP conversion failed, keeping original upload.', [
                'path' => $storedPath,
                'error' => $process->getErrorOutput(),
            ]);

            return $storedPath;
        }

        $disk->delete($storedPath);

        return $webpPath;
    }
}
