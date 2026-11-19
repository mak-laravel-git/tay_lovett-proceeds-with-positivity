import type { Editor } from '@tiptap/react';
import { useEditorState } from '@tiptap/react';
import {
    AlignCenter,
    AlignJustify,
    AlignLeft,
    AlignRight,
    Bold,
    ChevronsDownUp,
    Code,
    Code2,
    Eraser,
    Highlighter,
    Image as ImageIcon,
    Italic,
    Link2,
    List,
    ListChecks,
    ListOrdered,
    ListTree,
    Minus,
    Palette,
    Quote,
    Redo2,
    Strikethrough,
    Subscript as SubscriptIcon,
    Superscript as SuperscriptIcon,
    Table as TableIcon,
    Underline as UnderlineIcon,
    Undo2,
} from 'lucide-react';
import type { ComponentType, ReactNode } from 'react';
import { useState } from 'react';
import { ImageDialog, LinkDialog } from '@/components/legal/editor-dialogs';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Toggle } from '@/components/ui/toggle';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';

const palette = [
    '#0a1018', '#303b48', '#526475', '#ed2948', '#bb1831', '#76d8f8', '#0f6f8f',
    '#16a34a', '#d97706', '#7c3aed', '#db2777', '#ffffff',
];

function Tip({ label, children }: { label: string; children: ReactNode }) {
    return (
        <Tooltip>
            <TooltipTrigger asChild>{children}</TooltipTrigger>
            <TooltipContent>{label}</TooltipContent>
        </Tooltip>
    );
}

function Mark({
    label,
    icon: Icon,
    active,
    onPress,
    disabled,
}: {
    label: string;
    icon: ComponentType<{ className?: string }>;
    active?: boolean;
    onPress: () => void;
    disabled?: boolean;
}) {
    return (
        <Tip label={label}>
            <Toggle
                size="sm"
                pressed={active ?? false}
                onPressedChange={onPress}
                disabled={disabled}
                aria-label={label}
                className="size-8 min-w-8 p-0 data-[state=on]:bg-brand data-[state=on]:text-white"
            >
                <Icon className="size-4" />
            </Toggle>
        </Tip>
    );
}

function Action({
    label,
    icon: Icon,
    onPress,
    disabled,
}: {
    label: string;
    icon: ComponentType<{ className?: string }>;
    onPress: () => void;
    disabled?: boolean;
}) {
    return (
        <Tip label={label}>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                className="size-8"
                onClick={onPress}
                disabled={disabled}
                aria-label={label}
            >
                <Icon className="size-4" />
            </Button>
        </Tip>
    );
}

