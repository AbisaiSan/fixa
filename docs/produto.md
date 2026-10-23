# Plataforma de Estudo de Idiomas: Produto e Regras de Negócio

Oct 23, 2026 · @Abisai

## Visão e problema

A plataforma transforma cada aula de idioma em uma rotina diária de revisão e prática, para que o aluno fixe o que o professor ensinou em vez de esquecer.

**A dor.** O aluno assiste à aula, mas não tem onde praticar o conteúdo do dia nem uma forma organizada de revisar depois. Sem prática frequente, o aprendizado se perde.

**A proposta.** O aluno envia o material da aula. A plataforma, com apoio de IA, gera revisão, flashcards, vocabulário e atividades de escrita, leitura e fala, sempre baseados naquela aula. Um plano semanal organiza o estudo e se refaz sozinho quando o aluno falta.

**Princípios do produto.**

- Tudo parte da aula: nenhum conteúdo aparece sem origem no material enviado ou em uma lista de vocabulário comum do idioma.
- Constância acima de intensidade: sessões curtas e diárias, com meta mínima pequena.
- Poucos cliques: ao abrir o app, o aluno já vê o que fazer hoje.
- O idioma é um parâmetro: o produto serve para qualquer idioma, começando pelo inglês, com instrução em português.

## Usuários

O produto atende três perfis, e o MVP foca no aluno.

| Perfil | Objetivo | O que faz na plataforma | Fase |
| --- | --- | --- | --- |
| Aluno | Fixar o que aprendeu na aula e manter o hábito de estudo | Envia material, revisa, pratica, acompanha o progresso | MVP |
| Professor | Acompanhar a evolução dos alunos e enviar material para a turma | Envia material, vê dificuldades por aluno e por tema | Fase 3 |
| Administrador | Operar a plataforma com segurança | Gerencia usuários, limites de uso de IA, denúncias de conteúdo e auditoria | MVP (básico) e fase 3 |

Premissa de público inicial: adulto que estuda inglês com um professor e tem de 10 a 20 minutos por dia para praticar.

## Jornada principal

O aluno envia a aula uma vez, e o plano diário a coloca na rotina em ciclos curtos que se corrigem sozinhos.

&#91;embedded content: jornada do aluno · 3 faixas, 1 ciclo diário\]

A aula entra no plano no mesmo dia (PLA-05). Cada dia tem flashcards vencidos, uma atividade da aula e uma sobre um ponto fraco (PLA-02). Os erros voltam como atividade após 1, 3 e 7 dias (ERR-02), e faltar um dia refaz o plano na manhã seguinte (PLA-03).

## Escopo por fase

O MVP entrega o ciclo completo para o aluno (enviar a aula, revisar, praticar leitura e escrita, manter a constância). Fala e visão do professor ficam para as fases seguintes.

| Módulo | MVP | Fase 2 | Fase 3 |
| --- | --- | --- | --- |
| Material da aula | Texto, PDF e foto do caderno | Áudio da aula | Envio pelo professor para a turma |
| Revisão da aula | Resumo, conceitos, exemplos e erros comuns | Versão rápida e versão aprofundada | Igual à fase 2 |
| Flashcards | Repetição espaçada com cartões da aula | Cartões com áudio e imagem | Igual à fase 2 |
| Vocabulário | Palavras da aula e palavras salvas pelo aluno | Lista das palavras mais comuns do idioma | Igual à fase 2 |
| Escrita | Frases por contexto (dia a dia, trabalho) e textos curtos, com correção explicada | Mais cenários e escolha de registro formal ou informal | Igual à fase 2 |
| Leitura | Textos curtos com o vocabulário da aula e perguntas | Textos em níveis de dificuldade | Igual à fase 2 |
| Fala | Fora do escopo | Conversa por voz por cenário e feedback de pronúncia | Igual à fase 2 |
| Caderno de erros | Registro automático dos erros do aluno | Atividades dirigidas aos pontos fracos | Igual à fase 2 |
| Plano semanal | Plano diário curto que se refaz quando o aluno falta | Ajuste pelo caderno de erros | Igual à fase 2 |
| Gamificação | Sequência de dias com congelamento, XP e metas semanais | Conquistas e desafios | Ranking opcional por turma |
| Nível | Diagnóstico inicial (A1 a C2) | Reavaliação periódica | Igual à fase 2 |
| Professor | Fora do escopo | Fora do escopo | Painel por aluno e por turma |
| Idiomas | Inglês | Estrutura pronta para outros idiomas | Segundo idioma disponível |
| Plataforma | Site que funciona bem no celular | Instalável no celular (PWA) | App nativo, se houver demanda |

