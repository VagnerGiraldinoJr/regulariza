import GaugeMeter from '../components/dashboard/GaugeMeter';
import EmojiSatisfaction from '../components/dashboard/EmojiSatisfaction';
import SpiderChart from '../components/dashboard/SpiderChart';
import RiskMatrix from '../components/dashboard/RiskMatrix';
import OccurrenceHeatmap from '../components/dashboard/OccurrenceHeatmap';
import BulletChart from '../components/dashboard/BulletChart';
import RiskTreemap from '../components/dashboard/RiskTreemap';
import BurndownChart from '../components/dashboard/BurndownChart';

const toneClasses = {
    cyan: 'from-cyan-300/70 via-sky-200/65 to-white/70',
    emerald: 'from-emerald-300/70 via-emerald-200/65 to-white/70',
    amber: 'from-amber-300/75 via-amber-100/70 to-white/70',
    rose: 'from-rose-300/75 via-rose-100/70 to-white/70',
    slate: 'from-slate-200/80 via-slate-100/70 to-white/70',
};

function MetricCard({ item }) {
    return (
        <article className={`metric-card bg-gradient-to-br ${toneClasses[item.tone] ?? toneClasses.slate}`}>
            <p className="text-[11px] font-black uppercase tracking-[0.2em] text-slate-600">{item.label}</p>
            <h3 className="mt-3">{item.display}</h3>
            <p>{item.hint}</p>
        </article>
    );
}

function ComparisonCard({ card }) {
    return (
        <section className="panel-card overflow-hidden">
            <div className="border-b border-slate-300/55 bg-white/15 px-4 py-4">
                <h2 className="text-sm font-black uppercase tracking-[0.18em] text-slate-700">{card.title}</h2>
                <p className="mt-2 text-sm text-slate-500">{card.subtitle}</p>
            </div>

            <div className="grid gap-3 p-4 sm:grid-cols-3">
                {card.items.map((item) => (
                    <article
                        key={item.label}
                        className={`rounded-[1.4rem] border border-white/60 bg-gradient-to-br p-4 shadow-[0_14px_32px_rgba(15,23,42,0.06)] ${toneClasses[item.tone] ?? toneClasses.slate}`}
                    >
                        <p className="text-[11px] font-black uppercase tracking-[0.18em] text-slate-600">{item.label}</p>
                        <p className="mt-3 text-2xl font-black text-slate-900">{item.display}</p>
                        <p className="mt-1 text-xs text-slate-600">{item.meta}</p>
                    </article>
                ))}
            </div>
        </section>
    );
}

function ServicesCard({ services }) {
    return (
        <section className="panel-card overflow-hidden">
            <div className="border-b border-slate-300/55 bg-white/15 px-4 py-4">
                <h2 className="text-sm font-black uppercase tracking-[0.18em] text-slate-700">{services.title}</h2>
            </div>

            <div className="divide-y divide-slate-200/60">
                {services.items.length > 0 ? services.items.map((item, index) => (
                    <div key={item.label} className="flex items-center justify-between gap-3 px-4 py-4">
                        <div className="flex items-center gap-3">
                            <div className="flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-900 text-sm font-black text-white">
                                {index + 1}
                            </div>
                            <div>
                                <p className="text-sm font-bold text-slate-900">{item.label}</p>
                                <p className="text-xs text-slate-500">{item.count} pagamento(s)</p>
                            </div>
                        </div>
                        <div className="rounded-full bg-emerald-100 px-3 py-1 text-sm font-black text-emerald-900">
                            {item.display}
                        </div>
                    </div>
                )) : (
                    <div className="px-4 py-8 text-center text-sm text-slate-500">Ainda nao ha pagamentos confirmados.</div>
                )}
            </div>
        </section>
    );
}

export default function Dashboard(props) {
    const {
        headline = {
            title: 'Dashboard Financeiro',
            subtitle: '',
        },
        metricCards = [],
        comparisons = [],
        gauge = {},
        satisfaction = {},
        spider = {},
        riskMatrix = {},
        heatmap = {},
        bullet = {},
        treemap = {},
        burndown = {},
        services = { title: 'Servicos', items: [] },
    } = props;

    return (
        <div className="space-y-5">
            <section className="panel-card overflow-hidden">
                <div className="grid gap-6 bg-[radial-gradient(circle_at_top_left,_rgba(34,211,238,0.18),_transparent_40%),radial-gradient(circle_at_bottom_right,_rgba(14,165,233,0.14),_transparent_35%)] px-5 py-5 lg:grid-cols-[1.2fr_0.8fr]">
                    <div>
                        <p className="text-xs font-black uppercase tracking-[0.24em] text-cyan-800/80">Visao financeira</p>
                        <h2 className="mt-3 text-3xl font-black tracking-tight text-slate-950">{headline.title}</h2>
                        <p className="mt-3 max-w-3xl text-sm leading-6 text-slate-600">{headline.subtitle}</p>
                    </div>

                    <div className="grid gap-3 sm:grid-cols-2">
                        {metricCards.slice(0, 2).map((item) => (
                            <div key={item.label} className={`rounded-[1.5rem] border border-white/60 bg-gradient-to-br p-4 ${toneClasses[item.tone] ?? toneClasses.slate}`}>
                                <p className="text-[11px] font-black uppercase tracking-[0.18em] text-slate-600">{item.label}</p>
                                <p className="mt-3 text-2xl font-black text-slate-900">{item.display}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            <section className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                {metricCards.map((item) => (
                    <MetricCard key={item.label} item={item} />
                ))}
            </section>

            <section className="grid gap-5 xl:grid-cols-2">
                {comparisons.map((card) => (
                    <ComparisonCard key={card.title} card={card} />
                ))}
            </section>

            <section className="grid gap-5 xl:grid-cols-2 2xl:grid-cols-4">
                <GaugeMeter {...gauge} />
                <EmojiSatisfaction {...satisfaction} />
                <SpiderChart {...spider} />
                <RiskMatrix {...riskMatrix} />
            </section>

            <section className="grid gap-5 xl:grid-cols-2">
                <OccurrenceHeatmap {...heatmap} />
                <BulletChart {...bullet} />
            </section>

            <section className="grid gap-5 xl:grid-cols-2">
                <RiskTreemap {...treemap} />
                <BurndownChart {...burndown} />
            </section>

            <ServicesCard services={services} />
        </div>
    );
}
