<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import Banner from '@/components/fixa/Banner.vue';
import BottomNav from '@/components/fixa/BottomNav.vue';
import Button from '@/components/fixa/Button.vue';
import Card from '@/components/fixa/Card.vue';
import Checkbox from '@/components/fixa/Checkbox.vue';
import EmptyState from '@/components/fixa/EmptyState.vue';
import FeedbackPanel from '@/components/fixa/FeedbackPanel.vue';
import Flashcard from '@/components/fixa/Flashcard.vue';
import OriginTag from '@/components/fixa/OriginTag.vue';
import RateBar from '@/components/fixa/RateBar.vue';
import ReportButton from '@/components/fixa/ReportButton.vue';
import Select from '@/components/fixa/Select.vue';
import SessionHeader from '@/components/fixa/SessionHeader.vue';
import Sheet from '@/components/fixa/Sheet.vue';
import SidebarNav from '@/components/fixa/SidebarNav.vue';
import Skeleton from '@/components/fixa/Skeleton.vue';
import StatusChip from '@/components/fixa/StatusChip.vue';
import type { StatusKind } from '@/components/fixa/StatusChip.vue';
import Switch from '@/components/fixa/Switch.vue';
import TargetText from '@/components/fixa/TargetText.vue';
import TextArea from '@/components/fixa/TextArea.vue';
import TextField from '@/components/fixa/TextField.vue';
import Toast from '@/components/fixa/Toast.vue';
import { t } from '@/i18n';

/**
 * Catálogo de componentes, só em desenvolvimento (J0-F7). Confira contra
 * docs/design/prototipo/Sistema de Design.dc.html nos dois temas.
 * Os textos de exemplo vêm do protótipo.
 */
const themes = ['light', 'dark'] as const;
const kinds: StatusKind[] = [
    'nova',
    'aprendendo',
    'dominada',
    'feito',
    'hoje',
    'protegido',
    'perdido',
    'total',
    'parcial',
    'erro',
];
const colors = [
    'bg',
    'sf',
    'sf2',
    'line',
    'ink',
    'ink2',
    'ink3',
    'pri',
    'prid',
    'prit',
    'amb',
    'ambd',
    'ambt',
    'ok',
    'okd',
    'okt',
    'err',
    'errd',
    'errt',
    'info',
    'infod',
    'infot',
    'focus',
];

const email = ref('marina.alves@email.com');
const password = ref('123');
const level = ref('a2');
const notes = ref('I have worked here since 2022.');
const declared = ref(true);
const reminder = ref(true);
const sheetOpen = ref(false);
const cardFlipped = ref(false);
</script>

