/**
 * Textos da interface em pt-BR (J0-F6). Nenhum texto de interface fica fixo
 * nos componentes: use t('chave') ou t('chave', { valor }).
 */
export const ptBR = {
    languages: {
        en: 'inglês',
        es: 'espanhol',
        fr: 'francês',
    },
    nav: {
        label: 'Navegação principal',
        today: 'Hoje',
        lessons: 'Aulas',
        practice: 'Praticar',
        vocabulary: 'Vocabulário',
        progress: 'Progresso',
        settings: 'Configurações',
        profile: 'Perfil e configurações',
        upload: 'Enviar aula',
        skipToContent: 'Pular para o conteúdo',
    },
    status: {
        nova: 'Nova',
        aprendendo: 'Aprendendo',
        dominada: 'Dominada',
        feito: 'Feito',
        hoje: 'Hoje',
        protegido: 'Protegido',
        perdido: 'Perdido',
        total: 'Correto',
        parcial: 'Parcial',
        erro: 'Erro',
    },
    origin: {
        lesson: 'Da aula de {date}',
        mine: 'Adicionada por mim',
    },
    legal: {
        version: 'Versão de {version}',
        draftTitle: 'Rascunho.',
        draftBody:
            'Este texto ainda será revisado por um advogado antes de qualquer uso por outras pessoas.',
        acceptTitle: 'Atualizamos nossos termos',
        acceptBody:
            'Para continuar estudando, leia e aceite a versão atual dos termos de uso e da política de privacidade.',
        acceptLabelStart: 'Li e aceito os',
        terms: 'termos de uso',
        and: 'e a',
        privacy: 'política de privacidade',
        acceptAction: 'Aceitar e continuar',
    },
    common: {
        loading: 'Carregando',
        close: 'Fechar',
    },
    catalog: {
        title: 'Catálogo de componentes',
        light: 'Claro',
        dark: 'Escuro',
    },
} as const;
