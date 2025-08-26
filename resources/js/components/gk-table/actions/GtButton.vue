<template>
    <div>
        <Button @click="handleClick" v-bind="attributes(action)">
            <template v-if="action.icon && action.iconPosition === 'left'">
                <Icon :name="action.icon" />
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
import { ref } from 'vue';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { ActionProps } from '../../../types/field';
 
defineProps<ActionProps>();
const emit = defineEmits<{
    click: [];
}>();

const handleClick = () => {
    emit('click');
};

const attributes = (action: any) => {
    // Map your color or custom logic to allowed variants
    const allowedVariants = ['default', 'destructive', 'outline', 'secondary', 'ghost', 'link'];
    // Example: use 'default' if color is set, otherwise 'secondary'
    const variant = action.variant && allowedVariants.includes(action.variant) ? action.variant : 'default';

    if (variant) {
        return {
            variant,
            size: action.size ?? 'sm',
            class: cn('flex items-center gap-2', action?.class),
        }
    }

    return {
        variant,
        class: cn('flex items-center gap-2', action?.class),
        style: action?.style ?? { background: 'linear-gradient(to right, #1e293b, #a8ff3e)', color: 'white' }
    };
};
</script>