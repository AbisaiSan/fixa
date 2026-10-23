<script setup lang="ts">
import { Form, Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ManageTwoFactor from '@/components/ManageTwoFactor.vue';
import Banner from '@/components/fixa/Banner.vue';
import Button from '@/components/fixa/Button.vue';
import Card from '@/components/fixa/Card.vue';
import Icon from '@/components/fixa/Icon.vue';
import PasswordField from '@/components/fixa/PasswordField.vue';
import Select from '@/components/fixa/Select.vue';
import Switch from '@/components/fixa/Switch.vue';
import TextField from '@/components/fixa/TextField.vue';
import Toast from '@/components/fixa/Toast.vue';
import { useAppearance } from '@/composables/useAppearance';
import type { Appearance } from '@/composables/useAppearance';
import { languageName, t } from '@/i18n';
import { ptBR } from '@/i18n/pt-BR';
import { focusFirstError } from '@/lib/forms';
import { logout } from '@/routes';

type Preferences = {
    theme: Appearance;
    notify_daily_reminder: boolean;
    notify_weekly_summary: boolean;
    notify_streak: boolean;
};

const props = defineProps<{
    study: {
        daily_goal_minutes: number;
        target_language: string;
        timezone: string;
        level: string | null;
    };
    goalRange: { min: number; max: number; step: number };
    languages: { available: string[]; upcoming: string[] };
    timezones: string[];
    preferences: Preferences;
    twoFactor: {
        enabled: boolean;
        requiresConfirmation: boolean;
        required: boolean;
    };
    passwordRules: string;
    status?: string;
}>();

const page = usePage();
const user = computed(() => page.props.auth.user);
const initials = computed(() =>
    user.value.name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join(''),
);

const statusMessage = computed(() => {
    const messages: Record<string, string> = ptBR.settings.saved;

    return props.status ? messages[props.status] : undefined;
});

// Estudo
const goal = ref(props.study.daily_goal_minutes);
const language = ref(props.study.target_language);
const timezone = ref(props.study.timezone);
const languageOptions = computed(() => [
    ...props.languages.available.map((code) => ({
        value: code,
        label: capitalize(languageName(code)),
    })),
    ...props.languages.upcoming.map((code) => ({
        value: code,
        label: t('settings.upcoming', { name: capitalize(languageName(code)) }),
        disabled: true,
    })),
]);
const timezoneOptions = computed(() =>
    props.timezones.map((zone) => ({
        value: zone,
        label: zone.replace('_', ' '),
    })),
);

function capitalize(text: string): string {
    return text.charAt(0).toUpperCase() + text.slice(1);
}

// Preferências da conta: salvam na hora, como no protótipo.
const preferences = ref<Preferences>({ ...props.preferences });
const { updateAppearance } = useAppearance();

function savePreferences(changes: Partial<Preferences>): void {
    preferences.value = { ...preferences.value, ...changes };

    if (changes.theme) {
        updateAppearance(changes.theme);
    }

    router.patch('/configuracoes/preferencias', preferences.value, {
        preserveScroll: true,
    });
}

const themes: { value: Appearance; label: string; icon: string }[] = [
    { value: 'light', label: t('settings.themeLight'), icon: 'light_mode' },
    { value: 'dark', label: t('settings.themeDark'), icon: 'dark_mode' },
    { value: 'system', label: t('settings.themeSystem'), icon: 'contrast' },
];
</script>

<template>
    <Head :title="t('settings.title')" />

    <h1 class="m-0 type-display-l">{{ t('settings.title') }}</h1>

    <Toast v-if="statusMessage">{{ statusMessage }}</Toast>

    <div class="flex items-center gap-3.5">
        <span
            aria-hidden="true"
            class="grid size-14 place-items-center rounded-full bg-prit text-lg font-extrabold text-prid"
            >{{ initials }}</span
        >
        <div>
            <p class="m-0 font-extrabold">{{ user.name }}</p>
            <p class="m-0 text-sm text-ink2">{{ user.email }}</p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <Card
            as="section"
            aria-labelledby="settings-study"
            class="flex flex-col gap-5"
        >
            <h2 id="settings-study" class="m-0 type-subtitle">
                {{ t('settings.study') }}
            </h2>
            <Form
                action="/configuracoes/estudo"
                method="patch"
                :options="{ preserveScroll: true }"
                v-slot="{ errors, processing }"
                class="flex flex-col gap-5"
                @error="focusFirstError()"
            >
                <div>
                    <p id="goal-label" class="m-0 text-sm font-bold">
                        {{ t('settings.goal') }}
                    </p>
                    <p id="goal-hint" class="m-0 mb-2 text-[13.5px] text-ink2">
                        {{
                            t('settings.goalHint', {
                                min: goalRange.min,
                                max: goalRange.max,
                            })
                        }}
                    </p>
                    <div
                        class="flex items-center gap-3"
                        role="group"
                        aria-labelledby="goal-label"
                        aria-describedby="goal-hint"
                    >
                        <Button
                            variant="secondary"
                            :disabled="goal <= goalRange.min"
                            @click="
                                goal = Math.max(
                                    goalRange.min,
                                    goal - goalRange.step,
                                )
                            "
                        >
                            <Icon name="remove" />
                            <span class="sr-only">{{
                                t('settings.goalLess')
                            }}</span>
                        </Button>
                        <output
                            class="min-w-24 text-center font-display text-2xl"
                            aria-live="polite"
                        >
                            {{ t('settings.goalValue', { minutes: goal }) }}
                        </output>
                        <Button
                            variant="secondary"
                            :disabled="goal >= goalRange.max"
                            @click="
                                goal = Math.min(
                                    goalRange.max,
                                    goal + goalRange.step,
                                )
                            "
                        >
                            <Icon name="add" />
                            <span class="sr-only">{{
                                t('settings.goalMore')
                            }}</span>
                        </Button>
                    </div>
                    <input
                        type="hidden"
                        name="daily_goal_minutes"
                        :value="goal"
                    />
                    <p
                        v-if="errors.daily_goal_minutes"
                        class="m-0 mt-1.5 text-[13.5px] font-semibold text-errd"
                    >
                        {{ errors.daily_goal_minutes }}
                    </p>
                </div>

                <div>
                    <Select
                        v-model="language"
                        name="target_language"
                        :label="t('settings.language')"
                        :options="languageOptions"
                        :error="errors.target_language"
                    />
                    <p class="m-0 mt-1.5 text-[13.5px] text-ink2">
                        {{ t('settings.languageHint') }}
                    </p>
                </div>

                <div>
                    <Select
                        v-model="timezone"
                        name="timezone"
                        :label="t('settings.timezone')"
                        :options="timezoneOptions"
                        :error="errors.timezone"
                    />
                    <p class="m-0 mt-1.5 text-[13.5px] text-ink2">
                        {{ t('settings.timezoneHint') }}
                    </p>
                </div>

                <div>
                    <p class="m-0 text-sm font-bold">
                        {{ t('settings.level') }}
                    </p>
                    <p class="m-0 text-[13.5px] text-ink2">
                        {{ study.level ?? t('settings.levelUnknown') }}
                    </p>
                </div>

                <Button
                    type="submit"
                    :loading="processing"
                    class="self-start"
                    >{{ t('settings.save') }}</Button
                >
            </Form>
        </Card>

        <Card
            as="section"
            aria-labelledby="settings-notifications"
            class="flex flex-col gap-4"
        >
            <h2 id="settings-notifications" class="m-0 type-subtitle">
                {{ t('settings.notifications') }}
            </h2>
            <div>
                <Switch
                    :model-value="preferences.notify_daily_reminder"
                    :label="t('settings.dailyReminder')"
                    @update:model-value="
                        savePreferences({ notify_daily_reminder: $event })
                    "
                />
                <p class="m-0 text-[13.5px] text-ink2">
                    {{ t('settings.dailyReminderHint') }}
                </p>
            </div>
            <div>
                <Switch
                    :model-value="preferences.notify_weekly_summary"
                    :label="t('settings.weeklySummary')"
                    @update:model-value="
                        savePreferences({ notify_weekly_summary: $event })
                    "
                />
                <p class="m-0 text-[13.5px] text-ink2">
                    {{ t('settings.weeklySummaryHint') }}
                </p>
            </div>
            <div>
                <Switch
                    :model-value="preferences.notify_streak"
                    :label="t('settings.streakNotice')"
                    @update:model-value="
                        savePreferences({ notify_streak: $event })
                    "
                />
                <p class="m-0 text-[13.5px] text-ink2">
                    {{ t('settings.streakNoticeHint') }}
                </p>
            </div>

            <div
                id="seguranca"
                class="flex flex-col gap-2 border-t border-line pt-4"
            >
                <h3 class="m-0 font-bold">{{ t('settings.twoFactor') }}</h3>
                <p class="m-0 text-[13.5px] text-ink2">
                    {{ t('settings.twoFactorHint') }}
                </p>
                <Banner
                    v-if="twoFactor.required && !twoFactor.enabled"
                    tone="warning"
                    icon="shield_lock"
                    :title="t('settings.twoFactorRequired')"
                />
                <ManageTwoFactor
                    :can-manage-two-factor="true"
                    :requires-confirmation="twoFactor.requiresConfirmation"
                    :two-factor-enabled="twoFactor.enabled"
                />
            </div>
        </Card>

        <Card
            as="section"
            aria-labelledby="settings-theme"
            class="flex flex-col gap-4"
        >
            <h2 id="settings-theme" class="m-0 type-subtitle">
                {{ t('settings.theme') }}
            </h2>
            <div
                role="radiogroup"
                aria-labelledby="settings-theme"
                class="flex flex-wrap gap-2 rounded-m bg-sf2 p-1"
            >
                <button
                    v-for="theme in themes"
                    :key="theme.value"
                    type="button"
                    role="radio"
                    :aria-checked="preferences.theme === theme.value"
                    class="flex min-h-11 flex-1 items-center justify-center gap-1.5 rounded-[11px] px-3.5 font-bold"
                    :class="
                        preferences.theme === theme.value
                            ? 'bg-sf text-ink shadow-sh1'
                            : 'text-ink2'
                    "
                    @click="savePreferences({ theme: theme.value })"
                >
                    <Icon :name="theme.icon" :size="19" />{{ theme.label }}
                </button>
            </div>
        </Card>

        <Card
            as="section"
            aria-labelledby="settings-account"
            class="flex flex-col gap-4"
        >
            <h2 id="settings-account" class="m-0 type-subtitle">
                {{ t('settings.account') }}
            </h2>
            <Form
                action="/configuracoes/perfil"
                method="patch"
                :options="{ preserveScroll: true }"
                v-slot="{ errors, processing }"
                class="flex flex-col gap-4"
                @error="focusFirstError()"
            >
                <TextField
                    :label="t('settings.name')"
                    name="name"
                    :model-value="user.name"
                    autocomplete="name"
                    required
                    :error="errors.name"
                />
                <TextField
                    :label="t('auth.email')"
                    name="email"
                    type="email"
                    :model-value="user.email"
                    autocomplete="email"
                    required
                    :error="errors.email"
                />
                <Button
                    type="submit"
                    variant="secondary"
                    :loading="processing"
                    class="self-start"
                    >{{ t('settings.save') }}</Button
                >
            </Form>
        </Card>

        <Card
            as="section"
            aria-labelledby="settings-password"
            class="flex flex-col gap-4"
        >
            <h2 id="settings-password" class="m-0 type-subtitle">
                {{ t('settings.password') }}
            </h2>
            <Form
                action="/configuracoes/senha"
                method="put"
                :options="{ preserveScroll: true }"
                reset-on-success
                v-slot="{ errors, processing }"
                class="flex flex-col gap-4"
                @error="focusFirstError()"
            >
                <PasswordField
                    :label="t('settings.currentPassword')"
                    name="current_password"
                    required
                    :error="errors.current_password"
                />
                <PasswordField
                    :label="t('auth.newPassword')"
                    name="password"
                    autocomplete="new-password"
                    :passwordrules="passwordRules"
                    :hint="t('auth.passwordHint')"
                    required
                    :error="errors.password"
                />
                <PasswordField
                    :label="t('auth.passwordConfirmation')"
                    name="password_confirmation"
                    autocomplete="new-password"
                    required
                    :error="errors.password_confirmation"
                />
                <Button
                    type="submit"
                    variant="secondary"
                    :loading="processing"
                    class="self-start"
                    >{{ t('settings.changePassword') }}</Button
                >
            </Form>
        </Card>

        <Card
            as="section"
            aria-labelledby="settings-sessions"
            class="flex flex-col gap-4"
        >
            <h2 id="settings-sessions" class="m-0 type-subtitle">
                {{ t('settings.sessions') }}
            </h2>
            <p class="m-0 text-sm text-ink2">
                {{ t('settings.sessionsHint') }}
            </p>
            <Form
                action="/configuracoes/sessoes"
                method="delete"
                :options="{ preserveScroll: true }"
                reset-on-success
                v-slot="{ errors, processing }"
                class="flex flex-col gap-4"
                @error="focusFirstError()"
            >
                <PasswordField
                    :label="t('auth.password')"
                    name="password"
                    required
                    :error="errors.password"
                />
                <Button
                    type="submit"
                    variant="secondary"
                    icon="devices"
                    :loading="processing"
                    class="self-start"
                >
                    {{ t('settings.sessionsAction') }}
                </Button>
            </Form>
        </Card>

        <Card
            as="section"
            aria-labelledby="settings-privacy"
            class="flex flex-col gap-3"
        >
            <h2 id="settings-privacy" class="m-0 type-subtitle">
                {{ t('settings.privacy') }}
            </h2>
            <p class="m-0 text-sm text-ink2">{{ t('settings.privacySoon') }}</p>
            <div class="flex flex-wrap gap-2">
                <Link
                    href="/termos"
                    class="flex min-h-11 items-center font-bold text-prid"
                    >{{ t('legal.terms') }}</Link
                >
                <Link
                    href="/privacidade"
                    class="flex min-h-11 items-center font-bold text-prid"
                    >{{ t('legal.privacy') }}</Link
                >
            </div>
            <Link
                :href="logout()"
                method="post"
                as="button"
                class="flex min-h-11 items-center gap-2 self-start font-bold text-errd"
            >
                <Icon name="logout" />{{ t('settings.logout') }}
            </Link>
        </Card>
    </div>
</template>
