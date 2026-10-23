# ADR 0002: Provedor de IA, extração de texto e armazenamento de dados

- Data: 23/10/2026
- Situação: aceita (o provedor final de produção sai do `ai:compare`, J2-B13)
- Tarefas: J2-B1, J2-B6, J2-B13

## Contexto

A Jornada 2 transforma o material da aula em revisão com IA. O material pode ser conteúdo protegido do professor, então importa para onde o texto vai (IAS-01, DAD-01). O desenvolvimento é numa máquina só (Apple M5, 24 GB), que não deve ficar ocupada rodando modelos. Foram avaliados Llama local, Llama hospedado, Gemini e Claude, além de CockroachDB e Pinecone.

## Decisão

**IA**

- Provedor inicial: **Gemini por API**, com `gemini-3.8-flash` para tarefas pesadas (revisão da aula) e `gemini-3.5-flash-lite` para as leves, ambos configuráveis por `.env`.
- Tudo passa pela interface própria `AiProvider` em `app/Domain/Ai`. Adaptadores: `Fake` (testes, sempre) e `Gemini`. Um adaptador compatível com a API da OpenAI fica previsto para testar Llama e outros modelos abertos depois, local (Ollama) ou hospedado (Groq, Together, OpenRouter), só trocando endereço, chave e modelo.
- Cliente HTTP próprio e fino sobre o `Http` do Laravel, em vez do Laravel AI SDK. O SDK é oficial e estável, mas traz agentes, ferramentas e conversas, superfície que a IAS-01 proíbe dar à IA. Para poucas chamadas estruturadas, o cliente próprio é menor, sem dependência nova e fácil de simular nos testes.
- Saída sempre estruturada: o esquema vai para o Gemini (`responseSchema`) e a resposta é validada de novo no servidor com regras do Laravel antes de salvar (IAS-02).

**Extração de texto**

- O MVP aceita **texto colado e PDF com texto**, lido pelo **poppler** (`pdftotext`, `pdfinfo`) no container, local, gratuito e sem IA. A aula real de teste (PDF do Google Docs, 12 páginas) rendeu 1.172 palavras pelo poppler.
- **Revisão em 23/10/2026:** foto do caderno e PDF escaneado saíram do MVP. Eram o maior risco de qualidade da jornada (leitura ruim de letra de mão gera revisão errada), custavam tokens de visão e mandavam imagens para fora do servidor. PDF sem texto selecionável é recusado com o motivo `pdf_without_text`. Se voltarem, o caminho previsto é o Gemini com visão; o Tesseract foi descartado por ser fraco com letra de mão.
- Antivírus: **ClamAV em container próprio** no Sail, com atualização automática das assinaturas, isolado do app.
- Extração na fila, com limite de tempo e de memória por processo (UPL-03).

**Dados**

- Banco: continua **PostgreSQL**. CockroachDB resolve escala distribuída em várias regiões, que o Fixa não tem, e é só parcialmente compatível com o PostgreSQL.
- Banco de vetores: **nenhum**. Cada geração usa uma aula, que cabe inteira no contexto do modelo. Se surgir busca por significado, usar a extensão **pgvector** no próprio PostgreSQL, sem enviar dados do aluno a mais um serviço externo.

## Consequências

- No plano gratuito, o Google pode usar o que é enviado para melhorar os modelos. Até o plano pago (decisão da J6), só material próprio vai para a IA.
- Trocar de provedor é configuração. O `ai:compare` grava saídas lado a lado para decidir com aulas reais.
- O Llama local continua possível sem mudar o produto, quando a máquina puder ser usada para isso.
