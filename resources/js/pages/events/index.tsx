import { EventCard, type EventCardData } from '@/components/event-card';
import { SiteFooter } from '@/components/site-footer';
import { SiteHeader } from '@/components/site-header';
import { formatUtc } from '@/lib/event-format';
import { Head } from '@inertiajs/react';
import { useMemo, useState } from 'react';

interface EventIndexProps {
    events: EventCardData[];
}

export default function EventIndex({ events }: EventIndexProps) {
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
            <Head title="Events" />

            <div className="bg-background text-foreground min-h-screen text-sm">
                <SiteHeader activeLabel="Events" />

                <section className="mx-auto flex max-w-6xl flex-col gap-6 px-6 py-12">
                    <div className="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <h1 className="text-3xl font-semibold tracking-tight">Upcoming events</h1>
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
                            {visibleEvents.map((event) => (
                                <EventCard key={event.id} event={event} />
                            ))}
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
