import { useChartTheme } from '@/lib/chartTheme';

import {

    CartesianGrid,

    Line,

    LineChart,

    ResponsiveContainer,

    Tooltip,

    XAxis,

    YAxis,

} from 'recharts';



export default function EngagementTrendChart({

    data = [],

    title,

    subtitle,

    xKey = 'week',

    yKey = 'count',

}) {

    const ct = useChartTheme();

    const rows = (data ?? []).map((r) => ({

        ...r,

        [yKey]: Number(r[yKey] ?? 0),

    }));



    return (

        <div className="rounded-2xl border border-theme-line bg-theme-glass p-5 backdrop-blur-xl">

            <div className="mb-3">

                <h3 className="text-sm font-semibold uppercase tracking-widest text-emerald-600 dark:text-emerald-200/85">

                    {title ?? 'Practice hub engagement'}

                </h3>

                {subtitle ? (

                    <p className="mt-1 text-xs text-theme-dim">{subtitle}</p>

                ) : null}

            </div>

            {rows.length === 0 ? (

                <p className="text-sm text-theme-dim">

                    No practice completions in the selected window yet.

                </p>

            ) : (

                <div className="h-56 w-full">

                    <ResponsiveContainer width="100%" height="100%">

                        <LineChart data={rows}>

                            <CartesianGrid

                                strokeDasharray="4 8"

                                stroke={ct.grid}

                            />

                            <XAxis

                                dataKey={xKey}

                                tick={{ fill: ct.tick, fontSize: 10 }}

                                axisLine={{ stroke: ct.axisLine }}

                            />

                            <YAxis

                                allowDecimals={false}

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

                            <Line

                                type="monotone"

                                dataKey={yKey}

                                stroke="#34d399"

                                strokeWidth={2}

                                dot={{ r: 3, fill: '#6ee7b7' }}

                                activeDot={{ r: 5 }}

                            />

                        </LineChart>

                    </ResponsiveContainer>

                </div>

            )}

        </div>

    );

}