## Regras de negócio

Cada regra tem um código para servir de referência no plano de desenvolvimento e nos testes. Os números são valores iniciais propostos e devem ser configuráveis.

### Material da aula (MAT)

1. **MAT-01.** Cada envio vira uma aula com título, data e idioma estudado. O aluno pode editar título e data.
2. **MAT-02.** Formatos aceitos no MVP: texto colado, PDF e imagem (foto do caderno). Limite inicial de 10 MB por arquivo e 20 páginas por envio.
3. **MAT-03.** Todo conteúdo gerado guarda a aula de origem e a exibe na tela ("Da aula de 21/10").
4. **MAT-04.** A IA gera conteúdo apenas a partir do material enviado. Se o texto legível tiver menos de 50 palavras, a plataforma avisa e não gera.
5. **MAT-05.** O conteúdo gerado entra direto na rotina e o aluno pode editar, remover ou regenerar qualquer item. Um botão de "reportar erro" fica em todo item gerado.
6. **MAT-06.** O material é privado do aluno. Na fase 3, o professor só acessa o que o aluno vinculado a ele autorizar.
7. **MAT-07.** Ao enviar, o aluno declara ter direito de usar o material, e isso consta nos termos de uso.

### Revisão da aula (REV)

1. **REV-01.** A revisão tem estrutura fixa: resumo de até 150 palavras, conceitos principais, 3 exemplos por conceito, erros comuns de falantes de português e uma lista "você consegue?" de 3 a 5 itens.
2. **REV-02.** Explicações em português. Exemplos no idioma estudado, com tradução.
3. **REV-03.** A linguagem dos exemplos acompanha o nível do aluno (A1 a C2).
4. **REV-04.** A revisão é gerada uma vez por aula e guardada. Nova geração só ocorre por pedido do aluno.

### Flashcards (FLA)

1. **FLA-01.** Cada cartão tem frente (palavra, frase ou conceito), verso (resposta, exemplo e tradução) e aula de origem.
2. **FLA-02.** Cada aula gera no máximo 30 cartões novos, escolhidos entre vocabulário e frases-chave.
3. **FLA-03.** A revisão segue repetição espaçada. Após cada cartão o aluno marca Errei, Difícil, Bom ou Fácil. O intervalo cresce com os acertos e reinicia no erro. O algoritmo será documentado e aberto (como o SM-2).
4. **FLA-04.** Limite inicial de 15 cartões novos por dia. Revisões vencidas aparecem sempre antes dos novos.
5. **FLA-05.** Cartão repetido (mesma palavra e mesmo significado) não é recriado. Apenas ganha a referência à nova aula.
6. **FLA-06.** O aluno pode criar, editar e suspender cartões.

### Vocabulário (VOC)

1. **VOC-01.** Cada palavra tem termo, classe gramatical, tradução, definição simples, 2 exemplos, colocações, aula de origem e estado (nova, aprendendo, dominada).
2. **VOC-02.** O estado muda conforme o desempenho nos flashcards. A palavra vira dominada com intervalo de revisão de 21 dias ou mais e sem erro nas 3 últimas respostas.
3. **VOC-03.** Palavras salvas pelo aluno fora da aula entram com a origem "adicionada por mim".
4. **VOC-04.** A lista das palavras mais comuns do idioma (fase 2) vem de uma lista de frequência com licença verificada, ordenada por frequência.

### Atividades de escrita e leitura (ATV)

1. **ATV-01.** Escrita no MVP: completar frase, traduzir do português e formar frases a partir das palavras da aula em um contexto (dia a dia, trabalho, viagem). Cada sessão tem de 5 a 8 itens.
2. **ATV-02.** A correção aponta o erro, explica em português, mostra a versão corrigida e sugere uma alternativa mais natural. Respostas corretas diferentes do gabarito são aceitas.
3. **ATV-03.** Cada item recebe resultado total, parcial (erro pequeno) ou erro, e o XP é proporcional.
4. **ATV-04.** Leitura: texto de 80 a 200 palavras com o vocabulário e a estrutura da aula, no nível do aluno, com 3 a 5 perguntas. Palavra desconhecida pode ser salva no vocabulário com um toque.
5. **ATV-05.** Cada aluno tem um limite diário de correções feitas por IA (inicial: 30). Ao atingir o limite, atividades com gabarito fixo e flashcards continuam disponíveis.

### Caderno de erros (ERR)

