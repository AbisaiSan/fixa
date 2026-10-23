# ADR 0003: Cartões e palavras guardados sem criptografia

- Data: 24/10/2026
- Situação: aceita
- Tarefas: J3-B1, J3-B2, J3-B9

## Contexto

O texto extraído da aula e a revisão gerada ficam criptografados pela aplicação (DAD-03, ADR 0002). Os cartões (FLA-01) e as palavras (VOC-01) também nascem do material, mas a Jornada 3 precisa deles no banco para deduplicar a mesma palavra entre aulas (FLA-05) e para a busca sem acentos com `unaccent` e `pg_trgm` (J3-B9). Coluna criptografada não permite nem uma coisa nem outra.

## Decisão

- `cards` e `words` guardam frente, verso, termo, tradução e exemplos **em texto comum**, sempre filtrados pelo dono (`BelongsToUser`, ACE-04).
- Continuam criptografados o texto extraído, a revisão e os detalhes de "reportar erro", que podem trazer trechos longos do material ou texto livre do aluno.
- Cartões e palavras têm limite de tamanho (frente até 200 caracteres, demais campos até 300) e nunca guardam o material inteiro.
- Nada disso vai para logs, `ai_calls` ou auditoria (IAS-04).

## Consequências

- Busca, filtros e deduplicação rodam no PostgreSQL, sem trazer tudo para a aplicação.
- Um vazamento do banco expõe o vocabulário estudado, não o material das aulas. O risco é aceito para o MVP e revisto na J6 (DAD-03, backup criptografado).