export function EditorToolbar({ editor }: { editor: Editor }) {
    const [linkOpen, setLinkOpen] = useState(false);
    const [imageOpen, setImageOpen] = useState(false);

    const state = useEditorState({
        editor,
        selector: ({ editor: current }) => ({
            bold: current.isActive('bold'),
            italic: current.isActive('italic'),
            underline: current.isActive('underline'),
            strike: current.isActive('strike'),
            code: current.isActive('code'),
            highlight: current.isActive('highlight'),
            subscript: current.isActive('subscript'),
            superscript: current.isActive('superscript'),
            link: current.isActive('link'),
            bulletList: current.isActive('bulletList'),
            orderedList: current.isActive('orderedList'),
            taskList: current.isActive('taskList'),
            blockquote: current.isActive('blockquote'),
            codeBlock: current.isActive('codeBlock'),
            details: current.isActive('details'),
            inTable: current.isActive('table'),
            align: (['left', 'center', 'right', 'justify'] as const).find((a) => current.isActive({ textAlign: a })) ?? 'left',
            heading: ([1, 2, 3, 4, 5, 6] as const).find((level) => current.isActive('heading', { level })) ?? 0,
            color: (current.getAttributes('textStyle').color as string | undefined) ?? '',
            canUndo: current.can().undo(),
            canRedo: current.can().redo(),
        }),
    });

    const chain = () => editor.chain().focus();

    return (
        <div className="flex flex-wrap items-center gap-0.5 border-b border-line bg-sky-wash px-2 py-1.5">
            <Action label="Undo" icon={Undo2} onPress={() => chain().undo().run()} disabled={!state.canUndo} />
            <Action label="Redo" icon={Redo2} onPress={() => chain().redo().run()} disabled={!state.canRedo} />
            <Separator orientation="vertical" className="mx-1 h-6" />

            <Select
                value={String(state.heading)}
                onValueChange={(value) =>
                    value === '0'
                        ? chain().setParagraph().run()
                        : chain().toggleHeading({ level: Number(value) as 1 | 2 | 3 | 4 | 5 | 6 }).run()
                }
            >
                <SelectTrigger size="sm" className="h-8 w-32 bg-white" aria-label="Text style">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="0">Paragraph</SelectItem>
                    {[1, 2, 3, 4, 5, 6].map((level) => (
                        <SelectItem key={level} value={String(level)}>
                            Heading {level}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            <Separator orientation="vertical" className="mx-1 h-6" />

            <Mark label="Bold" icon={Bold} active={state.bold} onPress={() => chain().toggleBold().run()} />
            <Mark label="Italic" icon={Italic} active={state.italic} onPress={() => chain().toggleItalic().run()} />
            <Mark label="Underline" icon={UnderlineIcon} active={state.underline} onPress={() => chain().toggleUnderline().run()} />
            <Mark label="Strikethrough" icon={Strikethrough} active={state.strike} onPress={() => chain().toggleStrike().run()} />
            <Mark label="Inline code" icon={Code} active={state.code} onPress={() => chain().toggleCode().run()} />
            <Mark label="Highlight" icon={Highlighter} active={state.highlight} onPress={() => chain().toggleHighlight().run()} />
            <Mark label="Subscript" icon={SubscriptIcon} active={state.subscript} onPress={() => chain().toggleSubscript().run()} />
            <Mark label="Superscript" icon={SuperscriptIcon} active={state.superscript} onPress={() => chain().toggleSuperscript().run()} />

            <Popover>
                <Tip label="Text color">
                    <PopoverTrigger asChild>
                        <Button type="button" variant="ghost" size="icon" className="size-8" aria-label="Text color">
                            <Palette className="size-4" style={{ color: state.color || undefined }} />
                        </Button>
                    </PopoverTrigger>
                </Tip>
                <PopoverContent className="w-52 p-3" align="start">
                    <p className="mb-2 text-xs font-bold text-muted-ink">Text color</p>
                    <div className="grid grid-cols-6 gap-1.5">
                        {palette.map((color) => (
                            <button
                                key={color}
                                type="button"
                                aria-label={`Color ${color}`}
                                onClick={() => chain().setColor(color).run()}
                                className="size-6 rounded-md border border-line"
                                style={{ background: color }}
                            />
                        ))}
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        className="mt-3 w-full"
                        onClick={() => chain().unsetColor().run()}
                    >
                        Default color
                    </Button>
                </PopoverContent>
            </Popover>
            <Separator orientation="vertical" className="mx-1 h-6" />

            <Mark label="Align left" icon={AlignLeft} active={state.align === 'left'} onPress={() => chain().setTextAlign('left').run()} />
            <Mark label="Align center" icon={AlignCenter} active={state.align === 'center'} onPress={() => chain().setTextAlign('center').run()} />
            <Mark label="Align right" icon={AlignRight} active={state.align === 'right'} onPress={() => chain().setTextAlign('right').run()} />
            <Mark label="Justify" icon={AlignJustify} active={state.align === 'justify'} onPress={() => chain().setTextAlign('justify').run()} />
            <Separator orientation="vertical" className="mx-1 h-6" />

            <Mark label="Bullet list" icon={List} active={state.bulletList} onPress={() => chain().toggleBulletList().run()} />
            <Mark label="Numbered list" icon={ListOrdered} active={state.orderedList} onPress={() => chain().toggleOrderedList().run()} />
            <Mark label="Checklist" icon={ListChecks} active={state.taskList} onPress={() => chain().toggleTaskList().run()} />
            <Mark label="Quote" icon={Quote} active={state.blockquote} onPress={() => chain().toggleBlockquote().run()} />
            <Mark label="Code block" icon={Code2} active={state.codeBlock} onPress={() => chain().toggleCodeBlock().run()} />
            <Action label="Divider" icon={Minus} onPress={() => chain().setHorizontalRule().run()} />
            <Separator orientation="vertical" className="mx-1 h-6" />

            <Mark label="Link" icon={Link2} active={state.link} onPress={() => setLinkOpen(true)} />
            <Action label="Image" icon={ImageIcon} onPress={() => setImageOpen(true)} />

            <DropdownMenu>
                <Tip label="Table">
                    <DropdownMenuTrigger asChild>
                        <Button type="button" variant="ghost" size="icon" className="size-8" aria-label="Table">
                            <TableIcon className="size-4" />
                        </Button>
                    </DropdownMenuTrigger>
                </Tip>
                <DropdownMenuContent align="start" className="w-56">
                    <DropdownMenuItem onSelect={() => chain().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run()}>
                        Insert 3 × 3 table
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuLabel className="text-xs text-muted-foreground">Edit the table at the cursor</DropdownMenuLabel>
                    <DropdownMenuItem disabled={!state.inTable} onSelect={() => chain().addRowBefore().run()}>Add row above</DropdownMenuItem>
                    <DropdownMenuItem disabled={!state.inTable} onSelect={() => chain().addRowAfter().run()}>Add row below</DropdownMenuItem>
                    <DropdownMenuItem disabled={!state.inTable} onSelect={() => chain().addColumnBefore().run()}>Add column left</DropdownMenuItem>
                    <DropdownMenuItem disabled={!state.inTable} onSelect={() => chain().addColumnAfter().run()}>Add column right</DropdownMenuItem>
                    <DropdownMenuItem disabled={!state.inTable} onSelect={() => chain().toggleHeaderRow().run()}>Toggle header row</DropdownMenuItem>
                    <DropdownMenuItem disabled={!state.inTable} onSelect={() => chain().mergeOrSplit().run()}>Merge or split cells</DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem disabled={!state.inTable} onSelect={() => chain().deleteRow().run()}>Delete row</DropdownMenuItem>
                    <DropdownMenuItem disabled={!state.inTable} onSelect={() => chain().deleteColumn().run()}>Delete column</DropdownMenuItem>
                    <DropdownMenuItem
                        disabled={!state.inTable}
                        variant="destructive"
                        onSelect={() => chain().deleteTable().run()}
                    >
                        Delete table
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>

            <Mark
                label="Collapsible section"
                icon={ChevronsDownUp}
                active={state.details}
                onPress={() => (state.details ? chain().unsetDetails().run() : chain().setDetails().run())}
            />
            <Action
                label="Table of contents"
                icon={ListTree}
                onPress={() => chain().insertContent({ type: 'tocBlock' }).run()}
            />
            <Separator orientation="vertical" className="mx-1 h-6" />
            <Action
                label="Clear formatting"
                icon={Eraser}
                onPress={() => chain().unsetAllMarks().clearNodes().run()}
            />

            <LinkDialog editor={editor} open={linkOpen} onOpenChange={setLinkOpen} />
            <ImageDialog editor={editor} open={imageOpen} onOpenChange={setImageOpen} />
        </div>
    );
}
