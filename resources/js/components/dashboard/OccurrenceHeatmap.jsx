import { Fragment } from 'react';

function intensityClass(value, maxValue) {
    const ratio = maxValue <= 0 ? 0 : value / maxValue;

    if (ratio >= 0.8) {
        return 'bg-cyan-600 text-white';
    }

    if (ratio >= 0.55) {
        return 'bg-cyan-400 text-slate-950';
    }

    if (ratio >= 0.25) {
        return 'bg-cyan-200 text-slate-900';
    }

    return 'bg-slate-100 text-slate-500';
}

export default function OccurrenceHeatmap({
    title = 'Heatmap',
    subtitle,
    xLabels = [],
    rows = [],
    maxValue = 1,
}) {
    return (
        <section className="panel-card overflow-hidden">
            <div className="border-b border-slate-300/55 bg-white/20 px-5 py-4">
                <p className="text-xs font-black uppercase tracking-[0.22em] text-sky-800/70">{title}</p>
                {subtitle ? <p className="mt-2 text-sm text-slate-600">{subtitle}</p> : null}
            </div>

            <div className="overflow-x-auto px-5 py-5">
                <div className="grid min-w-[540px] grid-cols-[180px_repeat(6,minmax(0,1fr))] gap-2">
                    <div />
                    {xLabels.map((label) => (
                        <div key={label} className="text-center text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">
                            {label}
                        </div>
                    ))}

                    {rows.map((row) => (
                        <Fragment key={row.label}>
                            <div className="flex items-center pr-3 text-sm font-semibold text-slate-700">
                                {row.label}
                            </div>
                            {row.values.map((value, index) => (
                                <div
                                    key={`${row.label}-${xLabels[index] ?? index}`}
                                    className={`flex h-14 items-center justify-center rounded-2xl border border-white/50 text-sm font-black ${intensityClass(value, maxValue)}`}
                                >
                                    {value}
                                </div>
                            ))}
                        </Fragment>
                    ))}
                </div>
            </div>
        </section>
    );
}
