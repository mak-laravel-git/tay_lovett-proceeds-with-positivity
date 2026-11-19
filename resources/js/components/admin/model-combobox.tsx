import { Check, ChevronsUpDown } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';

export function ModelCombobox({
    id,
    options,
    value,
    onValueChange,
}: {
    id?: string;
    options: string[];
    value: string;
    onValueChange: (value: string) => void;
}) {
    const [open, setOpen] = useState(false);
    const [search, setSearch] = useState('');
    const typed = search.trim();
    const alreadyListed = options.some(
        (option) => option.toLowerCase() === typed.toLowerCase(),
    );

    const choose = (next: string) => {
        onValueChange(next);
        setSearch('');
        setOpen(false);
    };

    return (
        <Popover modal open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    id={id}
                    type="button"
                    variant="outline"
                    role="combobox"
                    aria-expanded={open}
                    className="w-full justify-between bg-white font-normal"
                >
                    <span
                        className={cn(
                            'truncate',
                            value === '' && 'text-muted-foreground',
                        )}
                    >
                        {value || 'Select or type a model id'}
                    </span>
                    <ChevronsUpDown className="size-4 shrink-0 opacity-50" />
                </Button>
            </PopoverTrigger>
            <PopoverContent
                className="w-(--radix-popover-trigger-width) p-0"
                align="start"
            >
                <Command>
                    <CommandInput
                        placeholder="Search or type your own model id..."
                        value={search}
                        onValueChange={setSearch}
                    />
                    <CommandList>
                        <CommandEmpty>No matching model.</CommandEmpty>
                        <CommandGroup heading="Other">
                            <CommandItem
                                value={`custom:${typed || '__empty__'}`}
                                disabled={typed === '' || alreadyListed}
                                onSelect={() => choose(typed)}
                            >
                                {typed === ''
                                    ? 'Type above to set a model id not in this list'
                                    : `Use "${typed}"`}
                            </CommandItem>
                        </CommandGroup>
                        <CommandGroup heading="Available models">
                            {options.map((option) => (
                                <CommandItem
                                    key={option}
                                    value={option}
                                    onSelect={() => choose(option)}
                                >
                                    <Check
                                        className={cn(
                                            'size-4',
                                            value === option
                                                ? 'opacity-100'
                                                : 'opacity-0',
                                        )}
                                    />
                                    {option}
                                </CommandItem>
                            ))}
                        </CommandGroup>
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    );
}
