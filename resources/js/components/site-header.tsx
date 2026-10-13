import { Button } from '@/components/ui/button';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';

const NAV_LINKS = [
    { label: 'Events', href: '/#events' },
    { label: 'Calendar', href: '#' },
    { label: 'Briefings', href: '#' },
    { label: 'About', href: '#' },
];

export function SiteHeader({ activeLabel = 'Events' }: { activeLabel?: string }) {
    const { auth } = usePage<SharedData>().props;

    return (
        <header className="border-border bg-background sticky top-0 z-10 border-b">
            <div className="mx-auto flex h-[60px] max-w-6xl items-center gap-5 px-6">
                <Link href={route('home')} className="flex flex-none items-center gap-2.5 font-semibold whitespace-nowrap">
                    <div className="bg-foreground text-background grid size-7 place-items-center rounded-md text-[11px]">XX</div>
                    Example vACC
                </Link>
                <nav className="flex min-w-0 flex-1 gap-1 overflow-hidden">
                    {NAV_LINKS.map((link) => (
                        <a
                            key={link.label}
                            href={link.href}
                            className={`rounded-md px-3 py-1.5 font-medium ${link.label === activeLabel ? 'text-foreground' : 'text-muted-foreground'}`}
                        >
                            {link.label}
                        </a>
                    ))}
                </nav>
                {auth.user ? (
                    <Link href={route('dashboard')} className="flex-none">
                        <Button size="sm">Dashboard</Button>
                    </Link>
                ) : (
                    <a href={route('auth.vatsim.redirect')} className="flex-none">
                        <Button size="sm">Log in with VATSIM</Button>
                    </a>
                )}
            </div>
        </header>
    );
}
