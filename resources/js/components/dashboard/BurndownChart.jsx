function scalePoint(index, count, value, maxValue) {
    const x = count <= 1 ? 32 : 32 + ((index / (count - 1)) * 296);
    const y = 196 - ((value / maxValue) * 156);

    return { x, y };
}

export default function BurndownChart({
    title = 'Burndown',
    subtitle,
    series = [],
}) {
    const maxValue = Math.max(
        1,
        ...series.flatMap((item) => [Number(item.expected) || 0, Number(item.actual) || 0]),
    );
    const expectedLine = series
        .map((item, index) => {
            const point = scalePoint(index, series.length, Number(item.expected) || 0, maxValue);

            return `${index === 0 ? 'M' : 'L'} ${point.x} ${point.y}`;
        })
        .join(' ');
    const actualLine = series
        .map((item, index) => {
            const point = scalePoint(index, series.length, Number(item.actual) || 0, maxValue);

            return `${index === 0 ? 'M' : 'L'} ${point.x} ${point.y}`;
        })
        .join(' ');

    return (
        <section className="panel-card overflow-hidden">
            <div className="border-b border-slate-300/55 bg-white/20 px-5 py-4">
                <p className="text-xs font-black uppercase tracking-[0.22em] text-indigo-800/70">{title}</p>
                {subtitle ? <p className="mt-2 text-sm text-slate-600">{subtitle}</p> : null}
            </div>

            <div className="px-5 py-5">
                <svg viewBox="0 0 360 220" className="w-full">
                    {[0, 0.25, 0.5, 0.75, 1].map((marker) => {
                        const y = 196 - (156 * marker);

                        return (
                            <line
                                key={marker}
                                x1="32"
                                y1={y}
                                x2="328"
                                y2={y}
                                stroke="rgba(100, 116, 139, 0.18)"
                                strokeDasharray="4 6"
                            />
                        );
                    })}

                    <path d={expectedLine} fill="none" stroke="#94a3b8" strokeWidth="4" strokeLinecap="round" />
                    <path d={actualLine} fill="none" stroke="#2563eb" strokeWidth="4" strokeLinecap="round" />

                    {series.map((item, index) => {
                        const point = scalePoint(index, series.length, Number(item.actual) || 0, maxValue);

                        return <circle key={item.label} cx={point.x} cy={point.y} r="5" fill="#2563eb" />;
                    })}

                    {series.map((item, index) => {
                        const point = scalePoint(index, series.length, 0, maxValue);

                        return (
                            <text
                                key={`label-${item.label}`}
                                x={point.x}
                                y="214"
                                textAnchor="middle"
                                className="fill-slate-500 text-[9px] font-bold uppercase tracking-[0.15em]"
                            >
                                {item.label}
                            </text>
                        );
                    })}
                </svg>

                <div className="mt-4 flex flex-wrap gap-4 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                    <div className="flex items-center gap-2">
                        <span className="block h-3 w-3 rounded-full bg-slate-400" />
                        Esperado
                    </div>
                    <div className="flex items-center gap-2">
                        <span className="block h-3 w-3 rounded-full bg-blue-600" />
                        Atual
                    </div>
                </div>
            </div>
        </section>
    );
}
