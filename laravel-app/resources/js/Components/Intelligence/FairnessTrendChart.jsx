import { useId } from 'react';
import { useChartTheme } from '@/lib/chartTheme';
import {
    Area,
    AreaChart,
    CartesianGrid,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

export default function FairnessTrendChart({ trend = [], title, note }) {
    const gid = useId().replace(/:/g, '');
    const ct = useChartTheme();
    const data = (trend ?? []).map((row) => ({
        date: row.date ?? '',
        health: row.course_health == null ? null : Number(row.course_health),
    }));

    return (
        <div className="rounded-2xl border border-theme-line bg-theme-glass p-5 backdrop-blur-xl">
            <div className="mb-3 flex flex-wrap items-start justify-between gap-2">
                <div>
                    <h3 className="text-sm font-semibold uppercase tracking-widest text-violet-600 dark:text-violet-200/85">
                        {title ?? 'Fairness-adjacent trend'}
                    </h3>
                    {note ? (
                        <p className="mt-1 max-w-prose text-xs leading-relaxed text-theme-dim">
                            {note}
                        </p>
                    ) : null}
                </div>
            </div>
            {data.length === 0 ? (
                <p className="text-sm text-theme-dim">
                    Generate cohort analytics to populate course health over time.
                </p>
            ) : (
                <div className="h-56 w-full">
                    <ResponsiveContainer width="100%" height="100%">
                        <AreaChart data={data}>
                            <defs>
                                <linearGradient
                                    id={`fairnessFill-${gid}`}
                                    x1="0"
                                    y1="0"
                                    x2="0"
                                    y2="1"
                                >
                                    <stop
                                        offset="0%"
                                        stopColor="#a78bfa"
                                        stopOpacity={0.5}
                                    />
                                    <stop
                                        offset="100%"
                                        stopColor="#a78bfa"
                                        stopOpacity={0}
                                    />
                                </linearGradient>
                            </defs>
                            <CartesianGrid
                                strokeDasharray="4 8"
                                stroke={ct.grid}
                            />
                            <XAxis
                                dataKey="date"
                                tick={{ fill: ct.tick, fontSize: 11 }}
                                axisLine={{ stroke: ct.axisLine }}
                            />
                            <YAxis
                                domain={[0, 100]}
                                tick={{ fill: ct.tick, fontSize: 11 }}
                                axisLine={{ stroke: ct.axisLine }}
                            />
                            <Tooltip
                                contentStyle={{
                                    background: ct.tooltipBg,
                                    border: `1px solid ${ct.tooltipBorder}`,
                                    borderRadius: 12,
                                    fontSize: 12,
                                    color: ct.tooltipItem,
                                }}
                                labelStyle={{ color: ct.tooltipLabel }}
                                itemStyle={{ color: ct.tooltipItem }}
                            />
                            <Area
                                type="monotone"
                                dataKey="health"
                                stroke="#c4b5fd"
                                strokeWidth={2}
                                fill={`url(#fairnessFill-${gid})`}
                                connectNulls
                            />
                        </AreaChart>
                    </ResponsiveContainer>
                </div>
            )}
        </div>
    );
}
