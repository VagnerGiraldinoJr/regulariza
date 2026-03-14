<x-layouts.app>
    <div class="space-y-5">
        <section>
            <h1 class="panel-title">Dashboard Financeiro</h1>
            <p class="panel-subtitle mt-1">Leitura visual da receita, risco operacional e tracao do funil financeiro em um unico painel.</p>
        </section>

        <div
            id="finance-dashboard-root"
            data-finance-dashboard-root
            class="min-h-[640px]"
        ></div>
    </div>

    <script>
        window.__FINANCE_DASHBOARD__ = {{ Js::from($dashboardPayload) }};
    </script>
</x-layouts.app>
