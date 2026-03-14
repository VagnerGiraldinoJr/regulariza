const levels = [
    { icon: '😖', label: 'Critico' },
    { icon: '🙁', label: 'Baixo' },
    { icon: '😐', label: 'Neutro' },
    { icon: '🙂', label: 'Bom' },
    { icon: '😄', label: 'Otimo' },
];

export default function EmojiSatisfaction({
    title = 'Satisfacao',
    label,
    value,
}) {
    const safeValue = Math.max(0, Math.min(100, Number(value) || 0));
    const activeIndex = Math.max(0, Math.min(levels.length - 1, Math.round((safeValue / 100) * (levels.length - 1))));

    return (
        <section className="panel-card overflow-hidden">
            <div className="border-b border-slate-300/55 bg-white/20 px-5 py-4">
                <p className="text-xs font-black uppercase tracking-[0.22em] text-amber-800/75">{title}</p>
                <h3 className="mt-2 text-base font-black text-slate-900">{label}</h3>
            </div>

            <div className="space-y-5 bg-[radial-gradient(circle_at_top,_rgba(251,191,36,0.18),_transparent_60%)] px-5 py-6">
                <div className="grid grid-cols-5 gap-2">
                    {levels.map((item, index) => {
                        const isActive = index === activeIndex;

                        return (
                            <div
                                key={item.label}
                                className={[
                                    'rounded-2xl border px-2 py-4 text-center transition',
                                    isActive
                                        ? 'border-amber-300 bg-amber-50 shadow-[0_14px_32px_rgba(245,158,11,0.18)]'
                                        : 'border-white/60 bg-white/45',
                                ].join(' ')}
                            >
                                <div className="text-3xl">{item.icon}</div>
                                <p className="mt-2 text-[11px] font-bold uppercase tracking-[0.18em] text-slate-600">{item.label}</p>
                            </div>
                        );
                    })}
                </div>

                <div>
                    <div className="mb-2 flex items-center justify-between text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                        <span>Indice estimado</span>
                        <span>{safeValue.toFixed(1)}%</span>
                    </div>
                    <div className="h-3 rounded-full bg-slate-200/70">
                        <div
                            className="h-3 rounded-full bg-gradient-to-r from-rose-400 via-amber-400 to-emerald-400"
                            style={{ width: `${safeValue}%` }}
                        />
                    </div>
                </div>
            </div>
        </section>
    );
}
