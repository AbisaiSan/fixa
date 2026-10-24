# Plano de Desenvolvimento e Implementação: Plataforma de Estudo de Idiomas

Oct 23, 2026 · @Abisai

## Resumo e premissas

O plano leva o produto do zero ao MVP em sete jornadas locais (0 a 6) e reserva duas para depois (7 e 8: fase 2 e venda). Cada jornada entrega uma fatia usável de ponta a ponta, com backend e frontend juntos, e só termina quando cumpre seus critérios de pronto.

**Base do plano**

- [Documento de produto e regras de negócio](https://claude.ai/artifact/DPs77QvnQgfNHYHfuuGLfo): origem dos códigos de regra (MAT, REV, FLA, VOC, ATV, ERR, PLA, GAM, NIV, SEG, ACE, DAD, UPL, IAS, OPE) citados em cada tarefa.
- Protótipo do zip, com o nome provisório "Fixa": sistema de design v1, protótipo navegável com 16 rotas e as telas da fase 2 e 3 em média fidelidade. As rotas, componentes e textos deste plano saem dele.

**Premissas**

- Uma pessoa desenvolve, localmente, com o Claude no terminal.
- Stack: PHP 8.4 ou 8.5 na versão corrigida, Laravel 13 (13.30.0 ou superior, a confirmar nos avisos), Vue 3 com Inertia, PostgreSQL e Redis. O "front padrão" (Vue com Tailwind) é uma premissa a confirmar.
- Porte das jornadas: P (1 a 2 dias de trabalho focado), M (3 a 5 dias) e G (5 a 10 dias). É uma estimativa inicial, para recalibrar ao fim da jornada 1. Somadas, as jornadas 0 a 6 dão de 27 a 50 dias de trabalho focado. Não há datas, porque o ritmo depende das horas semanais disponíveis.

**Como ler cada jornada**

Cada jornada traz o objetivo, tabelas de tarefas de backend (B) e frontend (F) com IDs como J2-B3, as regras cobertas e os critérios de pronto. O ID serve para pedir a tarefa ao Claude no terminal.

## Arquitetura

&#91;embedded content: arquitetura · monólito modular, 3 apoios\]

Tudo roda em um único aplicativo Laravel. Só o domínio Ai fala com provedores externos, sempre pela interface AiProvider, o que permite trocar de Gemini para Claude sem mexer no resto.

## Do local à venda

O produto nasce como um monolito modular em Laravel, rodando local, e cresce em três estágios sem reescrita. O que muda de um estágio para o outro é sobretudo operacional e jurídico, não de código.

| Aspecto | Local (jornadas 0 a 6) | Beta fechado (após o gate 4) | Comercial (após a jornada 8) |
| --- | --- | --- | --- |
| Usuários | Só você | 5 a 20 pessoas conhecidas, como alunos do professor | Público, com contrato |
| IA | Chave gratuita do Gemini e testes com Claude, só com material próprio | Plano pago, termos lidos e sem uso dos dados para treino, teto de gasto | Plano pago com contrato de tratamento de dados e limites por plano |
| Hospedagem | Docker local (Sail) | Um servidor ou plataforma gerenciada, com HTTPS | Mesma imagem em contêiner, com web e workers separados |
| Dados | Fictícios e seus | Reais, com consentimento e backup restaurado em teste | LGPD completa, retenção definida e encarregado nomeado |
| Segurança | CI com auditoria, ASVS nível 1 na jornada 6 | Igual, mais monitoramento e plano de incidentes | Teste de invasão por terceiro e revisão periódica |
| Operação | Você | Você, com alertas | Suporte, página de status e atualização do PHP em até 48 horas |
| Cobrança | Nenhuma | Nenhuma ou por convite | Planos e assinatura |
| Documentos legais | Rascunho | Termos e privacidade publicados | Revisados por advogado |

Nenhum usuário externo entra antes do gate 4. O plano gratuito de IA pode usar o que é enviado para melhorar modelos e, em alguns provedores, exclui uso comercial; confirme nos termos de cada provedor.

**O que já nasce preparado para crescer**

- ULID nas chaves públicas, para não expor sequências e facilitar uma futura migração de dados.
- Idioma estudado como parâmetro (matrícula por idioma) e textos da interface em arquivo pt-BR.
- Interfaces `AiProvider` e, mais tarde, `BillingGateway`, para trocar provedores por configuração.
- Todo dado de conteúdo pertence a um aluno e passa por Policies, com a trava de acesso testada desde a jornada 1.
- Arquivos em disco compatível com S3 (MinIO local), filas em Redis e configuração por ambiente.
- Tabela `ai_calls` com custo por aluno desde a jornada 2, para saber se o negócio fecha antes de vender.

**O que não construir agora**

- Multi-organização (entra na jornada 8), cobrança, aplicativo nativo e microsserviços.
- Qualquer recurso de fase 2 ou 3 antes do gate 3 comprovar o uso diário.

## Roadmap

&#91;embedded content: roadmap · 9 jornadas, 5 gates\]

A ordem é a da leitura. Um gate só libera a jornada seguinte quando o critério dele é atendido, e o porte de cada jornada é recalibrado ao fim da J1.

## Jornada 0: Fundação (porte M)

O objetivo é ter um repositório que já nasce seguro, testado e fácil de rodar, com o sistema de design pronto como código, antes de qualquer tela de produto.

### Backend

| ID | Tarefa | Regras |
| --- | --- | --- |
| J0-B1 | Criar o projeto com `laravel new` e o starter kit Vue, em PHP 8.4 ou 8.5 corrigido. Registrar as versões em `docs/versoes.md` | SEG-01, SEG-02, SEG-03 |
| J0-B2 | Ambiente local com Sail: PostgreSQL, Redis, Mailpit e MinIO (S3 local). Um comando sobe tudo | DAD-05 |
| J0-B3 | Configuração por ambiente: `.env.example` sem segredos, `APP_DEBUG=false` fora do local e chaves distintas por ambiente | DAD-04, OPE-02 |
| J0-B4 | CI no provedor do repositório: Pest, Pint, Larastan, `composer audit`, `npm audit` e busca de segredos (gitleaks) | SEG-04, SEG-05, SEG-07 |
| J0-B5 | Cabeçalhos de segurança (CSP, X-Frame-Options, Referrer-Policy), CORS restrito e proteção contra falsificação de requisição (`PreventRequestForgery`) ativa | OPE-01 |
| J0-B6 | Log estruturado sem conteúdo do aluno, página de erro genérica e canal de auditoria | OPE-02, OPE-03, IAS-04 |
| J0-B7 | Padrões de dados: ULID nas chaves públicas, trait `BelongsToUser` com escopo global e horários em UTC | ACE-04 |
| J0-B8 | Pastas por domínio em `app/Domain` (Lessons, Cards, Vocabulary, Practice, Plan, Progress, Ai), com Ações e DTOs | Arquitetura |
| J0-B9 | `CLAUDE.md`, `docs/produto.md`, `docs/design/` (zip do protótipo) e `docs/adr/` para decisões | Processo |
| J0-B10 | Rotina semanal de avisos de segurança: checklist de PHP, Laravel, pacotes, imagem do servidor e repositório | SEG-04, SEG-06 |

### Frontend

| ID | Tarefa | Referência |
| --- | --- | --- |
| J0-F1 | Tokens do protótipo como variáveis CSS (`--bg`, `--sf`, `--ink`, `--pri`, `--amb`, `--hero`, `--focus`, espaço, raio e movimento), ligados ao Tailwind em `theme.extend` | Sistema de design |
| J0-F2 | Tema claro e escuro por `[data-theme]` no `<html>`, com "Automático" seguindo `prefers-color-scheme` | Sistema de design |
| J0-F3 | Fontes Plus Jakarta Sans, Gloock e Source Serif 4 e ícones Material Symbols Rounded hospedados no próprio projeto, não em CDN: o protótipo as carrega do Google Fonts, o que enfraquece a CSP e envia o IP do aluno a terceiros | OPE-01, DAD-01 |
| J0-F4 | Componentes base: Button, TextField, Select, Checkbox, Switch, Card, StatusChip, OriginTag, Toast, Banner, Skeleton, EmptyState e Sheet, sobre as primitivas acessíveis do kit (shadcn-vue) e estilizados pelos tokens | Sistema de design |
| J0-F5 | AppShell com BottomNav abaixo de 1024 px e Sidebar a partir de 1024 px, e modo foco que esconde a navegação | Sistema de design |
| J0-F6 | `TargetText` (serifa e atributo `lang`) e arquivo de strings pt-BR. Nada de "inglês" fixo no código: usar `targetLang.name` | Idioma como parâmetro |
| J0-F7 | Catálogo de componentes em rota só de desenvolvimento, nos dois temas | Sistema de design |
| J0-F8 | Testes de componente (Vitest) e verificação automática de acessibilidade (axe) no CI | Acessibilidade AA |

### Critérios de pronto

- [ ] CI verde, com `composer audit` e `npm audit` sem vulnerabilidades críticas
- [ ] Um comando sobe o ambiente completo e outro roda todos os testes
- [ ] O catálogo de componentes confere com o sistema de design nos dois temas
- [ ] PHP e Laravel nas versões corrigidas, registradas, com os serviços reiniciados depois da atualização (SEG-03)
- [ ] `CLAUDE.md` descreve comandos, convenções e regras de segurança

## Jornada 1: Contas e acesso (porte M)

Ao fim, o aluno cria a conta, entra com segurança, ativa a verificação em duas etapas e navega pelo app vazio no layout final. O starter kit oficial já traz login, cadastro, recuperação de senha e 2FA por aplicativo autenticador via Fortify, então o trabalho é configurar, endurecer e testar, não escrever do zero.

### Backend

| ID | Tarefa | Regras |
| --- | --- | --- |
| J1-B1 | Ativar a verificação de e-mail (não vem ligada no kit: implementar `MustVerifyEmail` e o middleware `verified`) e manter cadastro e recuperação de senha com link de uso único e validade curta | ACE-01, ACE-02 |
| J1-B2 | Sessão por cookie (HttpOnly, Secure, SameSite), nova sessão a cada login e saída de todos os dispositivos. Sem tokens de API simples | ACE-01 |
| J1-B3 | 2FA por aplicativo (TOTP) com códigos de recuperação, opcional para o aluno e obrigatório para o administrador, exigindo confirmação de código e de senha para ligar ou desligar | ACE-03 |
| J1-B4 | Limite de tentativas em login, cadastro e recuperação de senha, por e-mail e por IP | ACE-05 |
| J1-B5 | Papéis aluno e administrador e Policies base (o professor só entra na jornada 8) | ACE-04 |
| J1-B6 | Matrícula e perfil: idioma de instrução (pt-BR), idioma estudado (padrão inglês), fuso (padrão America/Sao\_Paulo), meta diária (padrão 10 minutos, entre 5 e 30) e tema | PLA-01, GAM-06 |
| J1-B7 | Termos de uso e política de privacidade com aceite registrado (versão, data e IP) | DAD-01, MAT-07 |
| J1-B8 | Log de auditoria para login, falha de login, troca de senha e ativação de 2FA | OPE-03 |
| J1-B9 | Esqueleto de teste de acesso entre alunos, reutilizável em todo endpoint novo: aluno B não lê, altera nem apaga dado do aluno A | ACE-04 |

### Frontend

| ID | Tarefa | Referência |
| --- | --- | --- |
| J1-F1 | Telas `/entrar`, `/cadastro`, `/verificar`, `/recuperar` e `/2fa` como no protótipo, com mensagens de erro acolhedoras | Protótipo, tela 1 |
| J1-F2 | AppShell com as cinco abas (Hoje, Aulas, Praticar, Vocabulário, Progresso) e o estado vazio de cada uma, incluindo "Enviar minha primeira aula" em Hoje | Protótipo, fluxo A |
| J1-F3 | Configurações: meta diária, idioma estudado, lembrete, tema e 2FA com códigos de recuperação. Exportar e excluir dados ficam na jornada 6 | Protótipo, tela 13 |
| J1-F4 | Aceite de termos no cadastro, com links e versão | DAD-01 |
| J1-F5 | Contrato de estados como componentes de página: `status` ready, empty, loading e error, e banner offline por cima | Sistema de design |
| J1-F6 | Acessibilidade dos formulários: rótulos, foco visível, erros com `aria-live` e navegação só por teclado | AA |

### Critérios de pronto

- [ ] Teste automatizado tenta ler, alterar e apagar dado de outro aluno e falha em todos os casos
- [ ] Limite de tentativas bloqueia o excesso e libera depois da janela
- [ ] 2FA obrigatório para administrador, com códigos de recuperação funcionando
- [ ] Todas as telas de acesso funcionam só com teclado e nos dois temas
- [ ] Gate 1 atendido: CI verde, 2FA, trava de acesso entre alunos testada

## Jornada 2: Aulas, material e IA (porte G)

O aluno envia uma aula e recebe a revisão pronta, baseada só no material dele. É a jornada de maior risco do projeto, porque reúne upload, extração de texto, IA e segurança. Por isso começa com dois testes curtos (extração e comparação de provedores) antes de construir o fluxo.

### Backend

| ID | Tarefa | Regras |
| --- | --- | --- |
| J2-B1 | Teste de extração com PDFs reais pelo poppler. Foto do caderno e PDF escaneado saíram do MVP (ADR 0002), então o teste com fotos e modelo multimódal fica para quando voltarem | UPL-03 |
| J2-B2 | Modelo `lessons` (título, data, idioma, origem, status, texto extraído) e máquina de estados: na fila, extraindo, gerando, validando, pronta ou falhou | MAT-01 |
| J2-B3 | Upload: validar o tipo real do arquivo, 10 MB e 20 páginas, nome aleatório, disco privado e entrega por link temporário e autenticado | MAT-02, UPL-01, UPL-02 |
| J2-B4 | Antivírus (ClamAV) e extração de texto em processo isolado, com limite de tempo e de memória | UPL-03 |
| J2-B5 | Regra das 50 palavras: abaixo disso, avisar o aluno e não gerar | MAT-04 |
| J2-B6 | Camada de IA: interface `AiProvider` com revisar aula, gerar cartões, gerar atividades e corrigir texto. Adaptadores para Gemini e Claude e um adaptador Falso para testes. Avaliar o Laravel AI SDK como base dos adaptadores, sempre atrás da nossa interface | Estratégia de IA |
| J2-B7 | Prompts versionados em `resources/prompts/`. O texto do aluno vai em bloco de dados delimitado, nunca como instrução, e a IA não recebe ferramentas nem dados de outros usuários | IAS-01 |
| J2-B8 | Saída estruturada (JSON) validada por esquema, uma nova tentativa e, depois, falha com aviso. Tempo limite e espera entre tentativas com `#[Tries]`, `#[Backoff]` e `#[Timeout]` | IAS-02 |
| J2-B9 | Revisão da aula: resumo de até 150 palavras, conceitos, 3 exemplos por conceito com tradução, erros comuns de falantes de português e lista "você consegue?" com 3 a 5 itens. Gerada uma vez e guardada | REV-01 a REV-04 |
| J2-B10 | Todo item gerado guarda `source_lesson_id` e `reported`. Reportar erro tira o item da rotina, abre revisão e permite desfazer. Editar, remover e regenerar | MAT-03, MAT-05 |
| J2-B11 | Geração assíncrona: o envio responde na hora e o progresso em 4 etapas é consultado por polling | Sistema de design |
| J2-B12 | Tabela `ai_calls` (provedor, tarefa, tokens, custo estimado, aluno, versão do prompt), sem conteúdo. Teto mensal configurável com alerta | IAS-03, IAS-04 |
| J2-B13 | Comando `ai:compare` (só local) que roda o mesmo material em dois provedores e grava as saídas lado a lado, para você avaliar | Estratégia de IA |

### Frontend

| ID | Tarefa | Referência |
| --- | --- | --- |
| J2-F1 | `/aulas`: lista de aulas com origem, data, resumo de cartões e status | Protótipo, tela 5 |
| J2-F2 | `/aulas/enviar`: abas Texto e PDF, título, data e idioma, limite de 10 MB e 20 páginas, declaração de direito de uso e aviso de material curto | Protótipo, tela 4, MAT-07 |
| J2-F3 | Tela de geração com skeleton em 4 etapas e `aria-busy`. O aluno pode sair e voltar | Sistema de design |
| J2-F4 | `/aulas/:id`: revisão com `OriginTag`, editar, remover, regenerar e `ReportButton` | Protótipo, tela 5 |
| J2-F5 | Estados: erro de envio que preserva o preenchido, banner de falha da IA com "tentar de novo" e estado de item em análise | Sistema de design |
| J2-F6 | Fluxo A até "Praticar esta aula": do vazio em Hoje até a revisão em poucos cliques. O destino de prática fica ligado nas jornadas 3 e 4 | Protótipo, fluxo A |

Sugestão fora das regras atuais: mostrar ao aluno o texto extraído de uma foto para corrigir antes de gerar. Reduz o risco de uma leitura ruim gerar uma revisão errada.

### Critérios de pronto

- [ ] 5 aulas reais (texto e PDF) viram revisões que você aprova
- [ ] Comparação Gemini e Claude registrada e provedor inicial escolhido
- [ ] Material com "ignore as instruções" não muda a saída (teste automatizado)
- [ ] Arquivo com extensão trocada ou PDF enorme é recusado
- [ ] Nenhum log contém texto do aluno
- [ ] Gate 2 atendido: IA validada com material real

## Jornada 3: Flashcards e vocabulário (porte M)

O aluno revisa os cartões da aula com repetição espaçada e consulta o vocabulário. O coração técnico é o agendador de revisões, escrito como classe pura e coberta por testes antes de qualquer tela.

### Backend

| ID | Tarefa | Regras |
| --- | --- | --- |
| J3-B1 | Gerar até 30 cartões por aula, a partir de vocabulário e frases-chave, na mesma tarefa assíncrona da jornada 2 | FLA-01, FLA-02 |
| J3-B2 | Deduplicação: mesma palavra com o mesmo significado não recria o cartão, só ganha a referência à nova aula | FLA-05 |
| J3-B3 | `ReviewScheduler` como classe pura em `app/Domain/Cards`, com algoritmo documentado (começar por SM-2) atrás de uma interface que permita trocar depois. Testes por tabela para Errei, Difícil, Bom e Fácil e prévia do próximo intervalo nos quatro botões | FLA-03 |
| J3-B4 | Fila diária: vencidos primeiro, até 15 novos por dia (configurável), dia calculado no fuso do aluno | FLA-04, GAM-06 |
| J3-B5 | Registro de cada resposta em `card_reviews` (cartão, nota, tempo, data), base de vocabulário, erros e métricas | FLA-03 |
| J3-B6 | Criar, editar, suspender e remover cartão | FLA-06 |
| J3-B7 | Entidade `words` (termo, classe gramatical, tradução, definição simples, 2 exemplos, colocações, aula de origem, estado). Dominada quando o intervalo é de 21 dias ou mais e não há erro nas 3 últimas respostas | VOC-01, VOC-02 |
| J3-B8 | Palavra adicionada pelo aluno, com origem "adicionada por mim" e preenchimento opcional por IA, dentro do limite diário | VOC-03, ATV-05 |
| J3-B9 | Busca e filtros por estado e por aula, com busca sem acentos (`unaccent` e `pg_trgm` no PostgreSQL) | VOC-01 |
| J3-B10 | Tabela de palavras comuns do idioma, criada vazia. A carga entra na jornada 7 | VOC-04 |

### Frontend

| ID | Tarefa | Referência |
| --- | --- | --- |
| J3-F1 | `/praticar/flashcards` com `Flashcard` que gira (Espaço ou Enter), `RateBar` com 4 notas e intervalos previstos, teclas 1 a 4 e `SessionHeader` com progresso e contador, em modo foco | Protótipo, tela 6 |
| J3-F2 | Gesto de deslizar opcional, com os botões sempre visíveis | Protótipo, tela 6 |
| J3-F3 | Modo corrida (`?modo=corrida`): 5 minutos só com vencidos | PLA-04 |
| J3-F4 | Fim de sessão com resumo, XP e "Próximo: ...", sem voltar à home | Protótipo, fluxo B |
| J3-F5 | `/vocabulario`: lista com busca e filtros, painel de detalhe no desktop e `/vocabulario/:id` no celular, e adicionar palavra própria | Protótipo, tela 7 |
| J3-F6 | Criar, editar e suspender cartão em `Sheet` | FLA-06 |
| J3-F7 | Versão simples sem conexão: os cartões da sessão já baixados continuam funcionando e as respostas são enviadas quando a conexão volta, com o banner do sistema de design. O offline completo (PWA) fica para a jornada 7 | Sistema de design |

### Critérios de pronto

- [ ] Testes do agendador cobrem os 4 botões, o reinício no erro e a mudança de dia
- [ ] Sessão de 20 cartões roda sem travar em um celular de entrada
- [ ] Nenhum cartão duplicado entre aulas
- [ ] Flashcard funciona só com teclado e o leitor de tela anuncia frente e verso
- [ ] Vencidos sempre vêm antes dos novos e o limite de 15 novos é respeitado

## Jornada 4: Escrita, leitura e caderno de erros (porte G)

O aluno pratica escrita e leitura com correção explicada, e cada erro volta no momento certo. O desenho separa o que tem gabarito fixo (corrigido sem IA, de graça e na hora) do que é livre (corrigido por IA, com limite diário), e isso mantém o custo sob controle.

### Backend

| ID | Tarefa | Regras |
| --- | --- | --- |
| J4-B1 | Atividades por aula: completar frase, traduzir do português e formar frases por contexto (dia a dia, trabalho, viagem), em sessões de 5 a 8 itens | ATV-01 |
| J4-B2 | Correção de itens com gabarito fixo sem IA, com normalização da resposta (caixa, espaços, apóstrofos e contrações como "I've" e "I have") e lista de respostas aceitas | ATV-02 |
| J4-B3 | Correção por IA dos itens livres: erro apontado, explicação em português, versão corrigida e alternativa mais natural, aceitando respostas corretas diferentes do gabarito. Saída estruturada e modelo leve | ATV-02, IAS-02 |
| J4-B4 | Resultado total, parcial ou erro por item e XP proporcional | ATV-03 |
| J4-B5 | Limite diário de correções por IA por aluno (inicial: 30), com contador. Ao atingir, só a IA é bloqueada: gabarito fixo e flashcards continuam | ATV-05 |
| J4-B6 | Textos de leitura de 80 a 200 palavras no nível do aluno, com o vocabulário da aula e 3 a 5 perguntas | ATV-04, REV-03 |
| J4-B7 | Salvar palavra desconhecida do texto no vocabulário com um toque | ATV-04, VOC-03 |
| J4-B8 | Caderno de erros: cada erro guarda texto do aluno, correção, categoria (gramática, vocabulário, preposição, ortografia), aula e repetições | ERR-01 |
| J4-B9 | Erro repetido 3 vezes ganha prioridade e volta após 1, 3 e 7 dias. Sai da lista após 3 acertos seguidos no mesmo ponto | ERR-02, ERR-03 |
| J4-B10 | Atividade de ponto fraco montada a partir do caderno, com gabarito fixo | PLA-02 |
| J4-B11 | Tabelas `practice_sessions`, `practice_items` e `attempts`, com resposta, resultado e correção guardados | ATV-01 |

### Frontend

| ID | Tarefa | Referência |
| --- | --- | --- |
| J4-F1 | `/praticar`: hub com flashcards, escrita, leitura, ponto fraco e caderno de erros | Protótipo, aba Praticar |
| J4-F2 | `/praticar/escrita/:sessao` com campo, dica, progresso e modo foco | Protótipo, tela 8 |
| J4-F3 | `FeedbackPanel`: erro destacado, explicação, versão corrigida, alternativa natural, resultado com ícone e rótulo (total, parcial, erro) e XP, anunciado com `aria-live` | Protótipo, tela 8 |
| J4-F4 | Tela de limite de IA atingido e banner em Hoje e Praticar (`aiLimitReached`) | Protótipo, tela 8 |
| J4-F5 | `/praticar/leitura/:id`: texto com palavras clicáveis, `Sheet` para salvar no vocabulário e perguntas | Protótipo, tela 9 |
| J4-F6 | `/praticar/caderno-de-erros`: lista por categoria, repetições, próxima revisão e filtros | Protótipo, tela 10 |
| J4-F7 | `/praticar/ponto-fraco`, no mesmo formato da escrita | Protótipo, bloco 3 de Hoje |
| J4-F8 | `ReportButton` em todo item gerado, com estado "em análise" e desfazer | Sistema de design |

### Critérios de pronto

- [ ] Um conjunto de 20 variações corretas por item (contrações, ordem, sinônimos) é aceito
- [ ] O limite diário bloqueia só a correção por IA
- [ ] O erro volta após 1, 3 e 7 dias e sai após 3 acertos seguidos (teste com relógio congelado)
- [ ] Nenhuma saída da IA é exibida sem escape, com teste de código malicioso no texto (IAS-02)
- [ ] Aluno consegue concluir uma sessão de escrita inteira só com teclado

## Jornada 5: Plano semanal, Hoje e gamificação (porte G)

O aluno abre o app e já vê o que fazer, e o plano se refaz quando ele falta. Essa jornada junta tudo o que veio antes e carrega o risco de tempo e fuso, então toda regra de data é testada com relógio congelado e fusos diferentes.

### Backend

| ID | Tarefa | Regras |
| --- | --- | --- |
| J5-B1 | Gerador do plano semanal toda segunda, com 3 blocos por dia (flashcards vencidos, atividade da aula mais recente e atividade de ponto fraco) dentro da meta diária | PLA-01, PLA-02 |
| J5-B2 | Recálculo na manhã seguinte à falta, idempotente por aluno e por dia. Tarefas importantes são remanejadas sem passar de 1,5 vez a meta e o que não couber é descartado, nunca acumulado. Um comando agendado a cada hora atende quem virou o dia no próprio fuso | PLA-03, GAM-06 |
| J5-B3 | Aula enviada entra no plano no mesmo dia | PLA-05 |
| J5-B4 | Estados do dia: feito, hoje, perdido e protegido por congelamento | PLA-03, GAM-02 |
| J5-B5 | Modo corrida de 5 minutos, que conta para a sequência | PLA-04, GAM-01 |
| J5-B6 | Sequência: o dia vale ao cumprir a meta mínima ou o modo corrida. Congelamento: 1 por semana, acumulando até 2, aplicado automaticamente | GAM-01, GAM-02 |
| J5-B7 | XP por atividade e por cartão, que nunca diminui. Níveis com barra até o próximo e meta semanal de dias estudados | GAM-03, GAM-04 |
| J5-B8 | Sem punição: nenhuma regra reduz XP nem mostra contagem de dias perdidos. Mensagens de retomada centralizadas em um único arquivo | GAM-05 |
| J5-B9 | Endpoint de Hoje: plano do dia, anel de meta, sequência, congelamentos e próximo bloco | PLA-06 |
| J5-B10 | Lembrete diário por e-mail, opcional, no horário escolhido e com link para cancelar | Sistema de design |
| J5-B11 | Testes de tempo com relógio congelado, fusos diferentes e virada de dia | PLA-03, GAM-06 |

### Frontend

| ID | Tarefa | Referência |
| --- | --- | --- |
| J5-F1 | `/hoje`: saudação, `ProgressRing` da meta, botão "Começar", os 3 blocos, `StreakChip`, `FreezeSlots`, atalho "Modo corrida" e atalho "Enviar aula" | Protótipo, tela 3 |
| J5-F2 | Encadeamento "Próximo: ..." ao fim de cada bloco, sem voltar à home | Protótipo, fluxo B |
| J5-F3 | Meta cumprida com confete, sequência +1 e XP animado, sem animação quando o sistema pede movimento reduzido | Sistema de design |
| J5-F4 | `/plano`: 7 dias com ícones diferentes por estado, aviso "Bom te ver de volta. Refizemos sua semana para caber no seu ritmo." e ajuste da meta de 5 a 30 minutos | Protótipo, tela 11, fluxo C |
| J5-F5 | `/progresso`: sequência, congelamentos, XP, meta semanal, nível com barra e histórico simples. Sem ranking | Protótipo, tela 12 |
| J5-F6 | Configurações: lembrete diário com horário | Protótipo, tela 13 |
| J5-F7 | Estados de Hoje: vazio, "Tudo em dia por hoje", carregando, erro e sem conexão | Sistema de design |

### Critérios de pronto

- [x] Faltar 1 e 2 dias refaz o plano e nunca passa de 1,5 vez a meta (teste)
- [x] Congelamento protege a sequência e o saldo respeita o limite de 2 (teste)
- [x] XP nunca diminui, verificado por teste de propriedade
- [ ] Você abre o app e começa a estudar em 1 clique
- [ ] Gate 3 atendido: você estuda 14 dias seguidos usando só a plataforma

## Jornada 6: Nível, privacidade e lançamento do MVP (porte M)

O produto fica pronto para receber outras pessoas com segurança. Entram o diagnóstico de nível, o controle do aluno sobre os próprios dados, a revisão de segurança e a operação mínima. Ao fim, o beta fechado pode começar.

### Backend

| ID | Tarefa | Regras |
| --- | --- | --- |
| J6-B1 | Diagnóstico de nível: teste de 10 a 15 minutos (leitura, vocabulário e escrita curta), resultado de A1 a C2 com explicação, e opção de pular e escolher o nível | NIV-01, NIV-02 |
| J6-B2 | O nível passa a calibrar textos, exemplos e atividades, e a tela do resultado avisa que é uma estimativa, não uma certificação | NIV-03, REV-03 |
| J6-B3 | Exportar os dados do aluno (aulas, cartões, respostas e erros) em arquivo e excluir a conta com remoção completa em até 30 dias, incluindo arquivos | DAD-01 |
| J6-B4 | Política de retenção dos arquivos originais, aplicada por rotina agendada | DAD-01 |
| J6-B5 | Criptografia em repouso, backups criptografados e teste de restauração | DAD-03 |
| J6-B6 | Rotação de chaves e segredos documentada e ensaiada | DAD-04 |
| J6-B7 | Painel mínimo do administrador, só com 2FA: usuários, uso e custo de IA, itens reportados e auditoria | ACE-03, OPE-03 |
| J6-B8 | Plano de resposta a incidentes escrito: quem decide, como isolar e como avisar usuários e a ANPD nos prazos da LGPD | OPE-04 |
| J6-B9 | Revisão com a lista OWASP ASVS nível 1 e teste de invasão básico. Corrigir achados críticos e altos | OPE-05 |
| J6-B10 | Monitoramento: erros da aplicação, disponibilidade, falhas de fila e alerta de custo de IA | IAS-03 |
| J6-B11 | Ambiente de homologação separado, com dados fictícios | DAD-05 |
| J6-B12 | IA em plano pago, depois de ler os termos do provedor sobre uso dos dados para treino. Registrar a decisão em um ADR | Estratégia de IA |

### Frontend

| ID | Tarefa | Referência |
| --- | --- | --- |
| J6-F1 | `/diagnostico`: introdução, questões em modo foco, resultado e escolha manual de nível | Protótipo, tela 2 |
| J6-F2 | Configurações de privacidade: exportar e excluir tudo, com confirmação clara e resumo do que será apagado | Protótipo, tela 13 |
| J6-F3 | Páginas públicas de termos, privacidade e contato do encarregado | DAD-01 |
| J6-F4 | Primeiro uso: diagnóstico, depois "Enviar minha primeira aula" | Protótipo, fluxo A |
| J6-F5 | Telas simples do administrador (usuários, uso de IA, reportados) | Painel mínimo |
| J6-F6 | Revisão final de acessibilidade (AA nos dois temas), desempenho no celular e tela por tela contra o protótipo | Sistema de design |

### Critérios de pronto

- [ ] Checklist ASVS nível 1 concluído e achados críticos e altos corrigidos
- [ ] Exportar e excluir funcionam de ponta a ponta, inclusive os arquivos
- [ ] Restauração de um backup testada em ambiente separado
- [ ] IA em plano pago, com os termos de dados lidos e registrados
- [ ] Plano de incidentes escrito e revisado por você
- [ ] Gate 4 atendido: beta fechado liberado

## Jornadas 7 e 8: fase 2 e venda

Estas duas jornadas só começam depois do gate 4 e do uso real do beta. O protótipo da fase 2 e 3 está em média fidelidade, então o acabamento visual entra aqui.

### Jornada 7: Fase 2, voz, vocabulário comum e PWA (porte G)

O aluno ganha conversa por voz, a lista das palavras mais comuns do idioma, o app instalável e um plano que aprende com os próprios erros. O ponto mais incerto é a voz, porque o modelo de texto não ouve nem fala: exige serviços de transcrição e de voz sintetizada.

**Backend**

| ID | Tarefa | Regras |
| --- | --- | --- |
| J7-B1 | Teste de voz (2 dias): comparar 3 serviços de transcrição e de voz sintetizada em inglês falado por brasileiro. Avaliar o suporte a áudio do Laravel AI SDK. Registrar em ADR | Estratégia de IA |
| J7-B2 | Adaptadores de voz atrás de interface própria, no mesmo molde da camada de IA, com limites de tamanho e duração do áudio | UPL-01, UPL-03 |
| J7-B3 | Sessão de conversa a partir da aula: cenário, turnos, transcrição e dica em português. Áudio apagado após a transcrição ou em 30 dias | DAD-02 |
| J7-B4 | Feedback da conversa: resultado, palavras para treinar e frase para refazer. A nota de pronúncia só aparece se o serviço escolhido a fornecer de forma confiável | Protótipo, fase 2 |
| J7-B5 | Áudio da aula como material, com transcrição | MAT-02 |
| J7-B6 | Carga da lista de palavras comuns, de fonte com licença verificada, ordenada por frequência | VOC-04 |
| J7-B7 | Revisão em versão rápida e aprofundada, cartões com áudio e imagem, textos de leitura em níveis e escrita com registro formal ou informal | Escopo da fase 2 |
| J7-B8 | Reavaliação periódica de nível e plano ajustado pelo caderno de erros | NIV-01, PLA-02 |
| J7-B9 | Conquistas e desafios semanais, sem ranking | GAM-05 |
| J7-B10 | Segundo idioma de teste (por exemplo, espanhol) só com configuração e conteúdo, sem mudar código. Valida a regra "o idioma é parâmetro" | Visão do produto |

**Frontend**

| ID | Tarefa | Referência |
| --- | --- | --- |
| J7-F1 | Conversa por voz: microfone, transcrição ao vivo, "Ouvir de novo", "Ver tradução", dica em português e opção de digitar | Protótipo, fase 2 |
| J7-F2 | Feedback de pronúncia: palavras com som e confiabilidade, frase para refazer e atalho "Treinar as 3 palavras" | Protótipo, fase 2 |
| J7-F3 | Aba "Mais comuns do idioma" no vocabulário | Protótipo, tela 7 |
| J7-F4 | PWA instalável: manifesto, service worker com cache das telas e dos cartões da sessão, sincronização das respostas e notificações push opcionais | Sistema de design |
| J7-F5 | Conquistas e desafios na tela de progresso | GAM |
| J7-F6 | Acabamento visual final das telas de fase 2, que estão em média fidelidade | Protótipo, fase 2 |

**Critérios de pronto**

- [ ] A conversa funciona em um celular real, com permissão de microfone negada tratada com clareza
- [ ] O áudio é apagado conforme a política e não aparece em logs
- [ ] O segundo idioma entra sem alterar código
- [ ] O app instalado abre e roda uma sessão de cartões sem conexão

### Jornada 8: Fase 3 e preparação da venda (porte G)

O professor entra na plataforma e o produto ganha o que precisa para ser vendido: organizações, cobrança, suporte e a base jurídica. É a jornada que muda o modelo de acesso, e por isso tem o teste de isolamento mais exigente do plano.

**Backend**

| ID | Tarefa | Regras |
| --- | --- | --- |
| J8-B1 | Organizações, turmas e vínculo professor e aluno, com consentimento do aluno. O professor só vê o que o aluno autorizou. Avaliar o recurso opcional de equipes do starter kit | MAT-06, ACE-04 |
| J8-B2 | Policies passam a considerar usuário e organização. Testes de isolamento entre organizações para todo endpoint | ACE-04 |
| J8-B3 | Envio de material pelo professor para a turma | Escopo da fase 3 |
| J8-B4 | Painel do professor: dias estudados, minutos médios, acerto, itens reportados, dificuldades por tema e sugestão para a próxima aula, a partir do caderno de erros agregado | Protótipo, fase 3 |
| J8-B5 | `BillingGateway` com planos, teste grátis, cancelamento e limites de IA e de armazenamento por plano. Decidir o gateway (por exemplo, Stripe com Cashier, Asaas, Mercado Pago ou Pagar.me) e a emissão de nota fiscal com um contador | Visão comercial |
| J8-B6 | E-mails transacionais com domínio autenticado (SPF, DKIM e DMARC), canal de suporte e base de ajuda | Operação |
| J8-B7 | Produção: hospedagem, domínio, HTTPS, web e workers separados, deploy com reversão, backups, observabilidade e ambientes separados | SEG-06, DAD-03 |
| J8-B8 | Jurídico: termos e privacidade revisados por advogado, contratos de tratamento de dados com os provedores de IA e de voz, encarregado nomeado e registro de consentimentos | DAD-01 |
| J8-B9 | Teste de invasão por terceiro e plano de atualização do PHP em até 48 horas | OPE-05, SEG-04 |
| J8-B10 | Métricas do produto: retenção, custo de IA por aluno ativo e conversão | Métricas de sucesso |

**Frontend**

| ID | Tarefa | Referência |
| --- | --- | --- |
| J8-F1 | Painel do professor: turmas, alunos, dificuldades por tema e envio de material | Protótipo, fase 3 |
| J8-F2 | Consentimento do aluno para compartilhar com o professor, com opção de revogar | MAT-06 |
| J8-F3 | Página de planos, checkout e gerenciamento da assinatura | Visão comercial |
| J8-F4 | Seletor de idioma completo, com os "em breve" ativos | Sistema de design |
| J8-F5 | Site público: apresentação, preços e contato | Visão comercial |
| J8-F6 | Aplicativo nativo só se houver demanda comprovada. O PWA vem primeiro | Escopo da fase 3 |

**Critérios de pronto**

- [ ] Aluno de uma organização não acessa nada de outra, em teste automatizado de todos os endpoints
- [ ] Professor vê apenas o que o aluno compartilhou e perde o acesso quando ele revoga
- [ ] Cobrança testada em modo de teste do gateway, com cancelamento e falha de pagamento
- [ ] Custo de IA por aluno ativo calculado e menor que o preço do plano
- [ ] Teste de invasão externo sem achados críticos
- [ ] Gate 5 atendido: venda liberada

## Modelo de dados

Todo dado de conteúdo pertence a uma matrícula (um aluno estudando um idioma), e todo item gerado aponta para a aula de origem. Esses dois laços sustentam a regra "tudo parte da aula" e a troca de idioma sem mudar código.

| Entidade | O que guarda | Relaciona com | Jornada |
| --- | --- | --- | --- |
| `users` | Conta, e-mail, 2FA e papel | Todas | 1 |
| `enrollments` | Idioma estudado, nível, meta diária e fuso | `users` | 1 |
| `consents` | Aceite de termos, versão e data | `users` | 1 |
| `audit_logs` | Eventos de segurança, sem conteúdo do aluno | `users` | 1 |
| `lessons` | Título, data, origem, status e texto extraído | `enrollments` | 2 |
| `lesson_files` | Arquivo original, tipo, tamanho e caminho privado | `lessons` | 2 |
| `lesson_reviews` | Resumo, conceitos, exemplos e erros comuns | `lessons` | 2 |
| `ai_calls` | Provedor, tarefa, tokens, custo e versão do prompt, sem conteúdo | `users`, `lessons` | 2 |
| `item_reports` | Item reportado, motivo e estado | Itens gerados | 2 |
| `cards` | Frente, verso, exemplo, estado de repetição e suspenso | `lessons`, `words` | 3 |
| `card_reviews` | Nota, tempo e data de cada resposta | `cards` | 3 |
| `words` | Termo, classe, tradução, exemplos e estado | `lessons`, `cards` | 3 |
| `practice_sessions` e `practice_items` | Atividades de escrita, leitura e ponto fraco | `lessons` | 4 |
| `attempts` | Resposta do aluno, resultado e correção | `practice_items` | 4 |
| `mistakes` | Erro, categoria, repetições e próxima revisão | `attempts`, `lessons` | 4 |
| `plan_days` e `plan_tasks` | Plano da semana e estado de cada dia | `enrollments` | 5 |
| `xp_events` e `streaks` | XP, sequência e congelamentos | `enrollments` | 5 |
| `conversations` e `turns` | Cenário, transcrição e feedback | `lessons` | 7 |
| `common_words` | Palavras mais frequentes por idioma | `words` | 7 |
| `organizations`, `classes` e `memberships` | Escolas, turmas, vínculos e consentimento | `users` | 8 |
| `subscriptions` | Plano, estado e limites | `users`, `organizations` | 8 |

**Convenções**

- ULID nas chaves públicas e `user_id` em toda tabela de conteúdo, com escopo global `BelongsToUser`.
- Horários em UTC. O "dia" do aluno é calculado no fuso da matrícula (GAM-06).
- Todo item gerado guarda `source_lesson_id`, `provider`, `prompt_version` e `reported`.
- Nenhum log, tabela de chamadas de IA ou evento de auditoria guarda o texto do aluno (IAS-04).
- Exclusão de conta apaga em cascata os dados e os arquivos, e a rotina de retenção cuida dos arquivos originais (DAD-01).
- Migrações pequenas e reversíveis, uma por mudança, com revisão antes de ir para qualquer ambiente com dados reais.

## Qualidade e testes

As regras de negócio são testadas em nível de unidade, o acesso entre alunos é testado em todo endpoint, e a IA nunca é chamada de verdade nos testes automáticos. Quem avalia a qualidade das respostas da IA é você, com aulas reais.

| Camada | Ferramenta | O que cobre | Quando roda |
| --- | --- | --- | --- |
| Unidade | Pest | Regras puras: repetição espaçada, normalização de resposta, plano, sequência, XP e retorno de erros, com relógio congelado | A cada commit |
| Funcionalidade | Pest com PostgreSQL real | Rotas, Policies, upload, limites e filas | A cada commit |
| Acesso entre alunos | Pest | Matriz que percorre os endpoints e tenta ler, alterar e apagar com outro aluno | A cada commit |
| IA | Adaptador Falso e esquemas | Contrato da saída estruturada e teste de injeção de instrução. Aulas reais são avaliadas à mão com `ai:compare` | Commit (falso) e jornada 2 (real) |
| Componentes | Vitest e Vue Test Utils | Componentes do sistema de design e seus estados | A cada commit |
| Fluxos | Playwright | Fluxos A, B e C do protótipo, temas claro e escuro, navegação por teclado e celular | A cada jornada e antes de cada gate |
| Acessibilidade | axe | Contraste AA, rótulos e foco | A cada commit com interface |
| Segurança | `composer audit`, `npm audit`, Larastan, gitleaks | Dependências, análise estática e segredos no código | A cada commit e semanalmente |
| Desempenho | Medição manual em celular de entrada | Sessão de cartões, tamanho das páginas e fila | Fim das jornadas 3, 5 e 6 |

**Definição de pronto de uma tarefa**

- [ ] Regra citada no commit e teste escrito, com o teste falhando antes da implementação quando for regra pura
- [ ] Pint e Larastan limpos e CI verde
- [ ] Endpoint novo entra na matriz de acesso entre alunos
- [ ] Tela nova confere com o protótipo, nos dois temas e só com teclado
- [ ] Nenhum segredo, `dd()`, `v-html` ou log com conteúdo do aluno no diff
- [ ] Documentação e `CLAUDE.md` atualizados quando a convenção mudar

**Conjunto de aulas reais**

Guarde de 5 a 10 aulas suas, de formatos diferentes, em `tests/aulas-reais/`, uma pasta ignorada pelo Git, porque o material pode ser conteúdo protegido do seu professor. Elas alimentam `ai:compare`, servem de base para ajustar prompts e protegem contra regressão quando o provedor ou o modelo mudar.

## Segurança por jornada

Os requisitos do documento de produto entram na jornada em que o risco aparece, e cada um tem uma verificação automática ou uma checagem de gate. Nenhuma jornada fecha com requisito dela pendente.

| Jornada | Requisitos que entram | Como é verificado |
| --- | --- | --- |
| 0 | SEG-01 a SEG-07, DAD-04, DAD-05, OPE-01, OPE-02 | CI com auditoria de dependências e busca de segredos, teste dos cabeçalhos de segurança |
| 1 | ACE-01 a ACE-05, DAD-01 (aceite), OPE-03 | Teste de acesso entre alunos, limite de tentativas, 2FA e auditoria de login |
| 2 | UPL-01 a UPL-03, IAS-01 a IAS-04, MAT-06 | Arquivos maliciosos, injeção de instrução, logs sem conteúdo, teto de gasto |
| 3 a 5 | ACE-04 e ACE-05 em cada endpoint novo, IAS-02 em cada tela com texto gerado | Matriz de acesso automática e teste de escape de saída |
| 6 | DAD-01 a DAD-03, SEG-06, OPE-04, OPE-05 | Exportar e excluir de ponta a ponta, restauração de backup, ASVS nível 1 e teste de invasão básico |
| 7 | DAD-02 (áudio) e limites de áudio | Áudio apagado pela rotina, tamanho e duração limitados, permissão de microfone |
| 8 | ACE-04 por organização, contratos de dados com provedores, SEG-04 em produção | Teste de isolamento entre organizações e teste de invasão externo |

**Rotina contínua, desde a jornada 0**

- Verificação semanal de avisos de PHP, Laravel, pacotes, imagem do servidor e repositório (SEG-04).
- Correção crítica em até 48 horas, no mesmo dia se o servidor estiver exposto à internet.
- Depois de atualizar o PHP, reiniciar PHP-FPM, Apache e os workers de fila. Atualizar só a aplicação não troca o PHP que roda por baixo dela (SEG-03).
- Troca de chaves e senhas guardadas por um servidor que ficou sem a correção (DAD-04).

**Procedimento para aviso crítico**

1. Identificar onde o componente roda: seu computador, o ambiente local, homologação e produção, incluindo imagens e pacotes que embutem o PHP.
2. Atualizar para a versão corrigida, reconstruir a imagem e reimplantar.
3. Reiniciar os serviços que executam o código e conferir a versão em execução, não só a instalada.
4. Olhar os logs desde antes da divulgação: no caso do PHP de outubro de 2026, a Amazon registrou uma tentativa de exploração datada de 17 de outubro, quatro dias antes do aviso, sem ficar claro se teve sucesso.
5. Se o servidor ficou exposto sem a correção, trocar chaves de IA, banco, e-mail e segredos de sessão, e avaliar se há incidente a comunicar (OPE-04).
6. Registrar no ADR a causa, o tempo até a correção e o que mudar no processo.

## Como trabalhar com o Claude no terminal

O ritmo é uma tarefa pequena por vez, sempre citando o ID da tarefa e as regras, com teste antes de código nas regras puras e revisão sua em cada diff. O Claude escreve rápido, e por isso a revisão de segurança é a parte que não pode ser delegada por completo.

**O que colocar no repositório**

- `CLAUDE.md` na raiz: stack e versões, comandos (subir ambiente, testar, formatar, auditar), estrutura de pastas, regras de segurança que nunca podem ser quebradas e links para os documentos abaixo.
- `docs/produto.md`: o documento de produto e regras de negócio exportado.
- `docs/plano.md`: este plano exportado.
- `docs/design/`: o zip do protótipo extraído (Sistema de Design, Prototipo, Fase 2 e 3 e as capturas de tela).
- `docs/adr/`: uma nota curta por decisão (banco, provedor de IA, extração, voz, cobrança).

**Sobre o protótipo**

Os arquivos `.dc.html` usam um formato de template próprio do Claude Design e não são código para copiar para o Vue. Use-os como referência de tela, texto, rota e comportamento, junto com as capturas. Os dados de exemplo (aula de Present Perfect, cartões, erros, semana) viram seeders e fixtures de teste, e o `CLAUDE.md` deve dizer isso.

**Ritmo de cada tarefa**

1. Abrir a tarefa pelo ID e pelas regras, e pedir um plano de até 10 linhas antes de qualquer código.
2. Nas regras puras (repetição espaçada, plano, XP), pedir os testes primeiro e ver falharem.
3. Implementar só o que a tarefa pede, em uma branch por jornada e commits pequenos.
4. Rodar Pest, Pint, Larastan e `composer audit`, e `npm audit` quando mexer no frontend.
5. Revisar o diff com a lista de segurança abaixo antes de aceitar.
6. Ao fim de cada jornada, abrir uma sessão nova só de revisão de segurança, sem o contexto de quem escreveu o código.

**Exemplo de pedido**

```markdown
Tarefa J3-B3, regras FLA-03 e FLA-04.
Leia CLAUDE.md e docs/produto.md. Proponha o plano em até 10 linhas.
Depois escreva primeiro os testes Pest do ReviewScheduler (Errei, Difícil, Bom e Fácil,
reinício no erro, mudança de dia) e implemente a classe pura em app/Domain/Cards
atrás de uma interface. Não toque em outras pastas.
No fim, rode Pest, Pint e Larastan e liste os arquivos alterados.
```

**Lista de revisão de cada diff**

- Consulta sem filtro de dono, ou rota sem Policy.
- `$guarded = []`, validação ausente ou campo sensível aceito em massa.
- `v-html`, `{!! !!}` ou saída da IA exibida sem escape.
- Log, evento ou exceção que carregue texto do aluno, e `dd()` ou `dump()` esquecidos.
- Dependência nova sem justificativa, ou versão fora do `composer.lock` e do `package-lock.json`.
- Segredo no código, no `.env.example` ou nos testes.
- Tela que diverge do protótipo, do tema escuro ou do uso só por teclado.

## Riscos e mitigações

Os três maiores riscos são vazamento entre alunos, conteúdo errado gerado pela IA e crescimento de escopo antes de o uso diário ser comprovado.

| Risco | Impacto | Mitigação | Onde |
| --- | --- | --- | --- |
| Aluno acessar dados de outro aluno | Crítico | ULID, Policies, escopo global e matriz de acesso testada em todo endpoint | J1 em diante |
| IA gera conteúdo errado ou fora da aula | Alto | Só material da aula, saída validada, botão de reportar, conjunto de aulas reais e comparação de provedores | J2 |
| Escopo crescer antes de o uso diário ser provado | Alto | Gates, e fase 2 e 3 só depois do gate 3 e do beta | Roadmap |
| Vulnerabilidade crítica em PHP, Laravel ou pacotes | Alto | Verificação semanal, correção em até 48 horas, imagens reconstruídas e serviços reiniciados | J0 em diante |
| Foto do caderno com leitura ruim | Alto | Teste de extração, reserva com modelo multimódal e, como sugestão, conferir o texto lido antes de gerar | J2 |
| Custo de IA maior que o previsto | Médio a alto | Geração guardada, limite diário, teto mensal, modelo leve nas correções e custo por aluno medido desde a J2 | J2 e J4 |
| Plano semanal com erros de fuso e de virada de dia | Médio | Relógio congelado, recálculo idempotente e testes por fuso | J5 |
| Limites do plano gratuito de IA mudarem sem aviso | Médio | Interface única, adaptador Falso nos testes e segundo provedor pronto | J2 |
| Instruções escondidas no material enviado (injeção) | Médio | Texto tratado como dado, saída validada, IA sem ferramentas e teste automatizado | J2 |
| Material do professor com direitos autorais | Médio | Declaração de uso (MAT-07), termos e revisão jurídica na J8 | J1 e J8 |
| Voz com qualidade ruim ou cara em português e inglês | Médio | Teste de voz antes de construir, nota de pronúncia só se confiável e opção de digitar | J7 |
| Tempo disponível de uma pessoa só | Alto | Tarefas pequenas, gates, porte recalibrado ao fim da J1 | Todas |
| Protótipo e código divergirem | Baixo a médio | Tokens como variáveis, catálogo de componentes e conferência contra o protótipo na revisão | J0 em diante |

## Decisões em aberto

Nenhuma destas bloqueia a J0. Cada uma tem a jornada em que precisa ser fechada e uma sugestão inicial, que vale até você decidir outra coisa.

| Decisão | Opções | Sugestão inicial | Fechar até |
| --- | --- | --- | --- |
| Nome do produto | Manter "Fixa" ou trocar | Manter como provisório e checar disponibilidade de domínio e marca antes do gate 4 | J6 |
| Provedor de IA principal | Gemini, Claude ou outro | Decidir pelo resultado do `ai:compare` com suas aulas reais, mantendo a interface única | J2 |
| Plano pago de IA para uso com terceiros | Chave paga do provedor escolhido | Só contratar no beta, porque o plano gratuito pode usar dados e não permite uso comercial | J6 |
| Tamanho do beta | 3, 5 ou 10 pessoas | De 3 a 5 pessoas que você conhece, cada uma com a própria aula | J6 |
| Algoritmo de repetição | SM-2 ou FSRS | SM-2 atrás de uma interface, trocando por FSRS se os dados do gate 3 pedirem | J3 e J5 |
| Onde hospedar o beta | VPS própria, PaaS ou contêiner gerenciado | Escolher na J6 com a lista de preparação para venda; até lá tudo roda local | J6 |
| Armazenamento de arquivos | Disco local, MinIO ou S3 | MinIO no Sail e S3 compatível em produção, sempre pela interface de disco do Laravel | J2 |
| Cobrança | Gateway próprio, Stripe, Paddle ou outro | Só depois do gate 4, atrás de uma interface de cobrança | J8 |
| Idiomas depois do inglês | Espanhol, francês, outro | Escolher pelo interesse dos alunos do beta; o modelo de dados já considera idioma por curso | J8 |
| Termos e privacidade | Texto próprio ou revisão jurídica | Rascunho na J6 e revisão de profissional antes de qualquer cobrança | J6 e J8 |
| Voz na fase 3 | Modelo de voz do mesmo provedor ou serviço separado | Decidir depois do teste de voz da J7 | J7 |

## Fontes

Consultadas em 23/10/2026. Confirme as versões e os avisos de segurança nas páginas oficiais antes de iniciar a J0.

- [Laravel 13: starter kits](https://laravel.com/docs/13.x/starter-kits) (Vue com Inertia, Fortify, 2FA e verificação de e-mail)
- [Laravel 13: notas de lançamento](https://laravel.com/docs/13.x/releases) (requisitos de PHP e prazo de correções de segurança)
- [Laravel AI SDK](https://laravel.com/ai) (SDK oficial de IA, avaliado atrás de interface própria)
- [PHP: arquivo de notícias de 2026](https://www.php.net/archive/2026.php#2026-10-21-1) (lançamentos de correção de 21/10/2026)
- [Rescana: falhas de execução remota no PHP, outubro de 2026](https://www.rescana.com/post/php-rce-flaws-patch-october-2026-prepare-now) (relato de terceiros usado no procedimento de aviso crítico)
