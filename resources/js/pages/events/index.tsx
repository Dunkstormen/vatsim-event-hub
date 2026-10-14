import { EventCard, type EventCardData } from '@/components/event-card';
import { SiteFooter } from '@/components/site-footer';
import { SiteHeader } from '@/components/site-header';
import { Head, router } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';

interface EventIndexProps {
    events: EventCardData[];
    years: string[];
    year: string | null;
    nextPage: number | null;
}

interface EventFeedResponse {
    events: EventCardData[];
    nextPage: number | null;
}

export default function EventIndex({ events, years, year, nextPage }: EventIndexProps) {
    const [loadedEvents, setLoadedEvents] = useState<EventCardData[]>(events);
    const [page, setPage] = useState<number | null>(nextPage);
    const [isLoadingMore, setIsLoadingMore] = useState(false);
    const sentinelRef = useRef<HTMLDivElement | null>(null);

    useEffect(() => {
        setLoadedEvents(events);
        setPage(nextPage);
    }, [events, nextPage]);

    const loadMore = useCallback(async () => {
        if (page === null || isLoadingMore) {
            return;
        }

        setIsLoadingMore(true);

        const params = new URLSearchParams({ page: String(page) });
        if (year) {
            params.set('year', year);
        }

        const response = await fetch(`${route('events.feed')}?${params.toString()}`, {
            headers: { Accept: 'application/json' },
        });
        const data: EventFeedResponse = await response.json();

        setLoadedEvents((previous) => [...previous, ...data.events]);
        setPage(data.nextPage);
        setIsLoadingMore(false);
    }, [page, isLoadingMore, year]);

    useEffect(() => {
        const sentinel = sentinelRef.current;
        if (!sentinel || page === null) {
            return;
        }

        const observer = new IntersectionObserver(
            (entries) => {
                if (entries[0]?.isIntersecting) {
                    loadMore();
                }
            },
            { rootMargin: '400px' },
        );

        observer.observe(sentinel);

        return () => observer.disconnect();
    }, [loadMore, page]);

    const setYearFilter = (nextYear: string) => {
        router.get(route('events.index'), nextYear === 'all' ? {} : { year: nextYear }, {
            preserveState: false,
            preserveScroll: false,
        });
    };

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
                                {['all', ...years].map((option) => (
                                    <button
                                        key={option}
                                        onClick={() => setYearFilter(option)}
                                        className={`h-7 rounded-md px-3 text-[13px] font-medium ${
                                            (year ?? 'all') === option ? 'bg-background text-foreground' : 'text-muted-foreground'
                                        }`}
                                    >
                                        {option === 'all' ? 'All' : option}
                                    </button>
                                ))}
                            </div>
                        )}
                    </div>

                    {loadedEvents.length > 0 ? (
                        <>
                            <div className="grid [grid-template-columns:repeat(auto-fill,minmax(320px,1fr))] gap-4">
                                {loadedEvents.map((event) => (
                                    <EventCard key={event.id} event={event} />
                                ))}
                            </div>
                            {page !== null && (
                                <div ref={sentinelRef} className="text-muted-foreground flex justify-center py-6 text-xs">
                                    {isLoadingMore ? 'Loading more events…' : ''}
                                </div>
                            )}
                        </>
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