1. **ERR-01.** Cada erro guarda o texto do aluno, a correção, a categoria (gramática, vocabulário, preposição, ortografia), a aula e o número de repetições.
2. **ERR-02.** Erro repetido 3 vezes ganha prioridade e volta nas atividades após 1, 3 e 7 dias.
3. **ERR-03.** O erro sai da lista depois de 3 acertos seguidos no mesmo ponto.

### Plano semanal adaptativo (PLA)

1. **PLA-01.** O plano da semana é gerado toda segunda-feira. A meta diária mínima inicial é de 10 minutos e o aluno pode ajustar entre 5 e 30.
2. **PLA-02.** Cada dia tem 3 blocos: flashcards vencidos, uma atividade da aula mais recente e uma atividade sobre um ponto fraco.
3. **PLA-03.** Se o aluno não estudar em um dia, o plano é recalculado na manhã seguinte. Tarefas importantes atrasadas são redistribuídas pelos dias restantes sem passar de 1,5 vez a meta diária. O que não couber é descartado, nunca acumulado como dívida.
4. **PLA-04.** O modo corrida é uma sessão de 5 minutos só com flashcards vencidos e conta para a sequência.
5. **PLA-05.** Uma aula enviada entra no plano no mesmo dia.
6. **PLA-06.** A tela inicial mostra o que fazer hoje, a no máximo 1 clique de iniciar.

### Gamificação (GAM)

1. **GAM-01.** O dia conta para a sequência quando o aluno cumpre a meta mínima ou faz o modo corrida.
2. **GAM-02.** O aluno ganha 1 congelamento por semana, acumulando até 2. Ele protege a sequência em um dia perdido e é aplicado automaticamente.
3. **GAM-03.** O XP é dado por atividade concluída e por cartão revisado, e nunca diminui.
4. **GAM-04.** Há meta semanal de dias estudados e barra de progresso até o próximo nível.
5. **GAM-05.** Sem punição: perder um dia só afeta a sequência. As mensagens de retomada incentivam, sem culpa. Não há ranking no MVP.
6. **GAM-06.** O dia é calculado no fuso do aluno (padrão America/Sao\_Paulo).

### Nível do aluno (NIV)

1. **NIV-01.** O diagnóstico inicial é um teste de 10 a 15 minutos com leitura, vocabulário e escrita curta. O resultado vai de A1 a C2 e traz uma explicação.
2. **NIV-02.** O aluno pode pular o teste e escolher o nível manualmente.
3. **NIV-03.** O nível é uma estimativa para calibrar a dificuldade e nunca uma certificação. Isso aparece na tela do resultado.

## Estratégia de IA

A IA fica atrás de uma camada própria no servidor, com uma interface única e um adaptador por provedor, para trocar de modelo sem reescrever o produto.

- **Funções da camada:** gerar a revisão da aula, gerar flashcards e vocabulário, gerar atividades e textos de leitura, corrigir o texto do aluno e, na fase 2, conduzir conversas por cenário.
- **Provedores:** desenvolver e testar com a chave gratuita do Gemini (AI Studio, sem cartão) e comparar com o Claude usando aulas reais. O provedor ativo é definido por configuração, e a decisão final de produção vem dessa comparação.
- **Custos:** conteúdo gerado é guardado e não é regenerado sem pedido (REV-04). Há limite diário de correções por aluno (ATV-05). Correções curtas usam modelos mais leves e revisões de aula usam modelos mais fortes.
- **Qualidade:** a IA responde em formato estruturado, e o servidor valida a resposta antes de salvar. Se a validação falhar, tenta uma vez de novo e depois avisa o aluno. Os itens reportados com "reportar erro" (MAT-05) alimentam uma lista de revisão.
- **Dados enviados à IA:** só o conteúdo necessário para a tarefa, sem nome, e-mail ou outros dados pessoais do aluno.
- **Fala (fase 2):** exige serviços de transcrição de voz e de voz sintetizada, separados do modelo de texto. O provedor será definido na fase 2.
- **Ambiente de testes:** planos gratuitos de IA podem usar os dados enviados para melhorar modelos. Nos testes, usar só material próprio e frases fictícias, nunca dados de outras pessoas.

## Segurança e privacidade

Segurança é requisito de entrada: nenhuma fase termina sem cumprir os itens abaixo. Em 21/10/2026 o PHP lançou correções para duas falhas críticas de execução remota de código (CVE-2026-109618 e CVE-2026-109619), corrigidas nas versões 8.5.12, 8.4.27, 8.3.36 e 8.2.35. A correção só vale depois de atualizar o PHP e reiniciar os serviços que o executam, e atualizar a aplicação sozinha não resolve. Por isso as regras de manutenção abaixo vêm antes das demais.

