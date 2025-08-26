<template>
    <AlertDialog>
        <AlertDialogTrigger as-child class="p-0">
            <Button v-bind="attributes(action)">
                <Icon :name="action.icon" v-if="action.icon && action.iconPosition === 'left'" />
                <span :class="cn({
                    'sr-only': action.size == 'icon',
                })">{{ action.label }}</span>
                <Icon :name="action.icon" v-if="action.icon && action.iconPosition === 'right'" />
            </Button>
        </AlertDialogTrigger>
        <Teleport to="body">
            <Transition name="modal">
                <div>
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>
                                {{ action.confirm?.title || 'Ação Personalizada' }}
                            </AlertDialogTitle>
                            <AlertDialogDescription>
                                {{ action.confirm?.description || 'Esta ação não pode ser desfeita.' }}
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel>
                                <Icon :name="action.confirm?.cancelIcon" class="w-4 h-4 mr-1"
                                    v-if="action.confirm?.cancelIcon" />
                                {{ action.confirm?.cancelButtonText || 'Cancelar' }}
                            </AlertDialogCancel>
                            <AlertDialogAction as="button" v-bind="attributes(action)" @click="confirmAction">
                                <Icon :name="action.confirm?.confirmIcon" class="w-4 h-4 mr-1"
                                    v-if="action.confirm?.confirmIcon" />
                                <span>{{ action.confirm?.confirmButtonText || 'Continuar' }}</span>
                            </AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </div>
            </Transition>
        </Teleport>
    </AlertDialog>
</template>

<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/vue3';

import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog'
import { cn } from '../../../lib/utils';
import { ActionProps } from '../../../types/field';

const props = defineProps<ActionProps>(); 

 

const attributes = (action: any) => {
    // Map your color or custom logic to allowed variants
    const allowedVariants = ['default', 'destructive', 'outline', 'secondary', 'ghost', 'link'];
    // Example: use 'default' if color is set, otherwise 'secondary'
    const variant = action.variant && allowedVariants.includes(action.variant) ? action.variant : null;
    if (variant) {
        return {
            variant,
            class: cn('flex items-center p-0', action?.class),
            size: action.size ?? 'sm',
        }
    }

    return {
        variant,
        size: action.size ?? 'sm',
        class: cn('flex items-center  p-1', action?.class),
        style: action?.style ?? { background: 'linear-gradient(to right, #1e293b, #a8ff3e)', color: 'white' }
    };
};

const confirmAction = () => {
    if (props.action.url) {
        switch (props.action.method) {
            case 'POST':
                router.post(props.action.url, {}, {
                    preserveState: true,
                    replace: true,
                });
                break;
            case 'PUT':
                router.put(props.action.url, {}, {
                    preserveState: true,
                    replace: true,
                });
                break;
            case 'DELETE':
                router.delete(props.action.url, {
                    preserveState: true,
                    replace: true,
                });
                break;
            case 'GET':
            default:
                router.visit(props.action.url, {
                    preserveState: true,
                    replace: true,
                });
                break;
        }
    }
};
</script>