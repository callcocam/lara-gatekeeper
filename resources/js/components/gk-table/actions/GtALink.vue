<template>
    <div>
        <Button :href="action.url" v-bind="attributes(action)" as="a" >
            <template v-if="action.icon && action.iconPosition === 'left'">
                <Icon :name="action.icon" class="w-6 h-6" />
            </template>
            <span :class="cn({
                'sr-only': action.size == 'icon',
            })">{{ action.label }}</span>
            <template v-if="action.icon && action.iconPosition === 'right'">
                <Icon :name="action.icon" />
            </template>
        </Button>
    </div>
</template>

<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import { cn } from '../../../lib/utils';
import { Link } from '@inertiajs/vue3';

interface ActionProps {
    action: {
        id: string;
        label: string;
        icon?: string;
        color?: string;
        url?: string;
        iconPosition?: string;
        size: "default" | "sm" | "lg" | "icon" | null | undefined;
        variant?: "default" | "destructive" | "outline" | "secondary" | "ghost" | "link";
        confirm?: {
            title: string;
            description: string;
        };
    };
}

const props = defineProps<ActionProps>();


const attributes = (action: any) => {
    // Map your color or custom logic to allowed variants
    const allowedVariants = ['default', 'destructive', 'outline', 'secondary', 'ghost', 'link']; 
    // Example: use 'default' if color is set, otherwise 'secondary'
    const variant = action.variant && allowedVariants.includes(action.variant) ? action.variant : null;
    if (variant) {
        return {
            variant,
            size: action.size ?? 'sm',
            class: cn('flex items-center', action?.class),
        }
    }

    return {
        variant,
        size: action.size ?? 'sm',
        class: cn('flex items-center gap-1', action?.class),
        style: action?.style ?? { background: 'linear-gradient(to right, #1e293b, #a8ff3e)', color: 'white' }
    };
};

</script>