### Plataforma e dependências (SEG)

1. **SEG-01.** Usar PHP 8.4 ou 8.5 na versão corrigida mais recente. O PHP 8.2 deixa de ter suporte em 31/12/2026, então não entra no projeto.
2. **SEG-02.** Usar Laravel 13, na versão 13.30.0 ou superior. Essa versão cobre avisos recentes do framework: falha de autenticação no TokenGuard em certas configurações de MySQL e MariaDB (aviso de 19/10/2026), falha no envio de e-mail (corrigida na 13.10.0) e XSS na página de depuração (corrigido na 13.30.0). Confirmar cada aviso na página de segurança do Laravel antes de fixar a versão.
3. **SEG-03.** Após atualizar o PHP em qualquer ambiente (inclusive o computador de desenvolvimento), reiniciar PHP-FPM, Apache e qualquer processo de longa duração. Registrar a versão do PHP de cada ambiente.
4. **SEG-04.** Rodar `composer audit` e `npm audit` a cada alteração no pipeline e uma verificação semanal de avisos de segurança (PHP, Laravel, pacotes, imagem do servidor). Correções críticas são aplicadas em até 48 horas.
5. **SEG-05.** Versões travadas por `composer.lock` e `package-lock.json`. Cada dependência nova passa por revisão (mantida, popular, necessária). Preferir poucas dependências.
6. **SEG-06.** Imagens e servidores são reconstruídos e reimplantados quando sai correção do PHP, do sistema operacional ou do servidor web.
7. **SEG-07.** Repositório e CI: autenticação em dois fatores, tokens com escopo mínimo e validade curta, segredos fora do código. Se o GitLab for autogerenciado, aplicar as correções críticas no mesmo dia.

### Autenticação e acesso (ACE)

1. **ACE-01.** Autenticação com os componentes oficiais do Laravel (starter kit, Fortify ou Sanctum), sem código próprio. Frontend e backend conversam por sessão com cookie. Não usar tokens de API simples sem necessidade.
2. **ACE-02.** Senhas com o hash padrão do Laravel, verificação de e-mail, limite de tentativas e recuperação de senha com link de uso único e curta validade.
3. **ACE-03.** Autenticação em dois fatores opcional para o aluno e obrigatória para administradores.
4. **ACE-04.** Todo dado pertence a um aluno, e toda consulta é filtrada pelo dono por meio de Policies. Testes automatizados tentam acessar dados de outro aluno (falha de referência direta a objeto) e precisam falhar.
5. **ACE-05.** Limite de requisições em login, cadastro, recuperação de senha, envio de material e chamadas de IA.

### Dados e privacidade (DAD)

1. **DAD-01.** LGPD: coletar o mínimo, pedir consentimento claro para guardar materiais e, na fase 2, áudios. O aluno pode exportar e excluir todos os seus dados, com exclusão completa em até 30 dias.
2. **DAD-02.** Áudio de fala (fase 2) é apagado após a transcrição ou em até 30 dias, o que ocorrer primeiro.
3. **DAD-03.** HTTPS obrigatório com HSTS. Banco e arquivos criptografados em repouso. Backups criptografados, com restauração testada a cada trimestre.
4. **DAD-04.** Segredos (chaves de IA, banco, e-mail) ficam em variáveis de ambiente ou cofre, nunca no código nem no navegador, com chaves diferentes por ambiente e rotação possível. Se um servidor ficar sem correção do PHP por algum tempo, tratar as chaves dele como expostas e trocá-las.
5. **DAD-05.** Ambientes separados (desenvolvimento, homologação e produção). Fora da produção, apenas dados fictícios.

### Upload de arquivos (UPL)

1. **UPL-01.** Validar o tipo real do arquivo (não só a extensão), o tamanho e o número de páginas conforme MAT-02.
2. **UPL-02.** Guardar fora da pasta pública, com nomes aleatórios, e servir apenas por link temporário e autenticado.
3. **UPL-03.** Verificar com antivírus e processar PDF e imagem em processo isolado, com limite de tempo e de memória.

### IA (IAS)

1. **IAS-01.** O texto do material do aluno é tratado como dado, nunca como instrução. A IA não tem acesso a ferramentas, arquivos nem a dados de outros usuários.
2. **IAS-02.** As respostas da IA são validadas contra um esquema e escapadas antes de exibir, para evitar injeção de código na tela.
3. **IAS-03.** Teto de gasto mensal no provedor, com alerta, além dos limites por aluno (ATV-05).
4. **IAS-04.** Registrar qual provedor processou cada chamada, sem guardar o conteúdo nos logs.

