import { Head } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { show as showRegion } from '@/routes/regions';

type RangeOption = {
    value: 'day' | 'week' | 'month' | 'year';
    label: string;
};

type Region = {
    code: string;
    name: string;
    tile_row: number;
    tile_column: number;
    timezone: string;
    source_status: string;
    coverage_notes: string;
    status?: string;
    summary?: RegionDetail['summary'];
};

type Variable = {
    code: string;
    label: string;
    category: string;
    fuel_type: string | null;
    is_clean: boolean;
    value: number;
    unit: string;
    freshness_status: string;
    source: string;
};

type TrendPoint = {
    label: string;
    demand: number | null;
    generation: number | null;
};

type RegionDetail = {
    region: Region;
    range: RangeOption['value'];
    status: 'current' | 'stale' | 'unavailable';
    latest_observed_at: string | null;
    summary: {
        demand_mw: number | null;
        generation_mw: number | null;
        clean_share: {
            clean_mw: number;
            known_generation_mw: number;
            unknown_mw: number;
            percentage: number | null;
        };
    };
    source_mix: Variable[];
    trend: TrendPoint[];
    variables: Variable[];
    source_note: string;
};

type Source = {
    label: string;
    url: string;
    role: string;
};

type Props = {
    regions: Region[];
    initialRegion: RegionDetail;
    ranges: RangeOption[];
    sources: Source[];
};

const fuelColors: Record<string, string> = {
    hydro: '#0891b2',
    wind: '#16a34a',
    solar: '#eab308',
    nuclear: '#7c3aed',
    biomass: '#65a30d',
    gas: '#ef4444',
    coal: '#57534e',
    oil_diesel: '#c2410c',
    other: '#64748b',
};

