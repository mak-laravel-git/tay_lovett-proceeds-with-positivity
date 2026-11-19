import { Head, Link, usePage } from '@inertiajs/react';
import {
    Bot,
    CircleDollarSign,
    type LucideIcon,
    Quote,
    Users,
} from 'lucide-react';
import { Area, AreaChart, Bar, BarChart, CartesianGrid, XAxis, YAxis } from 'recharts';
import { Stagger, StaggerItem } from '@/components/motion/reveal';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    type ChartConfig,
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
} from '@/components/ui/chart';
import { cn } from '@/lib/utils';

type Stats = {
    users: number;
    administrators: number;
    published_quotes: number;
    pending_quotes: number;
    pending_withdrawals: number;
    generation_logs: number;
};

type TrendPoint = {
    date: string;
    generations: number;
};

type StatusPoint = {
    status: string;
    label: string;
    count: number;
};

const generationConfig = {
    generations: {
        label: 'Generations',
        color: '#76d8f8',
    },
} satisfies ChartConfig;

const statusConfig = {
    count: {
        label: 'Quotes',
        color: '#ed2948',
    },
} satisfies ChartConfig;

const statThemes = {
    blue: {
        card: 'border-[#76d8f8]/60 bg-[#eef9fd]',
        icon: 'bg-[#76d8f8]/25 text-[#0f6f8f]',
        value: 'text-[#0a1018]',
        label: 'text-[#3d6a7a]',
        hint: 'text-[#526475]',
        accent: 'bg-[#76d8f8]',
    },
    red: {
        card: 'border-[#ed2948]/45 bg-[#fff1f4]',
        icon: 'bg-[#ed2948]/15 text-[#ed2948]',
        value: 'text-[#ed2948]',
        label: 'text-[#a1283c]',
        hint: 'text-[#8a5560]',
        accent: 'bg-[#ed2948]',
    },
    navy: {
        card: 'border-[#243846] bg-[#0a1018]',
        icon: 'bg-[#182c38] text-[#76d8f8]',
        value: 'text-white',
        label: 'text-[#9ec3d1]',
        hint: 'text-[#8fb0bd]',
        accent: 'bg-[#ed2948]',
    },
    cyan: {
        card: 'border-[#76d8f8]/50 bg-gradient-to-br from-[#e7f7ff] to-[#fff0f3]',
        icon: 'bg-[#0a1018] text-[#76d8f8]',
        value: 'text-[#0a1018]',
        label: 'text-[#41516b]',
        hint: 'text-[#526475]',
        accent: 'bg-gradient-to-r from-[#76d8f8] to-[#ed2948]',
    },
} as const;

type StatTheme = keyof typeof statThemes;

