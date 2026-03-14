function bulletPercent(value, max) {
    if (!max || max <= 0) {
        return 0;
    }

    return Math.max(0, Math.min(100, (value / max) * 100));
}

export default function BulletChart({
    title = 'Bullet chart',
    subtitle,
    items = [],
}) {
    return (
        <section className="panel-card overflow-hidden">
            <div className="border-b border-slate-300/55 bg-white/20 px-5 py-4">
                <p className="text-xs font-black uppercase tracking-[0.22em] text-emerald-800/70">{title}</p>
                {subtitle ? <p className="mt-2 text-sm text-slate-600">{subtitle}</p> : null}
            </div>

            <div className="space-y-4 px-5 py-5">
                {items.map((item) => {
                    const currentWidth = bulletPercent(Number(item.value) || 0, Number(item.max) || 1);
                    const targetPosition = bulletPercent(Number(item.target) || 0, Number(item.max) || 1);

                    return (
                        <div key={item.label} className="rounded-2xl border border-slate-200/70 bg-white/55 p-4">
                            <div className="mb-3 flex items-start justify-between gap-3">
                                <div>
                                    <p className="text-sm font-bold text-slate-900">{item.label}</p>
                                    <p className="text-xs text-slate-500">Atual {item.displayValue} | Meta {item.displayTarget}</p>
                                </div>
                                <div className="rounded-full bg-slate-900 px-3 py-1 text-xs font-black uppercase tracking-[0.18em] text-white">
                                    {Math.round(currentWidth)}%
                                </div>
                            </div>

                            <div className="relative h-7 rounded-full bg-slate-200/65">
                                <div className="absolute inset-y-0 left-0 w-[55%] rounded-full bg-slate-300/80" />
                                <div className="absolute inset-y-0 left-0 w-[78%] rounded-full bg-slate-400/70" />
                                <div
                                    className="absolute inset-y-1 left-1 rounded-full bg-gradient-to-r from-emerald-400 to-cyan-500"
                                    style={{ width: currentWidth > 0 ? `calc(${currentWidth}% - 0.5rem)` : '0px' }}
                                />
                                <div
                                    className="absolute bottom-0 top-0 w-1 -translate-x-1/2 rounded-full bg-slate-900"
                                    style={{ left: `${targetPosition}%` }}
                                />
                            </div>
                        </div>
                    );
                })}
            </div>
        </section>
    );
}
