import { EventForm, type EventFormValues } from '@/components/events/event-form';
import { SiteFooter } from '@/components/site-footer';
import { SiteHeader } from '@/components/site-header';
import { Head } from '@inertiajs/react';

interface EventEditProps {
    event: EventFormValues & { id: number; banner_path: string | null };
}

export default function EventEdit({ event }: EventEditProps) {
    return (
        <div className="dark">
            <Head title={`Edit ${event.title}`} />

            <div className="bg-background text-foreground min-h-screen text-sm">
                <SiteHeader />

                <section className="mx-auto flex max-w-2xl flex-col gap-6 px-6 py-12">
                    <h1 className="text-3xl font-semibold tracking-tight">Edit event</h1>
                    <EventForm event={event} submitUrl={route('events.update', event.id)} submitMethod="put" submitLabel="Save changes" />
                </section>

                <SiteFooter />
            </div>
        </div>
    );
}
