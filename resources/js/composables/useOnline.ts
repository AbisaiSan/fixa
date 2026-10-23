import { onBeforeUnmount, onMounted, ref } from 'vue';
import type { Ref } from 'vue';

/**
 * Estado da conexão do navegador, para o banner de offline (J1-F5).
 */
export function useOnline(): Ref<boolean> {
    const online = ref(
        typeof navigator === 'undefined' ? true : navigator.onLine,
    );
    const update = () => {
        online.value = navigator.onLine;
    };

    onMounted(() => {
        window.addEventListener('online', update);
        window.addEventListener('offline', update);
        update();
    });

    onBeforeUnmount(() => {
        window.removeEventListener('online', update);
        window.removeEventListener('offline', update);
    });

    return online;
}