<template>
    <Head :title="t('catalog.title')" />

    <div class="grid min-h-dvh grid-cols-1 xl:grid-cols-2">
        <section
            v-for="theme in themes"
            :key="theme"
            :data-theme="theme"
            :aria-labelledby="`catalog-${theme}`"
            class="flex flex-col gap-8 bg-bg p-6 text-ink lg:p-10"
        >
            <header>
                <p class="type-label text-ink2">{{ t('catalog.title') }}</p>
                <h1 :id="`catalog-${theme}`" class="m-0 type-display-l">
                    {{
                        theme === 'light'
                            ? t('catalog.light')
                            : t('catalog.dark')
                    }}
                </h1>
            </header>

            <Card>
                <h2 class="mt-0 mb-4 type-subtitle">Cores</h2>
                <ul class="grid grid-cols-3 gap-2 p-0 sm:grid-cols-4">
                    <li
                        v-for="color in colors"
                        :key="color"
                        class="flex list-none items-center gap-2 text-sm"
                    >
                        <span
                            class="size-6 rounded-xs border border-line"
                            :style="{ background: `var(--${color})` }"
                        />
                        <code>--{{ color }}</code>
                    </li>
                </ul>
            </Card>

            <Card tone="hero">
                <h2 class="mt-0 mb-4 type-subtitle">Tipografia</h2>
                <p class="m-0 type-display-xl">Sua aula vira rotina.</p>
                <p class="m-0 type-display-l">Boa tarde, Marina</p>
                <p class="m-0 type-display-num">10 de 10</p>
                <p class="m-0 type-title">Seu plano de hoje</p>
                <p class="m-0 type-subtitle">Conceitos principais</p>
                <p class="m-0 type-body">
                    Escolha o que mais se parece com você hoje.
                </p>
                <p class="m-0 text-ink2 type-body-s">
                    8 cartões · 6 revisões e 2 novos
                </p>
                <p class="m-0 type-label text-ink2">Meta do dia</p>
                <TargetText as="p" size="large" class="m-0"
                    >Have you ever managed a team?</TargetText
                >
                <TargetText as="p" class="m-0"
                    >They haven’t called me yet.</TargetText
                >
                <p class="m-0 text-ink2">Eles ainda não me ligaram.</p>
            </Card>

            <Card>
                <h2 class="mt-0 mb-4 type-subtitle">Button</h2>
                <div class="flex flex-col gap-3">
                    <Button icon="play_arrow" block>Começar</Button>
                    <Button variant="secondary" block>Voltar para Hoje</Button>
                    <div class="flex flex-wrap gap-2">
                        <Button variant="ghost" icon="calendar_month"
                            >Ver semana</Button
                        >
                        <Button variant="danger">Excluir</Button>
                        <Button disabled>Desativado</Button>
                        <Button loading>Salvando</Button>
                    </div>
                </div>
            </Card>

            <Card>
                <h2 class="mt-0 mb-4 type-subtitle">Campos</h2>
                <div class="flex flex-col gap-4">
                    <TextField v-model="email" label="E-mail" type="email" />
                    <TextField
                        v-model="password"
                        label="Senha"
                        type="password"
                        error="Use pelo menos 8 caracteres."
                    />
                    <TextArea
                        v-model="notes"
                        label="Conteúdo da aula"
                        hint="12 palavras · mínimo 50"
                        :rows="4"
                    />
                    <Select
                        v-model="level"
                        label="Nível"
                        :options="[
                            { value: 'a2', label: 'A2 · Básico' },
                            { value: 'b1', label: 'B1 · Intermediário' },
                        ]"
                    />
                    <Checkbox v-model="declared"
                        >Declaro que tenho direito de usar este
                        material</Checkbox
                    >
                    <Switch v-model="reminder" label="Lembrete diário" />
                </div>
            </Card>

            <Card>
                <h2 class="mt-0 mb-4 type-subtitle">StatusChip e OriginTag</h2>
                <div class="flex flex-wrap gap-1.5">
                    <StatusChip
                        v-for="kind in kinds"
                        :key="kind"
                        :kind="kind"
                    />
                    <OriginTag lesson-date="21/10" />
                    <OriginTag />
                </div>
            </Card>

            <Card>
                <h2 class="mt-0 mb-4 type-subtitle">Flashcard</h2>
                <div class="flex flex-col gap-4">
                    <SessionHeader :current="2" :total="8" />
                    <Flashcard
                        v-model:flipped="cardFlipped"
                        front="deadline"
                        back="prazo final"
                        example="We've never missed a deadline."
                        example-translation="Nunca perdemos um prazo."
                        part-of-speech="substantivo"
                    />
                    <RateBar :preview="[0, 1, 6, 35]" />
                </div>
            </Card>

            <Card>
                <h2 class="mt-0 mb-4 type-subtitle">FeedbackPanel</h2>
                <FeedbackPanel
                    verdict="partial"
                    :xp="6"
                    answer="I work here since 2021."
                    corrected="I have worked here since 2021."
                    natural="I've been working here since 2021."
                    explanation="Com since, a ação continua até agora: use have mais o particípio."
                />
                <div class="mt-4 flex flex-wrap gap-4">
                    <ReportButton />
                    <ReportButton reported />
                </div>
            </Card>

            <Card>
                <h2 class="mt-0 mb-4 type-subtitle">Toast e Banner</h2>
                <div class="flex flex-col gap-2.5">
                    <Toast>Revisão pronta. A aula entrou no plano.</Toast>
                    <Toast tone="info"
                        >Obrigado por avisar. O item saiu da rotina.</Toast
                    >
                    <Banner icon="wifi_off" title="Sem conexão."
                        >Flashcards baixados continuam funcionando.</Banner
                    >
                    <Banner
                        tone="warning"
                        icon="hourglass_bottom"
                        title="Correções de IA esgotadas hoje."
                        >Gabarito fixo continua liberado.</Banner
                    >
                    <Banner
                        tone="info"
                        icon="waving_hand"
                        title="Bom te ver de volta."
                        >Refizemos sua semana para caber no seu ritmo.</Banner
                    >
                    <Banner
                        tone="error"
                        icon="error"
                        title="Não conseguimos enviar."
                        >O que você preencheu continua aqui.</Banner
                    >
                </div>
            </Card>

            <Card>
                <h2 class="mt-0 mb-4 type-subtitle">
                    Skeleton, EmptyState e Sheet
                </h2>
                <Skeleton label="Gerando revisão" :lines="3" />
                <EmptyState title="Tudo em dia por hoje"
                    >Volte amanhã para a próxima revisão.</EmptyState
                >
                <Button variant="secondary" @click="sheetOpen = true"
                    >Abrir Sheet</Button
                >
            </Card>

            <Card>
                <h2 class="mt-0 mb-4 type-subtitle">Navegação</h2>
                <div class="overflow-hidden rounded-m border border-line">
                    <BottomNav />
                </div>
                <div
                    class="mt-4 h-130 overflow-hidden rounded-m border border-line"
                >
                    <SidebarNav />
                </div>
            </Card>
        </section>
    </div>

    <Sheet
        v-model:open="sheetOpen"
        title="Ver semana"
        description="Tarefas remanejadas para sábado e domingo."
    >
        <p class="m-0 text-ink2">Conteúdo da folha.</p>
    </Sheet>
</template>
