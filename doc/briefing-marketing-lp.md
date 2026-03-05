# Briefing de Marketing - Landing Page de Conversao (WordPress)

## 1) Objetivo de negocio

Construir uma LP focada em conversao para gerar:

- Leads qualificados (pessoa fisica e juridica, conforme oferta da campanha)
- Cliques para o funil de regularizacao (`/regularizacao`)
- Pedidos pagos de pesquisa
- Avanco para contratos e parcelas

Objetivo secundario:

- Captacao de parceiros/analistas com link comercial de indicacao e comissionamento por venda.

## 2) Contexto do produto (resumo para marketing)

A CPF Clean Brasil opera uma plataforma de regularizacao e recuperacao de credito com jornada ponta a ponta:

1. Captura do lead
2. Escolha do servico
3. Pagamento da pesquisa
4. Operacao interna
5. Contrato e acompanhamento
6. Relacao com cliente via portal

No sistema atual:

- Landing institucional existente em `/`
- Funil ativo em `/regularizacao`
- Fluxo de sucesso/cancelamento em:
  - `/regularizacao/sucesso`
  - `/regularizacao/cancelado`

## 3) Publico-alvo prioritario

## ICP 1 - Cliente final (B2C)

- Pessoa com CPF/CNPJ com restricoes
- Dor: nome negativado, score baixo, dificuldade de credito
- Desejo: limpar nome, recuperar poder de compra, organizar vida financeira

## ICP 2 - Parceiros/analistas (canal de aquisicao)

- Profissionais de vendas, consultores e afiliados internos
- Dor: nao ter oferta com recorrencia de ganhos
- Desejo: ganhar comissao por indicacao e evolucao do cliente

## 4) Proposta de valor (mensagem central)

"Regularize CPF/CNPJ com orientacao especializada, processo claro e atendimento humano em todo Brasil."

Pilares de prova:

- Experiencia operacional da marca
- Atendimento comercial e SAC estruturados
- Jornada digital com acompanhamento
- Integracoes de pagamento e operacao em producao

## 5) Oferta principal da LP

- Diagnostico inicial + entrada no processo de regularizacao
- CTA principal: "Iniciar regularizacao"
- Destino do CTA: `/regularizacao`

Oferta de apoio (captura no topo e meio da pagina):

- "Falar com especialista" (formulario/WhatsApp)
- "Receber avaliacao do caso"

## 6) Estrutura recomendada da pagina (wireframe de conteudo)

## 6.1 Hero (acima da dobra)

- Headline direta com beneficio:
  - "Limpe seu CPF/CNPJ e recupere seu poder de compra"
- Subheadline com mecanismo:
  - "Processo guiado, atendimento especializado e acompanhamento ate a conclusao"
- CTAs:
  - Primario: "Iniciar regularizacao" -> `/regularizacao`
  - Secundario: "Falar com especialista"
- Prova rapida:
  - selo de seguranca
  - indicativos de credibilidade (sem promessas absolutas)

## 6.2 Bloco de dor e consequencia

- Mostrar o custo de permanecer com restricao:
  - credito negado
  - juros altos
  - bloqueio de oportunidades

## 6.3 Bloco de solucao

- "Como funciona" em 3-4 passos:
  - Cadastro rapido
  - Analise do caso
  - Pagamento da pesquisa
  - Plano de regularizacao

## 6.4 Bloco de beneficios

- Atendimento em todo Brasil
- Processo orientado por especialistas
- Acompanhamento do andamento
- Canais de suporte (SAC/portal)

## 6.5 Bloco de prova/autoridade

- Cases curtos (depoimentos curtos)
- Segmentos atendidos
- Conteudo social (@cpfclean.brasil)

## 6.6 Bloco de FAQ (objecoes)

- "Quanto tempo leva?"
- "Tenho garantia?"
- "Como funciona o pagamento?"
- "Posso acompanhar meu processo?"
- "E se eu ja tiver tentado antes?"

## 6.7 Bloco final de conversao

- Reforco de urgencia etica (sem promessa enganosa)
- CTA final forte:
  - "Comecar agora" -> `/regularizacao`

## 7) Secao para canal de analistas/parceiros (obrigatoria)

Incluir uma secao "Programa de Parceiros / Analistas" com:

- Link de indicacao individual por analista
- Comissao sobre:
  - valor da pesquisa paga
  - parcelas de contrato pagas pelo cliente indicado

Mensagem sugerida:

"O analista ganha sobre a venda inicial e tambem sobre a evolucao do cliente no contrato."

Observacao operacional:

- As taxas sao configuraveis no sistema.
- Referencia atual do projeto:
  - pesquisa: 30%
  - parcelas de contrato: 40%

Se o marketing publicar esses percentuais, validar com comercial antes de ir ao ar.

## 8) Regras de copy (direcao editorial)

- Linguagem simples, direta e sem juridiquês
- Foco em resultado percebido pelo cliente
- Evitar promessa absoluta ("garantia de limpar nome")
- Usar "analise", "plano", "acompanhamento", "possibilidades reais"
- Tom: confiante, humano, consultivo

## 9) Direcao visual (WordPress)

- Visual limpo, premium, profissional
- Contraste alto nos CTAs
- Mobile-first (maior parte do trafego)
- Blocos curtos com leitura escaneavel
- Repetir CTA principal ao longo da pagina

## 10) Eventos e metricas (instrumentacao minima)

Eventos obrigatorios:

- Clique CTA Hero
- Clique CTA meio/final
- Envio de formulario
- Clique para `/regularizacao`
- Inicio de checkout no funil
- Conversao em pagamento da pesquisa

UTM:

- Capturar `utm_source`, `utm_medium`, `utm_campaign`, `utm_content`, `utm_term`
- Persistir nos links para `/regularizacao`

## 11) Entregaveis esperados do Marketing

- LP completa no WordPress (desktop + mobile)
- Versao A/B de headline e CTA
- Copys finais validadas juridico/comercial
- Plano de midia com objetivo de conversao
- Checklist de tracking e QA final

## 12) Criticos para nao quebrar operacao

- CTA principal deve apontar para: `https://cpfclean.com.br/regularizacao`
- Se usar botao "Area do cliente", apontar para `/login`
- Nao remover/ocultar informacoes de compliance (privacidade/LGPD)
- Garantir velocidade de carregamento e estabilidade mobile

## 13) Mensagens prontas (base)

### Headline 1
"Regularize seu CPF/CNPJ com apoio especializado e volte a ter acesso ao credito."

### Headline 2
"Seu nome limpo com estrategia, acompanhamento e atendimento humano."

### CTA primario
"Iniciar regularizacao"

### CTA secundario
"Falar com especialista"

### Bloco parceiro/analista
"Indique clientes e ganhe comissao na pesquisa e nas parcelas do contrato."
