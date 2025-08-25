<script setup lang="ts">
import { Link, router } from "@inertiajs/vue3";
import { ChevronLeftIcon, ChevronRightIcon } from "lucide-vue-next";
import { cn } from "../../lib/utils";
import { Button } from "@/components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { ref, watch } from "vue";

const props = defineProps<{
    meta: {
        total: number;
        per_page: number;
        current_page: number;
        last_page: number;
        from: number;
        to: number;
        prev: string | null;
        next: string | null;
        links: Array<{ url: string; label: string; active: boolean }>;
        path: string;
        select_per_page?: number[];
        action: {
            [key: string]: any;
        }
    }
}>();

const perPage = ref<number | null>(props.meta.per_page);

const attributes = (action: any) => {
    // Map your color or custom logic to allowed variants
    const allowedVariants = ['default', 'destructive', 'outline', 'secondary', 'ghost', 'link'];
    // Example: use 'default' if color is set, otherwise 'secondary'
    const variant = action?.variant && allowedVariants.includes(action?.variant) ? action?.variant : 'outline';

    return {
        variant,
        size: action?.size ?? 'sm',
        class: cn('flex items-center', action?.class),
    }
};
const getLinks = () => {
    return props.meta.links.slice(1, -1);
}
watch(perPage, (newPerPage) => {
    const url = new URL(props.meta.path, window.location.origin);
    if (newPerPage) {
        url.searchParams.set('per_page', newPerPage.toString());
    }
    router.visit(url.toString(), { preserveState: true, replace: true });
});
</script>

<template>
    <div class="flex justify-between justify-items-center items-center w-full px-4 py-2 border-t">
        <div class="flex  text-sm text-muted-foreground">
            <template v-if="meta.total > 0">
                Mostrando {{ meta.from }} a {{ meta.to }}
                de {{ meta.total }} registro(s).
            </template>
            <template v-else>
                Nenhum registro encontrado.
            </template>
        </div>
        <div class="flex items-center justify-center space-x-2">
            <div>
                <Select :value="meta.per_page" class="w-24" v-model="perPage">
                    <SelectTrigger>
                        <SelectValue :placeholder="perPage?.toString()" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="perPage in meta.select_per_page" :key="perPage" :value="perPage">
                            {{ perPage }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <div class="flex items-center justify-center space-x-2">
                <Button :href="meta.prev" v-if="meta.prev" v-bind="attributes(meta.action)" :as="Link">
                    <ChevronLeftIcon />
                    <span class="sr-only">Anterior</span>
                </Button>
                <template v-for="(link, index) in getLinks()" :key="index">
                    <Button v-if="link.active" v-bind="attributes(meta.action)" variant="ghost">{{ link.label
                        }}</Button>
                    <Button v-else-if="!link.url" v-bind="attributes(meta.action)" variant="ghost">{{
                        link.label }}</Button>
                    <Button :href="link.url" v-else v-bind="attributes(meta.action)" :as="Link">{{ link.label
                        }}</Button>
                </template>
                <Button :href="meta.next" v-if="meta.next" v-bind="attributes(meta.action)" :as="Link">
                    <span class="sr-only">Próximo</span>
                    <ChevronRightIcon />
                </Button>
            </div>
        </div>
    </div>
</template>