export default function AdminHome({
    stats,
    generationTrend,
    quoteStatusChart,
}: {
    stats: Stats;
    generationTrend: TrendPoint[];
    quoteStatusChart: StatusPoint[];
}) {
    const { site } = usePage().props;

    return (
        <>
            <Head title="Overview" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto p-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Overview
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Operational snapshot for {site.name}.
                    </p>
                </div>

                <Stagger className="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
                    <StaggerItem>
                        <StatCard
                            title="Users"
                            value={stats.users}
                            hint={`${stats.administrators} administrator${stats.administrators === 1 ? '' : 's'}`}
                            href="/admin/users"
                            icon={Users}
                            theme="blue"
                        />
                    </StaggerItem>
                    <StaggerItem>
                        <StatCard
                            title="Published quotes"
                            value={stats.published_quotes}
                            hint={`${stats.pending_quotes} pending review`}
                            href="/admin/quotes"
                            icon={Quote}
                            theme="red"
                        />
                    </StaggerItem>
                    <StaggerItem>
                        <StatCard
                            title="Pending withdrawals"
                            value={stats.pending_withdrawals}
                            hint="Manual review queue"
                            href="/admin/withdrawals"
                            icon={CircleDollarSign}
                            theme="navy"
                        />
                    </StaggerItem>
                    <StaggerItem>
                        <StatCard
                            title="AI generation logs"
                            value={stats.generation_logs}
                            hint="Stored usage rows"
                            href="/admin/ai/usage"
                            icon={Bot}
                            theme="cyan"
                        />
                    </StaggerItem>
                </Stagger>

                <div className="grid gap-4 xl:grid-cols-2">
                    <Card className="gap-4 overflow-hidden border-[#76d8f8]/50 bg-[#eef9fd] py-0 shadow-sm">
                        <div className="h-1.5 bg-[#76d8f8]" />
                        <CardHeader className="pt-2">
                            <CardTitle className="text-[#0a1018]">
                                AI generations
                            </CardTitle>
                            <CardDescription>
                                Last 7 days of generation attempts
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="pb-6">
                            <ChartContainer
                                config={generationConfig}
                                className="aspect-auto h-[260px] w-full"
                            >
                                <AreaChart
                                    accessibilityLayer
                                    data={generationTrend}
                                    margin={{ left: 8, right: 8, top: 8 }}
                                >
                                    <CartesianGrid vertical={false} />
                                    <XAxis
                                        dataKey="date"
                                        tickLine={false}
                                        axisLine={false}
                                        tickMargin={8}
                                    />
                                    <YAxis
                                        allowDecimals={false}
                                        tickLine={false}
                                        axisLine={false}
                                        width={28}
                                    />
                                    <ChartTooltip
                                        content={
                                            <ChartTooltipContent indicator="line" />
                                        }
                                    />
                                    <Area
                                        dataKey="generations"
                                        type="natural"
                                        fill="var(--color-generations)"
                                        fillOpacity={0.3}
                                        stroke="var(--color-generations)"
                                        strokeWidth={2}
                                    />
                                </AreaChart>
                            </ChartContainer>
                        </CardContent>
                    </Card>

                    <Card className="gap-4 overflow-hidden border-[#ed2948]/35 bg-[#fff5f7] py-0 shadow-sm">
                        <div className="h-1.5 bg-[#ed2948]" />
                        <CardHeader className="pt-2">
                            <CardTitle className="text-[#0a1018]">
                                Quote statuses
                            </CardTitle>
                            <CardDescription>
                                Current publication pipeline
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="pb-6">
                            <ChartContainer
                                config={statusConfig}
                                className="aspect-auto h-[260px] w-full"
                            >
                                <BarChart
                                    accessibilityLayer
                                    data={quoteStatusChart}
                                    margin={{ left: 8, right: 8, top: 8 }}
                                >
                                    <CartesianGrid vertical={false} />
                                    <XAxis
                                        dataKey="label"
                                        tickLine={false}
                                        axisLine={false}
                                        tickMargin={8}
                                    />
                                    <YAxis
                                        allowDecimals={false}
                                        tickLine={false}
                                        axisLine={false}
                                        width={28}
                                    />
                                    <ChartTooltip
                                        content={<ChartTooltipContent />}
                                    />
                                    <Bar
                                        dataKey="count"
                                        fill="var(--color-count)"
                                        radius={6}
                                    />
                                </BarChart>
                            </ChartContainer>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

function StatCard({
    title,
    value,
    hint,
    href,
    icon: Icon,
    theme,
}: {
    title: string;
    value: number;
    hint: string;
    href: string;
    icon: LucideIcon;
    theme: StatTheme;
}) {
    const colors = statThemes[theme];

    return (
        <Link href={href} className="block min-w-0">
            <Card
                className={cn(
                    'gap-3 overflow-hidden py-0 shadow-sm transition-colors hover:brightness-[0.98]',
                    colors.card,
                )}
            >
                <div className={cn('h-1.5 w-full', colors.accent)} />
                <CardHeader className="gap-3 px-5 pt-4 pb-0">
                    <div className="flex items-center justify-between gap-3">
                        <p
                            className={cn(
                                'text-sm font-semibold',
                                colors.label,
                            )}
                        >
                            {title}
                        </p>
                        <span
                            className={cn(
                                'inline-flex size-9 shrink-0 items-center justify-center rounded-lg',
                                colors.icon,
                            )}
                        >
                            <Icon className="size-4" aria-hidden />
                        </span>
                    </div>
                    <p
                        className={cn(
                            'text-3xl font-semibold tabular-nums tracking-tight',
                            colors.value,
                        )}
                    >
                        {value}
                    </p>
                </CardHeader>
                <CardContent className="px-5 pt-0 pb-5">
                    <p className={cn('text-xs', colors.hint)}>{hint}</p>
                </CardContent>
            </Card>
        </Link>
    );
}

AdminHome.layout = {
    breadcrumbs: [
        {
            title: 'Overview',
            href: '/admin',
        },
    ],
};
