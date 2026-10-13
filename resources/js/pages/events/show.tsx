import { SiteFooter } from '@/components/site-footer';
import { SiteHeader } from '@/components/site-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { daysUntil, formatTimeRange, formatUtc } from '@/lib/event-format';
import { type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

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

interface PreDefinedShift {
    id: number;
    label: string;
    starts_at: string;
    ends_at: string;
    booking_id: number | null;
    booked_by_name: string | null;
    booked_by_me: boolean;
}

interface PreDefinedPosition {
    id: number;
    callsign: string;
    frequency: string;
    airport_icao: string;
    shifts: PreDefinedShift[];
}

interface OpenInterestAssignment {
    user_name: string | null;
    starts_at: string | null;
    ends_at: string | null;
}

interface OpenInterestPosition {
    id: number;
    callsign: string;
    frequency: string;
    airport_icao: string;
    my_interest: { availability: string | null } | null;
    assignments: OpenInterestAssignment[];
}

type AtcRosterData =
    | {
          type: 'pre_defined';
          visible: boolean;
          positions: PreDefinedPosition[];
      }
    | {
          type: 'open_interest';
          visible: boolean;
          is_published: boolean;
          interest_window: { opens_at: string | null; closes_at: string | null; is_open: boolean };
          positions: OpenInterestPosition[];
      };

interface EventShowProps {
    occurrence: OccurrenceData;
    event: EventData;
    atcRoster: AtcRosterData | null;
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

function PreDefinedRosterSection({ positions }: { positions: PreDefinedPosition[] }) {
    const { auth } = usePage<SharedData>().props;
    const [pendingShiftId, setPendingShiftId] = useState<number | null>(null);

    function book(shiftId: number) {
        setPendingShiftId(shiftId);
        router.post(route('atc-shifts.bookings.store', { atcShift: shiftId }), {}, { preserveScroll: true, onFinish: () => setPendingShiftId(null) });
    }

    function release(bookingId: number) {
        setPendingShiftId(bookingId);
        router.delete(route('atc-shift-bookings.destroy', { atcShiftBooking: bookingId }), {
            preserveScroll: true,
            onFinish: () => setPendingShiftId(null),
        });
    }

    if (positions.length === 0) {
        return <p className="text-muted-foreground text-[15px]">No ATC positions have been published for this occurrence yet.</p>;
    }

    return (
        <div className="border-border overflow-hidden rounded-xl border">
            {positions.map((position, index) => (
                <div key={position.id} className={`px-5 py-4 ${index ? 'border-border border-t' : ''}`}>
                    <div className="mb-2.5 flex items-center gap-3">
                        <span className="font-mono text-[13px] font-medium">{position.callsign}</span>
                        <Badge variant="outline" className="text-xs">
                            {position.frequency}
                        </Badge>
                        <span className="text-muted-foreground text-xs">{position.airport_icao}</span>
                    </div>
                    <div className="flex flex-col gap-2">
                        {position.shifts.map((shift) => (
                            <div key={shift.id} className="flex items-center justify-between gap-3 text-[13px]">
                                <div className="flex flex-col">
                                    <span className="font-medium">{shift.label}</span>
                                    <span className="text-muted-foreground">{formatTimeRange(shift.starts_at, shift.ends_at)}</span>
                                </div>
                                {shift.booking_id ? (
                                    shift.booked_by_me ? (
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            disabled={pendingShiftId === shift.booking_id}
                                            onClick={() => release(shift.booking_id as number)}
                                        >
                                            Release
                                        </Button>
                                    ) : (
                                        <span className="text-muted-foreground">Booked by {shift.booked_by_name}</span>
                                    )
                                ) : auth.user ? (
                                    <Button size="sm" disabled={pendingShiftId === shift.id} onClick={() => book(shift.id)}>
                                        Book
                                    </Button>
                                ) : (
                                    <a href={route('auth.vatsim.redirect')} className="text-muted-foreground hover:text-foreground underline">
                                        Log in to book
                                    </a>
                                )}
                            </div>
                        ))}
                    </div>
                </div>
            ))}
        </div>
    );
}

function OpenInterestRosterSection({
    positions,
    isPublished,
    interestWindow,
    occurrenceId,
}: {
    positions: OpenInterestPosition[];
    isPublished: boolean;
    interestWindow: { opens_at: string | null; closes_at: string | null; is_open: boolean };
    occurrenceId: number;
}) {
    const { auth } = usePage<SharedData>().props;
    const [availabilityByPosition, setAvailabilityByPosition] = useState<Record<number, string>>({});
    const [pendingPositionId, setPendingPositionId] = useState<number | null>(null);

    function expressInterest(positionId: number) {
        setPendingPositionId(positionId);
        router.post(
            route('atc-positions.interest.store', { eventOccurrence: occurrenceId, atcPosition: positionId }),
            { availability: availabilityByPosition[positionId] ?? '' },
            { preserveScroll: true, onFinish: () => setPendingPositionId(null) },
        );
    }

    function withdrawInterest(positionId: number) {
        setPendingPositionId(positionId);
        router.delete(route('atc-positions.interest.destroy', { eventOccurrence: occurrenceId, atcPosition: positionId }), {
            preserveScroll: true,
            onFinish: () => setPendingPositionId(null),
        });
    }

    if (positions.length === 0) {
        return <p className="text-muted-foreground text-[15px]">No ATC positions have been added for this occurrence yet.</p>;
    }

    return (
        <div className="flex flex-col gap-3">
            {!isPublished && interestWindow.closes_at && (
                <p className="text-muted-foreground text-[13px]">
                    {interestWindow.is_open
                        ? `Interest window closes ${formatUtc(interestWindow.closes_at, { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' })}.`
                        : 'The interest window for this occurrence is not currently open.'}
                </p>
            )}
            <div className="border-border overflow-hidden rounded-xl border">
                {positions.map((position, index) => (
                    <div key={position.id} className={`flex flex-col gap-2 px-5 py-4 ${index ? 'border-border border-t' : ''}`}>
                        <div className="flex items-center gap-3">
                            <span className="font-mono text-[13px] font-medium">{position.callsign}</span>
                            <Badge variant="outline" className="text-xs">
                                {position.frequency}
                            </Badge>
                            <span className="text-muted-foreground text-xs">{position.airport_icao}</span>
                        </div>

                        {isPublished ? (
                            position.assignments.length > 0 ? (
                                position.assignments.map((assignment, i) => (
                                    <div key={i} className="text-[13px]">
                                        <span className="font-medium">{assignment.user_name}</span>
                                        {assignment.starts_at && assignment.ends_at && (
                                            <span className="text-muted-foreground">
                                                {' '}
                                                &middot; {formatTimeRange(assignment.starts_at, assignment.ends_at)}
                                            </span>
                                        )}
                                    </div>
                                ))
                            ) : (
                                <span className="text-muted-foreground text-[13px]">Unstaffed</span>
                            )
                        ) : auth.user ? (
                            interestWindow.is_open ? (
                                <div className="flex items-center gap-2">
                                    <Input
                                        placeholder="Availability (optional)"
                                        defaultValue={position.my_interest?.availability ?? ''}
                                        className="h-9 max-w-xs text-[13px]"
                                        onChange={(e) => setAvailabilityByPosition((prev) => ({ ...prev, [position.id]: e.target.value }))}
                                    />
                                    {position.my_interest ? (
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            disabled={pendingPositionId === position.id}
                                            onClick={() => withdrawInterest(position.id)}
                                        >
                                            Withdraw interest
                                        </Button>
                                    ) : (
                                        <Button size="sm" disabled={pendingPositionId === position.id} onClick={() => expressInterest(position.id)}>
                                            Show interest
                                        </Button>
                                    )}
                                </div>
                            ) : position.my_interest ? (
                                <span className="text-muted-foreground text-[13px]">
                                    Interest submitted: {position.my_interest.availability || '—'}
                                </span>
                            ) : (
                                <span className="text-muted-foreground text-[13px]">Interest window closed</span>
                            )
                        ) : (
                            <a href={route('auth.vatsim.redirect')} className="text-muted-foreground hover:text-foreground text-[13px] underline">
                                Log in to show interest
                            </a>
                        )}
                    </div>
                ))}
            </div>
        </div>
    );
}

export default function EventShow({ occurrence, event, atcRoster }: EventShowProps) {
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

                            {atcRoster && atcRoster.visible && (
                                <section className="flex flex-col gap-3">
                                    <h2 className="text-lg font-semibold">ATC positions</h2>
                                    {atcRoster.type === 'pre_defined' ? (
                                        <PreDefinedRosterSection positions={atcRoster.positions} />
                                    ) : (
                                        <OpenInterestRosterSection
                                            positions={atcRoster.positions}
                                            isPublished={atcRoster.is_published}
                                            interestWindow={atcRoster.interest_window}
                                            occurrenceId={occurrence.id}
                                        />
                                    )}
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
