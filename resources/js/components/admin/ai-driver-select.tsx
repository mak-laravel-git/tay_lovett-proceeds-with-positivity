import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { AiDriverOption } from '@/types/ai';

export function AiDriverSelect({
    drivers,
    value,
    onValueChange,
    name = 'driver',
    disabled,
}: {
    drivers: AiDriverOption[];
    value: string;
    onValueChange: (value: string) => void;
    name?: string;
    disabled?: boolean;
}) {
    return (
        <>
            <input type="hidden" name={name} value={value} />
            <Select
                value={value}
                onValueChange={onValueChange}
                disabled={disabled}
            >
                <SelectTrigger className="w-full bg-white" aria-label="Provider type">
                    <SelectValue placeholder="Choose a provider" />
                </SelectTrigger>
                <SelectContent>
                    {drivers.map((driver) => (
                        <SelectItem key={driver.value} value={driver.value}>
                            {driver.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </>
    );
}
