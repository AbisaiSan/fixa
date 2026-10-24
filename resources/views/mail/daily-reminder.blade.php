<x-mail::message>
# Oi, {{ $name }}

Seu plano de hoje está pronto: {{ $goal }} minutos, tudo a partir das suas aulas.

<x-mail::button :url="$todayUrl">
Começar
</x-mail::button>

Sem tempo hoje? O modo corrida leva 5 minutos e também conta para a sequência.

<x-mail::subcopy>
Você recebe este lembrete porque o ativou em Configurações. [Não quero mais receber]({{ $unsubscribeUrl }})
</x-mail::subcopy>
</x-mail::message>
