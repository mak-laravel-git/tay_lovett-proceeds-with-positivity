import {
    Laptop,
    Monitor,
    Smartphone,
    SlidersHorizontal,
    Tablet,
} from 'lucide-react';
import { useState } from 'react';
import { LegalProse } from '@/components/legal/legal-prose';
import { LegalProseBoundary } from '@/components/legal/legal-prose-boundary';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Slider } from '@/components/ui/slider';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { cn } from '@/lib/utils';

const devices = [
    { value: 'desktop', label: 'Desktop (1920×1080)', icon: Monitor, width: 1920 },
    { value: 'laptop', label: 'Laptop (1366×768)', icon: Laptop, width: 1366 },
    { value: 'tablet', label: 'Tablet (768×1024)', icon: Tablet, width: 768 },
    { value: 'mobile', label: 'Mobile (390×844)', icon: Smartphone, width: 390 },
    { value: 'custom', label: 'Custom width', icon: SlidersHorizontal, width: 0 },
] as const;

type DeviceValue = (typeof devices)[number]['value'];

/**
 * Shows the page the way visitors will see it, after the same filtering they get. The frame is scaled down to
 * fit the available space rather than forcing horizontal scrolling, so even the 1920px desktop preset is usable
 * inside the admin panel.
 */
export function LegalPreview({ title, html }: { title: string; html: string }) {
    const [device, setDevice] = useState<DeviceValue>('desktop');
    const [customWidth, setCustomWidth] = useState(1024);

    const width = device === 'custom' ? customWidth : (devices.find((item) => item.value === device)?.width ?? 1024);

    return (
        <div className="overflow-hidden rounded-2xl border border-line bg-canvas">
            <div className="flex flex-wrap items-center justify-between gap-2 border-b border-line bg-sky-wash px-3 py-2">
                <p className="text-xs text-muted-ink">Preview of the public page · {width}px wide</p>
                <div className="flex items-center gap-2">
                    <ToggleGroup
                        type="single"
                        size="sm"
                        variant="outline"
                        value={device}
                        onValueChange={(value) => value && setDevice(value as DeviceValue)}
                    >
                        {devices.map(({ value, label, icon: Icon }) => (
                            <ToggleGroupItem key={value} value={value} aria-label={label} className="bg-white">
                                <Icon className="size-4" />
                            </ToggleGroupItem>
                        ))}
                    </ToggleGroup>
                    {device === 'custom' ? (
                        <Popover>
                            <PopoverTrigger asChild>
                                <button
                                    type="button"
                                    className="rounded-lg border border-line bg-white px-2.5 py-1.5 text-xs font-bold text-ink"
                                >
                                    {customWidth}px
                                </button>
                            </PopoverTrigger>
                            <PopoverContent className="w-64" align="end">
                                <p className="mb-2 text-xs font-bold text-muted-ink">Preview width</p>
                                <Slider
                                    min={320}
                                    max={1920}
                                    step={10}
                                    value={[customWidth]}
                                    onValueChange={([value]) => setCustomWidth(value)}
                                />
                                <div className="mt-1 flex justify-between text-[11px] text-muted-ink">
                                    <span>320px</span>
                                    <span>1920px</span>
                                </div>
                            </PopoverContent>
                        </Popover>
                    ) : null}
                </div>
            </div>
            <div className="max-h-[75vh] overflow-auto p-4">
                <div
                    className={cn(
                        'mx-auto rounded-2xl border bg-white p-6 transition-[width]',
                        width > 820 && 'origin-top',
                    )}
                    style={{ width }}
                >
                    <h1 className="mb-4 text-3xl font-black text-ink">{title}</h1>
                    <LegalProseBoundary html={html}>
                        <LegalProse html={html} />
                    </LegalProseBoundary>
                </div>
            </div>
        </div>
    );
}