export default function Welcome({
    regions,
    initialRegion,
    ranges,
    sources,
}: Props) {
    const [selectedRegion, setSelectedRegion] = useState(
        initialRegion.region.code,
    );
    const [selectedRange, setSelectedRange] =
        useState<RangeOption['value']>('day');
    const [detail, setDetail] = useState(initialRegion);
    const [isLoading, setIsLoading] = useState(false);

    const orderedRegions = useMemo(
        () =>
            [...regions].sort(
                (a, b) =>
                    a.tile_row - b.tile_row || a.tile_column - b.tile_column,
            ),
        [regions],
    );

    async function selectRegion(regionCode: string, range = selectedRange) {
        setSelectedRegion(regionCode);
        setIsLoading(true);

        const response = await fetch(
            showRegion.url(regionCode, { query: { range } }),
            {
                headers: { Accept: 'application/json' },
            },
        );

        if (response.ok) {
            setDetail((await response.json()) as RegionDetail);
        }

        setIsLoading(false);
    }

    async function selectRange(range: RangeOption['value']) {
        setSelectedRange(range);
        await selectRegion(selectedRegion, range);
    }

    return (
        <>
            <Head title="Canada Power Generation" />
            <main className="min-h-screen bg-[#f8faf8] text-[#17201b] dark:bg-[#111412] dark:text-[#f4f7f5]">
                <section className="border-b border-[#dfe7e1] bg-white dark:border-[#29322d] dark:bg-[#161a18]">
                    <div className="mx-auto flex max-w-7xl flex-col gap-5 px-4 py-5 sm:px-6 lg:px-8">
                        <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                            <div className="max-w-3xl">
                                <p className="text-sm font-semibold text-[#0f766e] dark:text-[#5eead4]">
                                    HFED/CCEI normalized electricity data
                                </p>
                                <h1 className="mt-2 text-3xl font-semibold sm:text-4xl">
                                    Canada Power Generation Visualizer
                                </h1>
                                <p className="mt-3 text-sm leading-6 text-[#526158] dark:text-[#b9c5bd]">
                                    Provincial and territorial demand, supply,
                                    generation source mix, freshness, and source
                                    limitations.
                                </p>
                            </div>
                            <div
                                className="flex flex-wrap gap-2"
                                aria-label="Time range"
                            >
                                {ranges.map((range) => (
                                    <button
                                        key={range.value}
                                        type="button"
                                        onClick={() =>
                                            void selectRange(range.value)
                                        }
                                        className={`h-10 rounded-md border px-4 text-sm font-medium transition ${
                                            selectedRange === range.value
                                                ? 'border-[#0f766e] bg-[#0f766e] text-white'
                                                : 'border-[#cbd8d0] bg-white text-[#253129] hover:border-[#0f766e] dark:border-[#3a463f] dark:bg-[#1d231f] dark:text-[#dce5df]'
                                        }`}
                                    >
                                        {range.label}
                                    </button>
                                ))}
                            </div>
                        </div>
                    </div>
                </section>

                <section className="mx-auto grid max-w-7xl gap-6 px-4 py-6 sm:px-6 lg:grid-cols-[minmax(0,1fr)_420px] lg:px-8">
                    <div className="flex flex-col gap-6">
                        <div>
                            <div className="mb-3 flex items-center justify-between gap-3">
                                <h2 className="text-lg font-semibold">
                                    Province and Territory Tile Map
                                </h2>
                                <span className="text-sm text-[#64746a] dark:text-[#aab6af]">
                                    {orderedRegions.length} regions
                                </span>
                            </div>
                            <div className="grid grid-cols-3 gap-2 sm:grid-cols-5 lg:grid-cols-7">
                                {orderedRegions.map((region) => (
                                    <button
                                        key={region.code}
                                        type="button"
                                        onClick={() =>
                                            void selectRegion(region.code)
                                        }
                                        className={`aspect-[1.2/1] rounded-md border p-3 text-left transition ${
                                            selectedRegion === region.code
                                                ? 'border-[#0f766e] bg-[#dff7f2] shadow-[inset_0_0_0_1px_#0f766e] dark:bg-[#103c36]'
                                                : 'border-[#d6e2da] bg-white hover:border-[#0f766e] dark:border-[#344039] dark:bg-[#1a201c]'
                                        }`}
                                        style={{
                                            gridColumnStart: Math.min(
                                                region.tile_column,
                                                7,
                                            ),
                                        }}
                                    >
                                        <span className="block text-xl font-semibold">
                                            {region.code}
                                        </span>
                                        <span className="mt-1 block text-xs leading-4 text-[#607067] dark:text-[#b1bdb6]">
                                            {region.name}
                                        </span>
                                        <span
                                            className={`mt-2 inline-block h-2 w-2 rounded-full ${region.status === 'unavailable' ? 'bg-[#d97706]' : 'bg-[#0f766e]'}`}
                                        />
                                    </button>
                                ))}
                            </div>
                        </div>

                        <div className="grid gap-4 md:grid-cols-3">
                            <Metric
                                label="Demand"
                                value={detail.summary.demand_mw}
                                unit="MW"
                            />
                            <Metric
                                label="Generation"
                                value={detail.summary.generation_mw}
                                unit="MW"
                            />
                            <Metric
                                label="Clean Share"
                                value={detail.summary.clean_share.percentage}
                                unit="%"
                            />
                        </div>

                        <div className="grid gap-6 xl:grid-cols-2">
                            <section className="rounded-md border border-[#d6e2da] bg-white p-4 dark:border-[#344039] dark:bg-[#1a201c]">
                                <h2 className="text-base font-semibold">
                                    Generation Source Mix
                                </h2>
                                <SourceMixChart variables={detail.source_mix} />
                            </section>
                            <section className="rounded-md border border-[#d6e2da] bg-white p-4 dark:border-[#344039] dark:bg-[#1a201c]">
                                <h2 className="text-base font-semibold">
                                    Demand and Supply Trend
                                </h2>
                                <TrendChart points={detail.trend} />
                            </section>
                        </div>
                    </div>

                    <aside className="flex flex-col gap-4">
                        <section className="rounded-md border border-[#d6e2da] bg-white p-5 dark:border-[#344039] dark:bg-[#1a201c]">
                            <div className="flex items-start justify-between gap-4">
                                <div>
                                    <p className="text-sm font-semibold text-[#0f766e] dark:text-[#5eead4]">
                                        {detail.region.code}
                                    </p>
                                    <h2 className="text-2xl font-semibold">
                                        {detail.region.name}
                                    </h2>
                                </div>
                                <StatusBadge
                                    status={
                                        isLoading ? 'loading' : detail.status
                                    }
                                />
                            </div>
                            <dl className="mt-5 grid gap-3 text-sm">
                                <div className="flex justify-between gap-4 border-b border-[#e4ece7] pb-3 dark:border-[#303a34]">
                                    <dt className="text-[#607067] dark:text-[#b1bdb6]">
                                        Latest observation
                                    </dt>
                                    <dd className="text-right font-medium">
                                        {formatTimestamp(
                                            detail.latest_observed_at,
                                        )}
                                    </dd>
                                </div>
                                <div className="flex justify-between gap-4 border-b border-[#e4ece7] pb-3 dark:border-[#303a34]">
                                    <dt className="text-[#607067] dark:text-[#b1bdb6]">
                                        Timezone
                                    </dt>
                                    <dd className="text-right font-medium">
                                        {detail.region.timezone}
                                    </dd>
                                </div>
                                <div className="flex justify-between gap-4">
                                    <dt className="text-[#607067] dark:text-[#b1bdb6]">
                                        Known clean MW
                                    </dt>
                                    <dd className="text-right font-medium">
                                        {formatNumber(
                                            detail.summary.clean_share.clean_mw,
                                        )}
                                    </dd>
                                </div>
                            </dl>
                            <p className="mt-5 rounded-md bg-[#fff7ed] p-3 text-sm leading-5 text-[#7c2d12] dark:bg-[#3b2415] dark:text-[#fed7aa]">
                                {detail.source_note}
                            </p>
                        </section>

                        <section className="rounded-md border border-[#d6e2da] bg-white p-5 dark:border-[#344039] dark:bg-[#1a201c]">
                            <h2 className="text-base font-semibold">
                                Available Variables
                            </h2>
                            <div className="mt-4 overflow-hidden rounded-md border border-[#e0e8e3] dark:border-[#303a34]">
                                <table className="w-full text-left text-sm">
                                    <thead className="bg-[#eef4f0] text-xs text-[#526158] dark:bg-[#232a25] dark:text-[#b9c5bd]">
                                        <tr>
                                            <th className="px-3 py-2 font-medium">
                                                Variable
                                            </th>
                                            <th className="px-3 py-2 text-right font-medium">
                                                Value
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-[#e0e8e3] dark:divide-[#303a34]">
                                        {detail.variables.length === 0 ? (
                                            <tr>
                                                <td
                                                    className="px-3 py-4 text-[#64746a] dark:text-[#aab6af]"
                                                    colSpan={2}
                                                >
                                                    No normalized observations
                                                    are available for this
                                                    range.
                                                </td>
                                            </tr>
                                        ) : (
                                            detail.variables.map((variable) => (
                                                <tr key={variable.code}>
                                                    <td className="px-3 py-2">
                                                        <span className="block font-medium">
                                                            {variable.label}
                                                        </span>
                                                        <span className="text-xs text-[#64746a] dark:text-[#aab6af]">
                                                            {variable.category}
                                                        </span>
                                                    </td>
                                                    <td className="px-3 py-2 text-right font-medium">
                                                        {formatNumber(
                                                            variable.value,
                                                        )}{' '}
                                                        {variable.unit}
                                                    </td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <section className="rounded-md border border-[#d6e2da] bg-white p-5 dark:border-[#344039] dark:bg-[#1a201c]">
                            <h2 className="text-base font-semibold">Sources</h2>
                            <div className="mt-3 flex flex-col gap-3">
                                {sources.map((source) => (
                                    <a
                                        key={source.label}
                                        href={source.url}
                                        className="rounded-md border border-[#e0e8e3] p-3 hover:border-[#0f766e] dark:border-[#303a34]"
                                    >
                                        <span className="block font-medium">
                                            {source.label}
                                        </span>
                                        <span className="mt-1 block text-sm text-[#607067] dark:text-[#b1bdb6]">
                                            {source.role}
                                        </span>
                                    </a>
                                ))}
                            </div>
                        </section>
                    </aside>
                </section>
            </main>
        </>
    );
}

function Metric({
    label,
    value,
    unit,
}: {
    label: string;
    value: number | null;
    unit: string;
}) {
    return (
        <section className="rounded-md border border-[#d6e2da] bg-white p-4 dark:border-[#344039] dark:bg-[#1a201c]">
            <p className="text-sm text-[#607067] dark:text-[#b1bdb6]">
                {label}
            </p>
            <p className="mt-2 text-2xl font-semibold">
                {value === null ? 'Unavailable' : formatNumber(value)}
                {value !== null && (
                    <span className="ml-1 text-sm font-medium text-[#607067] dark:text-[#b1bdb6]">
                        {unit}
                    </span>
                )}
            </p>
        </section>
    );
}

function StatusBadge({ status }: { status: string }) {
    const classes =
        status === 'current'
            ? 'bg-[#dcfce7] text-[#166534]'
            : status === 'loading'
              ? 'bg-[#e0f2fe] text-[#075985]'
              : 'bg-[#fef3c7] text-[#92400e]';

    return (
        <span
            className={`rounded-md px-2.5 py-1 text-xs font-semibold capitalize ${classes}`}
        >
            {status}
        </span>
    );
}

function SourceMixChart({ variables }: { variables: Variable[] }) {
    const total = variables.reduce((sum, variable) => sum + variable.value, 0);

    if (variables.length === 0 || total <= 0) {
        return <EmptyChart label="No source mix data" />;
    }

    const segments = variables.reduce<
        Array<{
            variable: Variable;
            x: number;
            width: number;
            percentage: number;
        }>
    >((items, variable) => {
        const x = items.reduce((sum, item) => sum + item.width, 0);
        const width = (variable.value / total) * 320;
        const percentage = (variable.value / total) * 100;

        return [...items, { variable, x, width, percentage }];
    }, []);

    return (
        <div className="mt-4 flex flex-col gap-4">
            <svg
                viewBox="0 0 320 60"
                className="h-20 w-full"
                role="img"
                aria-label="Stacked source mix chart"
            >
                {segments.map(({ variable, x, width, percentage }) => {
                    const color =
                        fuelColors[variable.fuel_type ?? 'other'] ??
                        fuelColors.other;

                    return (
                        <g key={variable.code}>
                            <rect
                                x={x}
                                y="8"
                                width={Math.max(width, 1)}
                                height="44"
                                fill={color}
                                rx="3"
                            />
                            {width >= 34 && (
                                <text
                                    x={x + width / 2}
                                    y="34"
                                    textAnchor="middle"
                                    className="fill-white text-[10px] font-semibold"
                                    paintOrder="stroke"
                                    stroke="rgba(0,0,0,0.35)"
                                    strokeWidth="2"
                                >
                                    {formatPercent(percentage)}
                                </text>
                            )}
                        </g>
                    );
                })}
            </svg>
            <div className="grid grid-cols-2 gap-2 text-sm">
                {variables.map((variable) => (
                    <div
                        key={variable.code}
                        className="flex items-center gap-2"
                    >
                        <span
                            className="h-3 w-3 rounded-sm"
                            style={{
                                backgroundColor:
                                    fuelColors[variable.fuel_type ?? 'other'] ??
                                    fuelColors.other,
                            }}
                        />
                        <span className="truncate">
                            {variable.label}{' '}
                            <span className="text-[#64746a] dark:text-[#aab6af]">
                                {formatPercent((variable.value / total) * 100)}
                            </span>
                        </span>
                    </div>
                ))}
            </div>
        </div>
    );
}

function TrendChart({ points }: { points: TrendPoint[] }) {
    const maxValue = Math.max(
        ...points.flatMap((point) => [
            point.demand ?? 0,
            point.generation ?? 0,
        ]),
        1,
    );
    const width = 420;
    const height = 220;
    const margin = { top: 16, right: 12, bottom: 34, left: 58 };
    const plotWidth = width - margin.left - margin.right;
    const plotHeight = height - margin.top - margin.bottom;
    const yMax = niceMax(maxValue);
    const yTicks = [0, yMax * 0.25, yMax * 0.5, yMax * 0.75, yMax];
    const step =
        points.length > 1 ? plotWidth / (points.length - 1) : plotWidth;
    const x = (index: number) => margin.left + index * step;
    const y = (value: number | null) =>
        margin.top + plotHeight - ((value ?? 0) / yMax) * plotHeight;
    const line = (key: 'demand' | 'generation') =>
        points.map((point, index) => `${x(index)},${y(point[key])}`).join(' ');
    const xLabelIndexes = Array.from(
        new Set([
            0,
            Math.floor((points.length - 1) / 2),
            Math.max(points.length - 1, 0),
        ]),
    );

    if (points.length === 0) {
        return <EmptyChart label="No trend data" />;
    }

    return (
        <div className="mt-4">
            <svg
                viewBox={`0 0 ${width} ${height}`}
                className="h-56 w-full"
                role="img"
                aria-label="Demand and generation trend chart"
            >
                {yTicks.map((tick) => {
                    const tickY = y(tick);

                    return (
                        <g key={tick}>
                            <line
                                x1={margin.left}
                                x2={width - margin.right}
                                y1={tickY}
                                y2={tickY}
                                stroke="#dbe5df"
                                strokeWidth="1"
                            />
                            <text
                                x={margin.left - 8}
                                y={tickY + 4}
                                textAnchor="end"
                                className="fill-[#64746a] text-[10px] dark:fill-[#aab6af]"
                            >
                                {formatCompactNumber(tick)}
                            </text>
                        </g>
                    );
                })}
                <line
                    x1={margin.left}
                    x2={margin.left}
                    y1={margin.top}
                    y2={margin.top + plotHeight}
                    stroke="#9aa8a0"
                    strokeWidth="1"
                />
                <line
                    x1={margin.left}
                    x2={width - margin.right}
                    y1={margin.top + plotHeight}
                    y2={margin.top + plotHeight}
                    stroke="#9aa8a0"
                    strokeWidth="1"
                />
                <polyline
                    points={line('generation')}
                    fill="none"
                    stroke="#0f766e"
                    strokeWidth="4"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                />
                <polyline
                    points={line('demand')}
                    fill="none"
                    stroke="#c2410c"
                    strokeWidth="4"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                />
                {xLabelIndexes.map((index) => (
                    <text
                        key={`${points[index]?.label}-${index}`}
                        x={x(index)}
                        y={height - 10}
                        textAnchor={
                            index === 0
                                ? 'start'
                                : index === points.length - 1
                                  ? 'end'
                                  : 'middle'
                        }
                        className="fill-[#64746a] text-[10px] dark:fill-[#aab6af]"
                    >
                        {points[index]?.label}
                    </text>
                ))}
            </svg>
            <div className="flex gap-4 text-sm">
                <span className="flex items-center gap-2">
                    <span className="h-2 w-6 rounded-sm bg-[#0f766e]" />
                    Generation
                </span>
                <span className="flex items-center gap-2">
                    <span className="h-2 w-6 rounded-sm bg-[#c2410c]" />
                    Demand
                </span>
            </div>
        </div>
    );
}

function EmptyChart({ label }: { label: string }) {
    return (
        <div className="mt-4 flex h-56 items-center justify-center rounded-md bg-[#f1f5f2] text-sm text-[#64746a] dark:bg-[#232a25] dark:text-[#aab6af]">
            {label}
        </div>
    );
}

function formatNumber(value: number | null) {
    return value === null
        ? 'Unavailable'
        : new Intl.NumberFormat('en-CA', { maximumFractionDigits: 1 }).format(
              value,
          );
}

function formatCompactNumber(value: number) {
    return new Intl.NumberFormat('en-CA', {
        notation: 'compact',
        maximumFractionDigits: 1,
    }).format(value);
}

function formatPercent(value: number) {
    return `${new Intl.NumberFormat('en-CA', { maximumFractionDigits: 0 }).format(value)}%`;
}

function niceMax(value: number) {
    const exponent = Math.floor(Math.log10(value));
    const magnitude = 10 ** exponent;
    const normalized = value / magnitude;
    const rounded =
        normalized <= 1 ? 1 : normalized <= 2 ? 2 : normalized <= 5 ? 5 : 10;

    return rounded * magnitude;
}

function formatTimestamp(value: string | null) {
    return value === null
        ? 'Unavailable'
        : new Intl.DateTimeFormat('en-CA', {
              dateStyle: 'medium',
              timeStyle: 'short',
          }).format(new Date(value));
}
