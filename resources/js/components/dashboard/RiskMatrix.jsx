import { Fragment } from 'react';

function severityClass(severity) {
    if (severity >= 20) {
        return 'bg-rose-500 text-white';
    }

    if (severity >= 12) {
        return 'bg-amber-400 text-slate-900';
    }

    return 'bg-emerald-400 text-slate-900';
}

export default function RiskMatrix({
    title = 'Risk matrix',
    subtitle,
    items = [],
}) {
    const labels = ['1', '2', '3', '4', '5'];

    return (
        <section className="panel-card overflow-hidden">
            <div className="border-b border-slate-300/55 bg-white/20 px-5 py-4">
                <p className="text-xs font-black uppercase tracking-[0.22em] text-rose-800/70">{title}</p>
                {subtitle ? <p className="mt-2 text-sm text-slate-600">{subtitle}</p> : null}
            </div>

            <div className="space-y-4 px-5 py-5">
                <div className="grid grid-cols-[auto_repeat(5,minmax(0,1fr))] gap-2">
                    <div />
                    {labels.map((label) => (
                        <div key={`prob-${label}`} className="text-center text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">
                            P{label}
                        </div>
                    ))}

                    {labels.slice().reverse().map((impact) => (
                        <Fragment key={`impact-${impact}`}>
                            <div className="flex items-center justify-center text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">
                                I{impact}
                            </div>
                            {labels.map((probability) => {
                                const cellScore = Number(impact) * Number(probability);
                                const background = cellScore >= 20
                                    ? 'bg-rose-100'
                                    : cellScore >= 12
                                        ? 'bg-amber-100'
                                        : 'bg-emerald-100';

                                const matchedItems = items.filter((item) => (
                                    Number(item.impact) === Number(impact)
                                    && Number(item.probability) === Number(probability)
                                ));

                                return (
                                    <div
                                        key={`${impact}-${probability}`}
                                        className={`min-h-16 rounded-2xl border border-white/60 p-2 ${background}`}
                                    >
                                        <div className="flex h-full flex-wrap content-start gap-1">
                                            {matchedItems.map((item) => (
                                                <div
                                                    key={item.label}
                                                    className={`rounded-full px-2 py-1 text-[10px] font-bold uppercase tracking-[0.12em] ${severityClass(item.severity)}`}
                                                    title={`${item.label} - ${item.display}`}
                                                >
                                                    {item.label}
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                );
                            })}
                        </Fragment>
                    ))}
                </div>

                <div className="grid gap-2">
                    {items.map((item) => (
                        <div key={item.label} className="flex items-center justify-between gap-3 rounded-2xl border border-slate-200/70 bg-white/55 px-3 py-2">
                            <div>
                                <p className="text-sm font-bold text-slate-900">{item.label}</p>
                                <p className="text-xs text-slate-500">Impacto {item.impact} x Probabilidade {item.probability}</p>
                            </div>
                            <div className={`rounded-full px-3 py-1 text-xs font-black uppercase tracking-[0.18em] ${severityClass(item.severity)}`}>
                                {item.display}
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}
