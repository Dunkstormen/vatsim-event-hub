import { SiteFooter } from '@/components/site-footer';
import { SiteHeader } from '@/components/site-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { daysUntil, formatTimeRange, formatUtc } from '@/lib/event-format';
import { Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';

interface EventCardData {
    id: number;
    title: string;
    description: string | null;
    starts_at: string;
    ends_at: string;
    airports: string[];
}

interface WelcomeProps {
    events: EventCardData[];
    nextEvent: EventCardData | null;
}

const MAX_VISIBLE_AIRPORTS = 4;

export default function Welcome({ events, nextEvent }: WelcomeProps) {
    const [yearFilter, setYearFilter] = useState<string>('all');

    const years = useMemo(() => {
        const unique = new Set(events.map((event) => formatUtc(event.starts_at, { year: 'numeric' })));

        return Array.from(unique).sort();
    }, [events]);

    const visibleEvents = useMemo(() => {
        if (yearFilter === 'all') {
            return events;
        }

        return events.filter((event) => formatUtc(event.starts_at, { year: 'numeric' }) === yearFilter);
    }, [events, yearFilter]);

    return (
        <div className="dark">
            <Head title="Example vACC">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
            </Head>

            <div className="bg-background text-foreground min-h-screen text-sm">
                <SiteHeader />

                <section className="mx-auto grid max-w-6xl items-center gap-12 px-6 py-18 pb-12 md:grid-cols-2">
                    <div className="flex flex-col gap-5">
                        <h1 className="text-5xl leading-[1.05] font-semibold tracking-tight text-balance">
                            Fly and control with us at our next event
                        </h1>
                        <p className="text-muted-foreground max-w-[480px] text-base leading-normal text-pretty">
                            Browse upcoming events and open an event to see the full briefing, airport, times and how to take part.
                        </p>
                        <div className="flex flex-wrap gap-3">
                            <a href="#events">
                                <Button>View events</Button>
                            </a>
                        </div>
                    </div>

                    <div className="border-border bg-background overflow-hidden rounded-xl border">
                        <div className="text-muted-foreground grid h-40 place-items-center bg-[repeating-linear-gradient(135deg,_var(--color-muted)_0_12px,_transparent_12px_24px)] font-mono text-xs">
                            event banner image
                        </div>
                        <div className="flex flex-col gap-3.5 p-5">
                            {nextEvent ? (
                                <>
                                    <div className="flex items-center justify-between">
                                        <span className="text-muted-foreground text-xs">Next event</span>
                                        <span className="text-muted-foreground font-mono text-xs font-medium">
                                            in {daysUntil(nextEvent.starts_at)} days
                                        </span>
                                    </div>
                                    <div className="text-xl font-semibold tracking-tight">{nextEvent.title}</div>
                                    <div className="text-muted-foreground">
                                        {formatUtc(nextEvent.starts_at, { month: 'long', day: 'numeric' })} &middot;{' '}
                                        {formatTimeRange(nextEvent.starts_at, nextEvent.ends_at)}
                                        {nextEvent.airports[0] ? <> &middot; {nextEvent.airports[0]}</> : null}
                                    </div>
                                    <Link href={route('events.show', nextEvent.id)} className="font-medium">
                                        View event details &rarr;
                                    </Link>
                                </>
                            ) : (
                                <div className="text-muted-foreground">No events are scheduled yet. Check back soon.</div>
                            )}
                        </div>
                    </div>
                </section>

                <section id="events" className="mx-auto flex max-w-6xl flex-col gap-6 px-6 py-6 pb-18">
                    <div className="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <h2 className="text-2xl font-semibold tracking-tight">Upcoming events</h2>
                            <p className="text-muted-foreground mt-1">Times are in UTC.</p>
                        </div>
                        {years.length > 1 && (
                            <div className="bg-muted flex gap-0.5 rounded-lg p-1">
                                {['all', ...years].map((year) => (
                                    <button
                                        key={year}
                                        onClick={() => setYearFilter(year)}
                                        className={`h-7 rounded-md px-3 text-[13px] font-medium ${
                                            yearFilter === year ? 'bg-background text-foreground' : 'text-muted-foreground'
                                        }`}
                                    >
                                        {year === 'all' ? 'All' : year}
                                    </button>
                                ))}
                            </div>
                        )}
                    </div>

                    {visibleEvents.length > 0 ? (
                        <div className="grid [grid-template-columns:repeat(auto-fill,minmax(320px,1fr))] gap-4">
                            {visibleEvents.map((event) => {
                                const visibleAirports = event.airports.slice(0, MAX_VISIBLE_AIRPORTS);
                                const extraAirports = event.airports.length - visibleAirports.length;

                                return (
                                    <article
                                        key={event.id}
                                        className="border-border hover:border-muted-foreground flex flex-col overflow-hidden rounded-xl border transition-colors"
                                    >
                                        <div className="relative h-[120px] bg-[repeating-linear-gradient(135deg,_var(--color-muted)_0_12px,_transparent_12px_24px)]">
                                            <div className="border-border bg-background absolute top-3 left-3 rounded-lg border px-2.5 py-1 text-center">
                                                <div className="text-muted-foreground text-[10px] uppercase">
                                                    {formatUtc(event.starts_at, { month: 'short' })}
                                                </div>
                                                <div className="text-lg leading-tight font-semibold">
                                                    {formatUtc(event.starts_at, { day: 'numeric' })}
                                                </div>
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
                                            {event.description && (
                                                <p className="text-muted-foreground line-clamp-3 text-pretty">{event.description}</p>
                                            )}
                                            <Link
                                                href={route('events.show', event.id)}
                                                className="mt-auto inline-flex items-center gap-1.5 pt-2 font-medium"
                                            >
                                                View event details <span>&rarr;</span>
                                            </Link>
                                        </div>
                                    </article>
                                );
                            })}
                        </div>
                    ) : (
                        <div className="border-border text-muted-foreground rounded-xl border px-6 py-16 text-center">
                            No upcoming events match this filter yet.
                        </div>
                    )}
                </section>

                <SiteFooter />
            </div>
        </div>
    );
}
