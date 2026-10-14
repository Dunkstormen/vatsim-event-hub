import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatTimeRange, formatUtc } from '@/lib/event-format';
import { Link, router } from '@inertiajs/react';

export interface EventCardData {
    id: number;
    event_id: number;
    title: string;
    description: string | null;
    starts_at: string;
    ends_at: string;
    airports: string[];
}

const MAX_VISIBLE_AIRPORTS = 4;

export function EventCard({ event, canManage = false }: { event: EventCardData; canManage?: boolean }) {
    function destroy() {
        if (!window.confirm(`Delete "${event.title}"? This cannot be undone.`)) {
            return;
        }

        router.delete(route('events.destroy', event.event_id), { preserveScroll: true });
    }

    const visibleAirports = event.airports.slice(0, MAX_VISIBLE_AIRPORTS);
    const extraAirports = event.airports.length - visibleAirports.length;

    return (
        <article className="border-border hover:border-muted-foreground flex flex-col overflow-hidden rounded-xl border transition-colors">
            <div className="relative h-[120px] bg-[repeating-linear-gradient(135deg,_var(--color-muted)_0_12px,_transparent_12px_24px)]">
                <div className="border-border bg-background absolute top-3 left-3 rounded-lg border px-2.5 py-1 text-center">
                    <div className="text-muted-foreground text-[10px] uppercase">{formatUtc(event.starts_at, { month: 'short' })}</div>
                    <div className="text-lg leading-tight font-semibold">{formatUtc(event.starts_at, { day: 'numeric' })}</div>
                </div>
            </div>
            <div className="flex flex-1 flex-col gap-3 p-5">
                <div>
                    <div className="text-base font-semibold">{event.title}</div>
                    <div className="text-muted-foreground mt-0.5">{formatTimeRange(event.starts_at, event.ends_at)}</div>
                </div>
                {event.airports.length > 0 && (
                    <div className="flex flex-wrap gap-1.5">
                        {visibleAirports.map((icao) => (
                            <Badge key={icao} variant="outline" className="rounded font-mono text-xs">
                                {icao}
                            </Badge>
                        ))}
                        {extraAirports > 0 && (
                            <Badge variant="outline" className="text-muted-foreground rounded font-mono text-xs">
                                +{extraAirports}
                            </Badge>
                        )}
                    </div>
                )}
                {event.description && <p className="text-muted-foreground line-clamp-3 text-pretty">{event.description}</p>}
                <div className="mt-auto flex items-center justify-between gap-3 pt-2">
                    <Link href={route('events.show', event.id)} className="inline-flex items-center gap-1.5 font-medium">
                        View event details <span>&rarr;</span>
                    </Link>
                    {canManage && (
                        <div className="flex gap-2">
                            <Button size="sm" variant="outline" asChild>
                                <Link href={route('events.edit', event.event_id)}>Edit</Link>
                            </Button>
                            <Button size="sm" variant="destructive" onClick={destroy}>
                                Delete
                            </Button>
                        </div>
                    )}
                </div>
            </div>
        </article>
    );
}