### Aplicação e operação (OPE)

1. **OPE-01.** Manter ativas as proteções padrão do Laravel: CSRF, escape de saída e consultas parametrizadas. Configurar cabeçalhos de segurança (CSP, X-Frame-Options, Referrer-Policy) e CORS restrito.
2. **OPE-02.** Em produção, `APP_DEBUG=false` e página de erro genérica.
3. **OPE-03.** Log de auditoria para login, exclusão, exportação e ações de administrador, com alertas para comportamento anormal.
4. **OPE-04.** Plano de resposta a incidentes escrito antes do lançamento: quem decide, como isolar, e como avisar usuários e a ANPD nos prazos da LGPD.
5. **OPE-05.** Antes de cada lançamento de fase, revisão de segurança com a lista OWASP ASVS nível 1 e um teste de invasão básico.

## Métricas de sucesso

O produto dá certo se o aluno volta todo dia e lembra do que estudou. As metas abaixo são propostas iniciais e devem ser recalibradas depois do protótipo.

| Métrica | Definição | Meta inicial |
| --- | --- | --- |
| Tempo até a primeira atividade | Do envio da aula à primeira atividade concluída | Até 3 minutos |
| Dias estudados por semana | Média por aluno ativo | 4 ou mais |
| Plano do dia cumprido | Dias com a meta mínima atingida, sobre dias de plano | 60% ou mais |
| Retorno após falta | Alunos que voltam em até 3 dias depois de faltar | 50% ou mais |
| Retenção na semana 4 | Alunos ativos na semana 4, sobre os que enviaram a primeira aula | 40% ou mais |
| Memorização | Cartões respondidos Bom ou Fácil 7 dias depois do primeiro acerto | 80% ou mais |
| Qualidade da IA | Itens reportados como erro, sobre itens gerados | 2% ou menos |
| Custo de IA por aluno ativo | Gasto mensal no provedor, dividido por alunos ativos | Definir após os testes |

## Premissas e decisões em aberto

O documento segue as premissas abaixo até que cada ponto seja decidido. Quando uma decisão mudar, as regras afetadas devem ser revistas.

| Tema | Situação | Premissa adotada |
| --- | --- | --- |
| Stack | Definida | Laravel (PHP 8.4 ou 8.5) com o frontend padrão da equipe |
| Desenvolvimento | Definido | Local, com o Claude no terminal, após o protótipo do frontend |
| Uso pessoal ou comercial | Em aberto | Começa com poucos alunos, mas privacidade e segurança seguem como produto comercial |
| Participação do professor | Em aberto | Apenas na fase 3 |
| Formato do material | Em aberto | Texto, PDF e foto do caderno no MVP |
| Web ou celular | Em aberto | Site responsivo no MVP |
| Nível de inglês do aluno inicial | Em aberto | O diagnóstico (NIV-01) cobre qualquer nível |
| Provedor de IA | Em teste | Gemini (chave gratuita) comparado ao Claude com aulas reais |
| Provedor de voz (fase 2) | Em aberto | Definir antes da fase 2 |
| Lista de palavras comuns (fase 2) | Em aberto | Escolher fonte com licença verificada |
| Modelo de cobrança | Em aberto | Só se o produto for comercial |
| Plano de desenvolvimento | Próximo documento | Escrito após o protótipo do frontend, com as regras deste documento como base |

## Fontes

Consultadas em 23/10/2026. Os avisos de segurança mudam rápido, então confirme as versões nas páginas oficiais antes de fixá-las no projeto.

- [Rescana: PHP 8.5.12, 8.4.27, 8.3.36 e 8.2.35 corrigem duas falhas críticas](https://www.rescana.com/post/php-rce-flaws-patch-october-2026-prepare-now) (página aberta; cita o aviso oficial do PHP em php.net/archive/2026.php)
- [Laravel: política de suporte](https://laravel.com/docs/12.x/releases) (resultado de busca, não aberto)
- [CVE-2026-102279: XSS na página de depuração do Laravel](https://socradar.io/free-tools/cve-radar/CVE-2026-102279) (resultado de busca, não aberto)
- [Aviso do Laravel sobre TokenGuard, de 19/10/2026](https://rocket-boys.co.jp/?p=44675) (relato de terceiros; a página bloqueou a abertura e o aviso oficial no GitHub do Laravel não foi verificado)
