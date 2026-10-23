/**
 * Dados públicos do aluno compartilhados pelo Inertia (User::toPublicArray).
 */
export type User = {
    ulid: string;
    name: string;
    email: string;
    avatar?: string;
    email_verified: boolean;
    two_factor_enabled: boolean;
    is_admin: boolean;
};

export type Auth = {
    user: User;
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
