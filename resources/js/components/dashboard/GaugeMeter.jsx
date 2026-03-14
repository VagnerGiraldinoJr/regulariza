function polarToCartesian(cx, cy, radius, angleInDegrees) {
    const angleInRadians = (angleInDegrees - 90) * (Math.PI / 180);

    return {
        x: cx + (radius * Math.cos(angleInRadians)),
        y: cy + (radius * Math.sin(angleInRadians)),
    };
}

function describeArc(cx, cy, radius, startAngle, endAngle) {
    const start = polarToCartesian(cx, cy, radius, endAngle);
    const end = polarToCartesian(cx, cy, radius, startAngle);
    const largeArcFlag = endAngle - startAngle <= 180 ? 0 : 1;

    return `M ${start.x} ${start.y} A ${radius} ${radius} 0 ${largeArcFlag} 0 ${end.x} ${end.y}`;
}

export default function GaugeMeter({
    title = 'Gauge',
    label,
    value,
    min = 0,
    max = 100,
    display,
}) {
    const safeValue = Math.max(min, Math.min(max, Number(value) || 0));
    const ratio = max === min ? 0 : (safeValue - min) / (max - min);
    const progressEnd = -90 + (180 * ratio);
    const pointer = polarToCartesian(100, 100, 72, progressEnd);

    return (
        <section className="panel-card overflow-hidden">
            <div className="border-b border-slate-300/55 bg-white/20 px-5 py-4">
                <p className="text-xs font-black uppercase tracking-[0.22em] text-cyan-800/75">{title}</p>
                <h3 className="mt-2 text-base font-black text-slate-900">{label}</h3>
            </div>

            <div className="bg-[radial-gradient(circle_at_top,_rgba(34,211,238,0.22),_transparent_60%)] px-5 py-6">
                <div className="mx-auto w-full max-w-[260px]">
                    <svg viewBox="0 0 200 130" className="w-full overflow-visible">
                        <path
                            d={describeArc(100, 100, 72, -90, 90)}
                            fill="none"
                            stroke="rgba(148, 163, 184, 0.2)"
                            strokeWidth="18"
                            strokeLinecap="round"
                        />
                        <path
                            d={describeArc(100, 100, 72, -90, progressEnd)}
                            fill="none"
                            stroke="url(#gaugeGradient)"
                            strokeWidth="18"
                            strokeLinecap="round"
                        />
                        <defs>
                            <linearGradient id="gaugeGradient" x1="10%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" stopColor="#f59e0b" />
                                <stop offset="55%" stopColor="#22c55e" />
                                <stop offset="100%" stopColor="#06b6d4" />
                            </linearGradient>
                        </defs>

                        <line
                            x1="100"
                            y1="100"
                            x2={pointer.x}
                            y2={pointer.y}
                            stroke="#102235"
                            strokeWidth="4"
                            strokeLinecap="round"
                        />
                        <circle cx="100" cy="100" r="8" fill="#102235" />
                        <circle cx={pointer.x} cy={pointer.y} r="6" fill="#0ea5e9" />

                        <text x="100" y="86" textAnchor="middle" className="fill-slate-900 text-[20px] font-black">
                            {display ?? safeValue}
                        </text>
                        <text x="28" y="115" textAnchor="middle" className="fill-slate-500 text-[9px] font-semibold uppercase tracking-[0.2em]">
                            {min}
                        </text>
                        <text x="172" y="115" textAnchor="middle" className="fill-slate-500 text-[9px] font-semibold uppercase tracking-[0.2em]">
                            {max}
                        </text>
                    </svg>
                </div>
            </div>
        </section>
    );
}
