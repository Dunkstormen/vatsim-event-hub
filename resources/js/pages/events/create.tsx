import { EventForm } from '@/components/events/event-form';
import { SiteFooter } from '@/components/site-footer';
import { SiteHeader } from '@/components/site-header';
import { Head } from '@inertiajs/react';

export default function EventCreate() {
    return (
        <div className="dark">
            <Head title="New event" />

            <div className="bg-background text-foreground min-h-screen text-sm">
                <SiteHeader />

                <section className="mx-auto flex max-w-2xl flex-col gap-6 px-6 py-12">
                    <h1 className="text-3xl font-semibold tracking-tight">New event</h1>
                    <EventForm submitUrl={route('events.store')} submitMethod="post" submitLabel="Create event" />
                </section>

                <SiteFooter />
            </div>
        </div>
    );
}
