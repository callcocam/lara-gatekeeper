<template>
    <Popover>
        <PopoverTrigger asChild>
            <Button type="button" variant="outline" class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="5" height="5" x="3" y="3" rx="1" />
                    <rect width="5" height="5" x="16" y="3" rx="1" />
                    <rect width="5" height="5" x="3" y="16" rx="1" />
                    <path d="M21 16h-3a2 2 0 0 0-2 2v3" />
                    <path d="M21 21v.01" />
                    <path d="M12 7v3a2 2 0 0 1-2 2H7" />
                    <path d="M3 12h.01" />
                    <path d="M12 3h.01" />
                    <path d="M12 16v.01" />
                    <path d="M16 12h1" />
                    <path d="M21 12v.01" />
                    <path d="M12 21v-1" />
                </svg>
                QR-Code
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-auto">
            <div class="flex flex-col items-center gap-4">
                <!-- QR Code Image -->
                <div class="relative">
                    <img ref="qrImageRef" :src="qrCodeUrl" alt="QR Code" class="w-[200px] h-[200px]" />
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-wrap gap-2 w-full">
                    <button @click="downloadQRCode"
                        class="flex-1 flex items-center justify-center gap-2 px-3 py-2 text-sm bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors"
                        title="Baixar QR Code">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                            <polyline points="7 10 12 15 17 10" />
                            <line x1="12" y1="15" x2="12" y2="3" />
                        </svg>
                        <span class="sr-only"> Baixar</span>
                    </button>

                    <button @click="printQRCode"
                        class="flex-1 flex items-center justify-center gap-2 px-3 py-2 text-sm bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors"
                        title="Imprimir QR Code">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 6 2 18 2 18 9" />
                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
                            <rect width="12" height="8" x="6" y="14" />
                        </svg>
                        <span class="sr-only"> Imprimir</span>
                    </button>

                    <button @click="copyQRCode"
                        class="flex-1 flex items-center justify-center gap-2 px-3 py-2 text-sm bg-secondary text-secondary-foreground rounded-md hover:bg-secondary/80 transition-colors"
                        title="Copiar URL">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="14" height="14" x="8" y="8" rx="2" ry="2" />
                            <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" />
                        </svg>
                        <span class="sr-only"> Copiar</span>
                    </button>

                    <button @click="shareQRCode"
                        class="flex-1 flex items-center justify-center gap-2 px-3 py-2 text-sm bg-secondary text-secondary-foreground rounded-md hover:bg-secondary/80 transition-colors"
                        title="Compartilhar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="18" cy="5" r="3" />
                            <circle cx="6" cy="12" r="3" />
                            <circle cx="18" cy="19" r="3" />
                            <line x1="8.59" y1="13.51" x2="15.42" y2="17.49" />
                            <line x1="15.41" y1="6.51" x2="8.59" y2="10.49" />
                        </svg>
                        <span class="sr-only"> Compartilhar</span>
                    </button>
                </div>

                <!-- Feedback Message -->
                <transition enter-active-class="transition-opacity duration-200"
                    leave-active-class="transition-opacity duration-200" enter-from-class="opacity-0"
                    leave-to-class="opacity-0">
                    <p v-if="feedbackMessage" class="text-sm text-green-600 dark:text-green-400">
                        {{ feedbackMessage }}
                    </p>
                </transition>
            </div>
        </PopoverContent>
    </Popover>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Button } from '@/components/ui/button';

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

const qrImageRef = ref<HTMLImageElement | null>(null);
const feedbackMessage = ref('');

const qrCodeUrl = computed(() =>
    `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(props.action.url || '')}`
);

// Função para baixar o QR Code
const downloadQRCode = async () => {
    try {
        const response = await fetch(qrCodeUrl.value);
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `qrcode-${props.action.id}.png`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);

        showFeedback('QR Code baixado com sucesso!');
    } catch (error) {
        console.error('Erro ao baixar QR Code:', error);
        showFeedback('Erro ao baixar QR Code');
    }
};

// Função para imprimir o QR Code
const printQRCode = () => {
    try {
        const printWindow = window.open('', '_blank');
        if (printWindow) {
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Imprimir QR Code</title>
                    <style>
                        body {
                            margin: 0;
                            padding: 20px;
                            display: flex;
                            flex-direction: column;
                            align-items: center;
                            justify-content: center;
                            min-height: 100vh;
                            font-family: Arial, sans-serif;
                        }
                        .qr-container {
                            text-align: center;
                            page-break-inside: avoid;
                        }
                        img {
                            width: 300px;
                            height: 300px;
                            margin: 20px 0;
                        }
                        .url {
                            margin-top: 20px;
                            word-break: break-all;
                            font-size: 14px;
                            color: #333;
                        }
                        @media print {
                            body {
                                padding: 0;
                            }
                        }
                    </style>
                </head>
                <body>
                    <div class="qr-container">
                        <h2>QR Code</h2>
                        <img src="${qrCodeUrl.value}" alt="QR Code" />
                        <div class="url">${props.action.url || ''}</div>
                    </div>
                </body>
                </html>
            `);
            printWindow.document.close();

            // Aguardar o carregamento da imagem antes de imprimir
            printWindow.onload = () => {
                setTimeout(() => {
                    printWindow.print();
                    printWindow.close();
                }, 250);
            };

            showFeedback('Abrindo impressão...');
        } else {
            showFeedback('Não foi possível abrir a janela de impressão');
        }
    } catch (error) {
        console.error('Erro ao imprimir QR Code:', error);
        showFeedback('Erro ao imprimir');
    }
};

// Função para copiar o QR Code para a área de transferência
const copyQRCode = async () => {
    try {
        // Tentar copiar a URL diretamente (mais compatível)
        if (props.action.url && navigator.clipboard && navigator.clipboard.writeText) {
            await navigator.clipboard.writeText(props.action.url);
            showFeedback('URL copiada!');
        } else {
            showFeedback('Erro ao copiar');
        }
    } catch (error) {
        console.error('Erro ao copiar:', error);
        showFeedback('Erro ao copiar');
    }
};

// Função para compartilhar o QR Code
const shareQRCode = async () => {
    try {
        // Verificar se a API de compartilhamento está disponível
        if (navigator.share && props.action.url) {
            await navigator.share({
                title: 'QR Code',
                text: `Acesse: ${props.action.url}`,
                url: props.action.url
            });

            showFeedback('Compartilhado com sucesso!');
        } else {
            // Fallback: copiar URL
            if (props.action.url && navigator.clipboard && navigator.clipboard.writeText) {
                await navigator.clipboard.writeText(props.action.url);
                showFeedback('URL copiada para compartilhar!');
            } else {
                showFeedback('Compartilhamento não disponível');
            }
        }
    } catch (error) {
        // Usuário cancelou o compartilhamento ou ocorreu um erro
        if ((error as Error).name !== 'AbortError') {
            console.error('Erro ao compartilhar:', error);
            showFeedback('Erro ao compartilhar');
        }
    }
};

// Função para mostrar mensagem de feedback
const showFeedback = (message: string) => {
    feedbackMessage.value = message;
    setTimeout(() => {
        feedbackMessage.value = '';
    }, 2000);
};
</script>