<script setup lang="ts">
defineProps<{
    image_url?: string | null;
}>();
</script>

<template>
    <div v-if="image_url" class="flex items-center justify-center space-x-2">
        <img 
            :src="image_url" 
            alt="Avatar Atual" 
            class="h-24 w-24 rounded-full object-cover border"
        />
        <!-- Opcional: Adicionar texto ou link para remover? -->
    </div>
    <div v-else class="text-sm text-muted-foreground italic">
        (Sem avatar)
    </div>
</template> 