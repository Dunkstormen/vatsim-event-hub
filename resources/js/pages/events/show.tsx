import { SiteFooter } from '@/components/site-footer';
import { SiteHeader } from '@/components/site-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { daysUntil, formatTimeRange, formatUtc } from '@/lib/event-format';
import { Head, Link } from '@inertiajs/react';

interface OccurrenceData {
    id: number;
    status: 'scheduled' | 'cancelled' | 'skipped';
    starts_at: string;
    ends_at: string;
}

interface AirportData {
    icao: string;
    is_featured: boolean;
}

interface EventData {
    title: string;
    description: string | null;
    recurrence_frequency: 'weekly' | 'biweekly' | 'monthly' | 'quarterly' | null;
    airports: AirportData[];
}

interface EventShowProps {
    occurrence: OccurrenceData;
    event: EventData;
}

const RECURRENCE_LABELS: Record<NonNullable<EventData['recurrence_frequency']>, string> = {
    weekly: 'Weekly',
    biweekly: 'Every two weeks',
    monthly: 'Monthly',
    quarterly: 'Quarterly',
};

function toIcsStamp(iso: string) {
    return new Date(iso)
        .toISOString()
        .replace(/[-:]/g, '')
        .replace(/\.\d{3}Z$/, 'Z');
}

function downloadCalendarInvite(event: EventData, occurrence: OccurrenceData) {
    const ics = [
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:-//Example vACC//Event Hub//EN',
        'BEGIN:VEVENT',
        `UID:event-occurrence-${occurrence.id}@example-vacc`,
        `DTSTAMP:${toIcsStamp(new Date().toISOString())}`,
        `DTSTART:${toIcsStamp(occurrence.starts_at)}`,
        `DTEND:${toIcsStamp(occurrence.ends_at)}`,
        `SUMMARY:${event.title}`,
        event.description ? `DESCRIPTION:${event.description.replace(/\n/g, '\\n')}` : null,
        'END:VEVENT',
        'END:VCALENDAR',
    ]
        .filter(Boolean)
        .join('\r\n');

    const blob = new Blob([ics], { type: 'text/calendar' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `${event.title.replace(/[^a-z0-9]+/gi, '-').toLowerCase()}.ics`;
    link.click();
    URL.revokeObjectURL(url);
}

export default function EventShow({ occurrence, event }: EventShowProps) {
    const isCancelled = occurrence.status === 'cancelled';
    const descriptionParagraphs = event.description ? event.description.split(/\n{2,}/).filter(Boolean) : [];

    const info: Array<{ k: string; v: string }> = [
        { k: 'Date', v: formatUtc(occurrence.starts_at, { month: 'short', day: 'numeric', year: 'numeric' }) },
        { k: 'Time', v: formatTimeRange(occurrence.starts_at, occurrence.ends_at) },
        { k: 'Recurrence', v: event.recurrence_frequency ? RECURRENCE_LABELS[event.recurrence_frequency] : 'One-time' },
    ];

    if (event.airports.length > 0) {
        info.push({ k: 'Airports', v: String(event.airports.length) });
    }

    return (
        <div className="dark">
            <Head title={event.title} />

            <div className="bg-background text-foreground min-h-screen text-sm">
                <SiteHeader />

                <main className="mx-auto flex max-w-6xl flex-col gap-6 px-6 py-6 pb-18">
                    <Link href={route('home')} className="text-muted-foreground hover:text-foreground w-fit">
                        &larr; All events
                    </Link>

                    <div className="border-border overflow-hidden rounded-xl border">
                        <div className="text-muted-foreground grid h-[220px] place-items-center bg-[repeating-linear-gradient(135deg,_var(--color-muted)_0_12px,_transparent_12px_24px)] font-mono text-xs">
                            event banner image
                        </div>
                    </div>

                    <div className="flex flex-wrap items-end justify-between gap-6">
                        <div className="flex flex-col gap-2">
                            <div className="flex items-center gap-3">
                                <span className="text-muted-foreground font-mono text-xs font-medium">in {daysUntil(occurrence.starts_at)} days</span>
                                {isCancelled && <Badge className="rounded-full">Cancelled</Badge>}
                            </div>
                            <h1 className="text-4xl leading-[1.1] font-semibold tracking-tight">{event.title}</h1>
                            <div className="text-muted-foreground text-base">
                                {formatUtc(occurrence.starts_at, { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' })} &middot;{' '}
                                {formatTimeRange(occurrence.starts_at, occurrence.ends_at)}
                            </div>
                        </div>
                        <Button variant="outline" onClick={() => downloadCalendarInvite(event, occurrence)}>
                            Add to calendar
                        </Button>
                    </div>

                    <div className="grid items-start gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
                        <div className="flex min-w-0 flex-col gap-6">
                            {descriptionParagraphs.length > 0 && (
                                <section className="flex flex-col gap-3">
                                    <h2 className="text-lg font-semibold">About this event</h2>
                                    {descriptionParagraphs.map((paragraph, index) => (
                                        <p key={index} className="text-muted-foreground text-[15px] leading-relaxed text-pretty">
                                            {paragraph}
                                        </p>
                                    ))}
                                </section>
                            )}

                            {event.airports.length > 0 && (
                                <section className="flex flex-col gap-3">
                                    <h2 className="text-lg font-semibold">Participating airports</h2>
                                    <div className="border-border overflow-hidden rounded-xl border">
                                        {event.airports.map((airport, index) => (
                                            <div
                                                key={airport.icao}
                                                className={`flex items-center gap-4 px-5 py-3.5 ${index ? 'border-border border-t' : ''}`}
                                            >
                                                <span className="w-14 font-mono text-[13px] font-medium">{airport.icao}</span>
                                                {airport.is_featured && (
                                                    <Badge variant="outline" className="text-xs">
                                                        Primary
                                                    </Badge>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                </section>
                            )}
                        </div>

                        <aside className="border-border sticky top-[84px] flex flex-col gap-3.5 rounded-xl border p-5">
                            <div className="font-semibold">Event details</div>
                            {info.map((item) => (
                                <div key={item.k} className="flex justify-between gap-3">
                                    <span className="text-muted-foreground">{item.k}</span>
                                    <span className="text-right font-medium">{item.v}</span>
                                </div>
                            ))}
                            <div className="border-border text-muted-foreground border-t pt-3.5 text-[13px] leading-normal">
                                All times are UTC. Event timing may change; check this page before you fly.
                            </div>
                        </aside>
                    </div>
                </main>

                <SiteFooter />
            </div>
        </div>
    );
}
