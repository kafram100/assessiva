import { useId } from 'react';
import { useChartTheme } from '@/lib/chartTheme';
import {
    Bar,
    BarChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

export default function WeakTopicPanel({ topics = [], title, subtitle }) {
    const gid = useId().replace(/:/g, '');
    const ct = useChartTheme();
    const data = (topics ?? []).map((t) => ({
        name:
            t.topic && t.topic.length > 28
                ? `${t.topic.slice(0, 26)}…`
                : (t.topic ?? ''),
        weight: t.weight ?? 0,
    }));

    return (
        <div className="rounded-2xl border border-theme-line bg-theme-glass p-5 shadow-inner backdrop-blur-xl">
            <div className="mb-4">
                <h3 className="text-sm font-semibold uppercase tracking-widest text-cyan-600 dark:text-cyan-200/80">
                    {title ?? 'Weak topic intelligence'}
                </h3>
                {subtitle ? (
                    <p className="mt-1 text-xs text-theme-dim">{subtitle}</p>
                ) : null}
            </div>
            {data.length === 0 ? (
                <p className="text-sm text-theme-dim">
                    No weak-topic clusters detected in stored intelligence yet.
                </p>
            ) : (
                <div className="h-64 w-full">
                    <ResponsiveContainer width="100%" height="100%">
                        <BarChart
                            data={data}
                            layout="vertical"
                            margin={{ left: 8, right: 16, top: 4, bottom: 4 }}
                        >
                            <XAxis type="number" hide />
                            <YAxis
                                type="category"
                                dataKey="name"
                                width={110}
                                tick={{
                                    fill: ct.tick,
                                    fontSize: 11,
                                }}
                                axisLine={false}
                                tickLine={false}
                            />
                            <Tooltip
                                cursor={{ fill: ct.grid }}
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
                            <Bar
                                dataKey="weight"
                                radius={[0, 6, 6, 0]}
                                fill={`url(#topicBar-${gid})`}
                            />
                            <defs>
                                <linearGradient
                                    id={`topicBar-${gid}`}
                                    x1="0"
                                    y1="0"
                                    x2="1"
                                    y2="0"
                                >
                                    <stop
                                        offset="0%"
                                        stopColor="#22d3ee"
                                        stopOpacity={0.9}
                                    />
                                    <stop
                                        offset="100%"
                                        stopColor="#6366f1"
                                        stopOpacity={0.95}
                                    />
                                </linearGradient>
                            </defs>
                        </BarChart>
                    </ResponsiveContainer>
                </div>
            )}
        </div>
    );
}
