# ADR 0004: Plano semanal

- Data: 25/10/2026
- Situação: aceita
- Tarefas: J5-B1 em diante

## Contexto

A PLA-01 manda gerar o plano toda segunda, e a PLA-02 define 3 blocos por dia, mas nenhuma das duas diz quantos minutos cada bloco leva, qual atividade da aula entra em cada dia nem o que fazer quando o aluno começa no meio da semana ou ainda não tem aula.

## Decisão

- A semana vai de **segunda a domingo no fuso da matrícula** (GAM-06), sempre com segunda fixa no código, porque o locale pt-BR do Carbon começa a semana no domingo.
- Os blocos dividem a meta diária na proporção **3, 4 e 3** (flashcards, aula, ponto fraco), como no protótipo (3, 4 e 3 minutos na meta de 10). A divisão é pelo maior resto, então a soma é sempre a meta.
- A atividade da aula **gira entre escrita, leitura e vocabulário** da aula mais recente pronta, pelo dia da semana, usando só as que já existem.
- Bloco sem conteúdo fica de fora e os outros dividem a meta: sem aula, o dia tem só flashcards; com erros abertos, o ponto fraco continua. Sem nada para estudar, os dias ficam vazios e a tela de Hoje mostra o estado vazio.
- Quem começa no meio da semana recebe só os dias que faltam.
- Flashcards não são "tarefa importante" para o remanejamento (PLA-03): a fila do dia seguinte já traz os vencidos.
- O plano guarda só tipo, minutos e a aula de origem, nunca conteúdo do aluno. O conteúdo de cada bloco é resolvido na hora de começar.
- O comando `fixa:plan-week` roda **de hora em hora** e gera o plano de quem ainda não tem na semana corrente do próprio fuso. O agendador roda no container pelo `schedule:work` do supervisord.

## Consequências

- Mudar a proporção ou o rodízio é mudar a regra pura `WeeklyPlanGenerator`, coberta por testes de unidade.
- O agendador só passa a rodar no Sail depois de reconstruir a imagem (`sail build --no-cache`).
