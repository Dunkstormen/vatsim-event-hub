import { EventCard, type EventCardData } from '@/components/event-card';
import { SiteFooter } from '@/components/site-footer';
import { SiteHeader } from '@/components/site-header';
import { Button } from '@/components/ui/button';
import { type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';

interface EventIndexProps {
    events: EventCardData[];
    years: string[];
    year: string | null;
    search: string | null;
    nextPage: number | null;
}

interface EventFeedResponse {
    events: EventCardData[];
    nextPage: number | null;
}

export default function EventIndex({ events, years, year, search, nextPage }: EventIndexProps) {
    const { auth } = usePage<SharedData>().props;
    const canManage = auth.can.manageEvents;
    const [loadedEvents, setLoadedEvents] = useState<EventCardData[]>(events);
    const [page, setPage] = useState<number | null>(nextPage);
    const [isLoadingMore, setIsLoadingMore] = useState(false);
    const [searchInput, setSearchInput] = useState(search ?? '');
    const sentinelRef = useRef<HTMLDivElement | null>(null);

    useEffect(() => {
        setLoadedEvents(events);
        setPage(nextPage);
    }, [events, nextPage]);

    useEffect(() => {
        setSearchInput(search ?? '');
    }, [search]);

    const loadMore = useCallback(async () => {
        if (page === null || isLoadingMore) {
            return;
        }

        setIsLoadingMore(true);

        const params = new URLSearchParams({ page: String(page) });
        if (year) {
            params.set('year', year);
        }
        if (search) {
            params.set('search', search);
        }

        const response = await fetch(`${route('events.feed')}?${params.toString()}`, {
            headers: { Accept: 'application/json' },
        });
        const data: EventFeedResponse = await response.json();

        setLoadedEvents((previous) => [...previous, ...data.events]);
        setPage(data.nextPage);
        setIsLoadingMore(false);
    }, [page, isLoadingMore, year, search]);

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

    const visitWithFilters = (nextYear: string | null, nextSearch: string) => {
        const params: Record<string, string> = {};
        if (nextYear && nextYear !== 'all') {
            params.year = nextYear;
        }
        if (nextSearch.trim() !== '') {
            params.search = nextSearch.trim();
        }

        router.get(route('events.index'), params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const setYearFilter = (nextYear: string) => visitWithFilters(nextYear, searchInput);

    useEffect(() => {
        const trimmed = searchInput.trim();
        if (trimmed === (search ?? '')) {
            return;
        }

        const timeout = setTimeout(() => visitWithFilters(year, searchInput), 400);
        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [searchInput]);

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
                        {canManage && (
                            <Button asChild>
                                <Link href={route('events.create')}>New event</Link>
                            </Button>
                        )}
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

                    <input
                        type="search"
                        value={searchInput}
                        onChange={(event) => setSearchInput(event.target.value)}
                        placeholder="Search events by title, description, or airport…"
                        aria-label="Search events"
                        className="border-border bg-muted/40 placeholder:text-muted-foreground w-full max-w-md rounded-lg border px-3 py-2 text-sm focus:outline-none"
                    />

                    {loadedEvents.length > 0 ? (
                        <>
                            <div className="grid [grid-template-columns:repeat(auto-fill,minmax(320px,1fr))] gap-4">
                                {loadedEvents.map((event) => (
                                    <EventCard key={event.id} event={event} canManage={canManage} />
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
                            No upcoming events match {search ? 'your search' : 'this filter'} yet.
                        </div>
                    )}
                </section>

                <SiteFooter />
            </div>
        </div>
    );
}
