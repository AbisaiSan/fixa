<script setup lang="ts">
const model = defineModel<boolean>({ default: false });
</script>

<template>
    <label
        class="flex min-h-11 cursor-pointer items-center gap-3 text-[14.5px]"
    >
        <input
            v-model="model"
            v-bind="$attrs"
            type="checkbox"
            class="m-0 size-5.5 shrink-0 accent-prid"
        />
        <span><slot /></span>
    </label>
</template>

<script lang="ts">
export default { inheritAttrs: false };
</script>
