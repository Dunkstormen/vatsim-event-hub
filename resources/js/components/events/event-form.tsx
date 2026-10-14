import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { cn } from '@/lib/utils';
import { useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

export type EventStatus = 'draft' | 'published' | 'cancelled';
export type RecurrenceFrequency = 'weekly' | 'biweekly' | 'monthly' | 'quarterly';

export interface EventFormValues {
    title: string;
    description: string;
    status: EventStatus;
    starts_at: string;
    ends_at: string;
    recurrence_frequency: RecurrenceFrequency | '';
}

interface EventFormProps {
    event?: EventFormValues & { banner_path: string | null };
    submitUrl: string;
    submitMethod: 'post' | 'put';
    submitLabel: string;
}

function toDateTimeLocal(iso: string): string {
    return iso.slice(0, 16);
}

export function EventForm({ event, submitUrl, submitMethod, submitLabel }: EventFormProps) {
    const [bannerPreview, setBannerPreview] = useState<string | null>(event?.banner_path ? `/storage/${event.banner_path}` : null);

    const { data, setData, post, put, processing, errors } = useForm({
        title: event?.title ?? '',
        description: event?.description ?? '',
        status: event?.status ?? ('draft' as EventStatus),
        starts_at: event?.starts_at ? toDateTimeLocal(event.starts_at) : '',
        ends_at: event?.ends_at ? toDateTimeLocal(event.ends_at) : '',
        recurrence_frequency: event?.recurrence_frequency ?? ('' as RecurrenceFrequency | ''),
        banner: null as File | null,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        const options = { preserveScroll: true, forceFormData: true };

        if (submitMethod === 'post') {
            post(submitUrl, options);
        } else {
            put(submitUrl, options);
        }
    };

    function onBannerChange(file: File | null) {
        setData('banner', file);
        setBannerPreview(file ? URL.createObjectURL(file) : null);
    }

    return (
        <form onSubmit={submit} className="flex flex-col gap-6">
            <div className="grid gap-2">
                <Label htmlFor="title">Title</Label>
                <Input id="title" value={data.title} onChange={(e) => setData('title', e.target.value)} />
                <InputError message={errors.title} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="description">Description</Label>
                <textarea
                    id="description"
                    rows={5}
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                    className={cn(
                        'flex w-full rounded-md border border-input bg-background px-3 py-2 text-base ring-offset-background placeholder:text-muted-foreground focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
                    )}
                />
                <InputError message={errors.description} />
            </div>

            <div className="grid grid-cols-2 gap-4">
                <div className="grid gap-2">
                    <Label htmlFor="starts_at">Starts at (UTC)</Label>
                    <Input
                        id="starts_at"
                        type="datetime-local"
                        value={data.starts_at}
                        onChange={(e) => setData('starts_at', e.target.value)}
                    />
                    <InputError message={errors.starts_at} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="ends_at">Ends at (UTC)</Label>
                    <Input id="ends_at" type="datetime-local" value={data.ends_at} onChange={(e) => setData('ends_at', e.target.value)} />
                    <InputError message={errors.ends_at} />
                </div>
            </div>

            <div className="grid grid-cols-2 gap-4">
                <div className="grid gap-2">
                    <Label htmlFor="status">Status</Label>
                    <Select value={data.status} onValueChange={(value) => setData('status', value as EventStatus)}>
                        <SelectTrigger id="status">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="draft">Draft</SelectItem>
                            <SelectItem value="published">Published</SelectItem>
                            <SelectItem value="cancelled">Cancelled</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={errors.status} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="recurrence_frequency">Recurrence</Label>
                    <Select
                        value={data.recurrence_frequency === '' ? 'none' : data.recurrence_frequency}
                        onValueChange={(value) => setData('recurrence_frequency', value === 'none' ? '' : (value as RecurrenceFrequency))}
                    >
                        <SelectTrigger id="recurrence_frequency">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="none">One-time</SelectItem>
                            <SelectItem value="weekly">Weekly</SelectItem>
                            <SelectItem value="biweekly">Every two weeks</SelectItem>
                            <SelectItem value="monthly">Monthly</SelectItem>
                            <SelectItem value="quarterly">Quarterly</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={errors.recurrence_frequency} />
                </div>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="banner">Banner image</Label>
                {bannerPreview && <img src={bannerPreview} alt="Banner preview" className="h-[120px] w-full rounded-md object-cover" />}
                <Input id="banner" type="file" accept="image/*" onChange={(e) => onBannerChange(e.target.files?.[0] ?? null)} />
                <InputError message={errors.banner} />
            </div>

            <div className="flex justify-end">
                <Button type="submit" disabled={processing}>
                    {submitLabel}
                </Button>
            </div>
        </form>
    );
}
