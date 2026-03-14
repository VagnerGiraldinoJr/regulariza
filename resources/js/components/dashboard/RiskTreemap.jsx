function blockTone(index) {
    const tones = [
        'from-rose-400/80 to-rose-300/55',
        'from-amber-400/80 to-amber-300/55',
        'from-cyan-500/80 to-sky-300/55',
        'from-emerald-500/80 to-emerald-300/55',
    ];

    return tones[index % tones.length];
}

export default function RiskTreemap({
    title = 'Treemap',
    subtitle,
    items = [],
}) {
    const total = items.reduce((carry, item) => carry + (Number(item.value) || 0), 0) || 1;

    return (
        <section className="panel-card overflow-hidden">
            <div className="border-b border-slate-300/55 bg-white/20 px-5 py-4">
                <p className="text-xs font-black uppercase tracking-[0.22em] text-violet-800/70">{title}</p>
                {subtitle ? <p className="mt-2 text-sm text-slate-600">{subtitle}</p> : null}
            </div>

            <div className="flex min-h-[320px] flex-wrap gap-3 px-5 py-5">
                {items.map((item, index) => {
                    const width = Math.max(22, ((Number(item.value) || 0) / total) * 100);

                    return (
                        <article
                            key={item.label}
                            className={`flex min-h-[132px] min-w-[180px] flex-1 flex-col justify-between rounded-[1.75rem] border border-white/50 bg-gradient-to-br p-4 text-slate-950 shadow-[0_18px_48px_rgba(15,23,42,0.08)] ${blockTone(index)}`}
                            style={{ flexBasis: `${width}%` }}
                        >
                            <div className="flex items-start justify-between gap-3">
                                <p className="max-w-[16rem] text-sm font-black uppercase tracking-[0.14em]">{item.label}</p>
                                <div className="rounded-full bg-white/55 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.18em]">
                                    {item.value}
                                </div>
                            </div>
                            <p className="text-2xl font-black">{item.display}</p>
                        </article>
                    );
                })}
            </div>
        </section>
    );
}
