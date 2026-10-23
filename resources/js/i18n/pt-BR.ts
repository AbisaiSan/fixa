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
    auth: {
        brandTitle: 'Sua aula vira rotina.',
        brandBody:
            'Envie o material da aula e pratique 10 minutos por dia com revisão, flashcards e atividades feitas a partir dele.',
        brandPrivacy: 'Seus materiais são privados e só você tem acesso.',
        email: 'E-mail',
        password: 'Senha',
        showPassword: 'Mostrar senha',
        hidePassword: 'Esconder senha',
        passwordHint: 'Pelo menos 8 caracteres, com letras e números.',
        loginTitle: 'Entrar',
        loginBody: 'Bom te ver. Continue de onde parou.',
        loginErrorTitle: 'Não conseguimos entrar.',
        loginErrorBody:
            'Depois de 5 tentativas, pausamos o acesso por 1 minuto, por segurança.',
        remember: 'Manter conectado neste aparelho',
        forgot: 'Esqueci minha senha',
        loginAction: 'Entrar',
        newHere: 'Novo por aqui?',
        createAccount: 'Criar conta',
        registerTitle: 'Criar conta',
        registerBody: 'Leva menos de um minuto.',
        name: 'Como podemos te chamar?',
        passwordConfirmation: 'Repita a senha',
        haveAccount: 'Já tem conta?',
        verifyTitle: 'Confirme seu e-mail',
        verifyBody:
            'Enviamos um link de confirmação para o seu e-mail. Abra o link para começar a estudar.',
        verifySent: 'Enviamos um novo link. Confira também a caixa de spam.',
        verifyResend: 'Reenviar link',
        logout: 'Sair',
        recoverTitle: 'Recuperar senha',
        recoverBody:
            'Digite seu e-mail. Enviamos um link para você criar uma senha nova.',
        recoverAction: 'Enviar link',
        recoverSentTitle: 'Confira seu e-mail',
        recoverSentBody:
            'Se existir uma conta com esse e-mail, o link chega em instantes. Ele vale por 30 minutos e funciona uma única vez.',
        backToLogin: 'Voltar para o login',
        resetTitle: 'Criar senha nova',
        resetBody: 'Escolha uma senha que você não usa em outros sites.',
        newPassword: 'Senha nova',
        resetAction: 'Salvar e entrar',
        twoFactorTitle: 'Verificação em duas etapas',
        twoFactorBody:
            'Digite o código de 6 dígitos que aparece no seu app autenticador.',
        twoFactorCode: 'Código do app',
        recoveryTitle: 'Código de recuperação',
        recoveryBody:
            'Digite um dos códigos de recuperação que você guardou ao ativar a verificação.',
        recoveryCode: 'Código de recuperação',
        verifyAction: 'Verificar',
        useRecovery: 'Usar um código de recuperação',
        useApp: 'Usar o código do app',
        confirmTitle: 'Confirme sua senha',
        confirmBody:
            'Esta é uma área protegida. Digite sua senha para continuar.',
        confirmAction: 'Confirmar',
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
    state: {
        loading: 'Carregando',
        errorTitle: 'Não conseguimos carregar.',
        errorBody: 'Confira sua conexão e tente de novo.',
        retry: 'Tentar de novo',
        offlineTitle: 'Sem conexão.',
        offlineBody:
            'Assim que a internet voltar, tudo continua de onde parou.',
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
