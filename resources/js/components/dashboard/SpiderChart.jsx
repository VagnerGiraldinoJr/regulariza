function pointForMetric(index, total, valueRatio, radius, center) {
    const angle = ((Math.PI * 2) / total) * index - (Math.PI / 2);
    const distance = radius * valueRatio;

    return {
        x: center + (Math.cos(angle) * distance),
        y: center + (Math.sin(angle) * distance),
    };
}

export default function SpiderChart({
    title = 'Spider chart',
    subtitle,
    metrics = [],
}) {
    const center = 120;
    const radius = 82;
    const total = Math.max(metrics.length, 3);
    const rings = [0.25, 0.5, 0.75, 1];
    const polygonPoints = metrics
        .map((metric, index) => {
            const ratio = Math.max(0, Math.min(1, (Number(metric.value) || 0) / Math.max(Number(metric.max) || 1, 1)));
            const point = pointForMetric(index, total, ratio, radius, center);

            return `${point.x},${point.y}`;
        })
        .join(' ');

    return (
        <section className="panel-card overflow-hidden">
            <div className="border-b border-slate-300/55 bg-white/20 px-5 py-4">
                <p className="text-xs font-black uppercase tracking-[0.22em] text-fuchsia-800/70">{title}</p>
                {subtitle ? <p className="mt-2 text-sm text-slate-600">{subtitle}</p> : null}
            </div>

            <div className="px-4 py-5">
                <svg viewBox="0 0 240 240" className="mx-auto w-full max-w-[340px]">
                    {rings.map((ring) => {
                        const points = Array.from({ length: total }, (_, index) => {
                            const point = pointForMetric(index, total, ring, radius, center);

                            return `${point.x},${point.y}`;
                        }).join(' ');

                        return (
                            <polygon
                                key={ring}
                                points={points}
                                fill="none"
                                stroke="rgba(100, 116, 139, 0.22)"
                                strokeWidth="1"
                            />
                        );
                    })}

                    {metrics.map((metric, index) => {
                        const anchor = pointForMetric(index, total, 1.12, radius, center);

                        return (
                            <g key={metric.label}>
                                <line
                                    x1={center}
                                    y1={center}
                                    x2={anchor.x}
                                    y2={anchor.y}
                                    stroke="rgba(100, 116, 139, 0.18)"
                                    strokeWidth="1"
                                />
                                <text
                                    x={anchor.x}
                                    y={anchor.y}
                                    textAnchor={anchor.x >= center + 10 ? 'start' : anchor.x <= center - 10 ? 'end' : 'middle'}
                                    dominantBaseline={anchor.y > center ? 'hanging' : 'auto'}
                                    className="fill-slate-600 text-[8px] font-bold uppercase tracking-[0.18em]"
                                >
                                    {metric.label}
                                </text>
                            </g>
                        );
                    })}

                    <polygon
                        points={polygonPoints}
                        fill="rgba(14, 165, 233, 0.18)"
                        stroke="rgba(2, 132, 199, 0.9)"
                        strokeWidth="3"
                    />

                    {metrics.map((metric, index) => {
                        const ratio = Math.max(0, Math.min(1, (Number(metric.value) || 0) / Math.max(Number(metric.max) || 1, 1)));
                        const point = pointForMetric(index, total, ratio, radius, center);

                        return <circle key={metric.label} cx={point.x} cy={point.y} r="5" fill="#0284c7" />;
                    })}
                </svg>

                <div className="mt-4 grid gap-2 sm:grid-cols-2">
                    {metrics.map((metric) => (
                        <div key={metric.label} className="rounded-2xl border border-slate-200/70 bg-white/60 px-3 py-2">
                            <p className="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">{metric.label}</p>
                            <p className="mt-1 text-lg font-black text-slate-900">{metric.value}/{metric.max}</p>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}
