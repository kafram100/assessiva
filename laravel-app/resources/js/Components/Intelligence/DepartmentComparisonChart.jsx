import { useChartTheme } from '@/lib/chartTheme';

import {

    Bar,

    BarChart,

    CartesianGrid,

    ResponsiveContainer,

    Tooltip,

    XAxis,

    YAxis,

} from 'recharts';



export default function DepartmentComparisonChart({

    departments = [],

    title,

    subtitle,

}) {

    const ct = useChartTheme();

    const data = (departments ?? [])

        .filter((d) => d.score != null)

        .map((d) => ({

            name:

                d.department && d.department.length > 22

                    ? `${d.department.slice(0, 20)}…`

                    : (d.department ?? ''),

            score: Number(d.score),

        }));



    return (

        <div className="rounded-2xl border border-theme-line bg-theme-glass p-5 backdrop-blur-xl">

            <div className="mb-3">

                <h3 className="text-sm font-semibold uppercase tracking-widest text-sky-600 dark:text-sky-200/85">

                    {title ?? 'Weakest departments (health index)'}

                </h3>

                {subtitle ? (

                    <p className="mt-1 text-xs text-theme-dim">{subtitle}</p>

                ) : null}

            </div>

            {data.length === 0 ? (

                <p className="text-sm text-theme-dim">

                    Not enough department-level health scores to compare yet.

                </p>

            ) : (

                <div className="h-64 w-full">

                    <ResponsiveContainer width="100%" height="100%">

                        <BarChart

                            data={data}

                            margin={{ top: 8, right: 8, left: 0, bottom: 8 }}

                        >

                            <CartesianGrid

                                strokeDasharray="4 8"

                                stroke={ct.grid}

                                vertical={false}

                            />

                            <XAxis

                                dataKey="name"

                                tick={{ fill: ct.tick, fontSize: 10 }}

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

                            <Bar

                                dataKey="score"

                                fill="#38bdf8"

                                radius={[6, 6, 0, 0]}

                                opacity={0.9}

                            />

                        </BarChart>

                    </ResponsiveContainer>

                </div>

            )}

        </div>

    );

}

