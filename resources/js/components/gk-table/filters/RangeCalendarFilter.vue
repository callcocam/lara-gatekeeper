<template>
    <Popover>
        <PopoverTrigger as-child>
            <Button variant="outline" size="sm" class="h-8 border-dashed">
                <CalendarIcon class="mr-2 h-4 w-4" />
                <template v-if="value.start">
                    <template v-if="value.end">
                        {{ df.format(value.start.toDate(getLocalTimeZone())) }} - {{
                            df.format(value.end.toDate(getLocalTimeZone())) }}
                    </template>

                    <template v-else>
                        {{ df.format(value.start.toDate(getLocalTimeZone())) }}
                    </template>
                </template>
                <template v-else>
                    {{ filter.label }}
                </template>
            </Button>
        </PopoverTrigger>
        <PopoverContent align="start" class="w-auto p-0">
            <RangeCalendar v-model="value" initial-focus :number-of-months="2"
                @update:start-value="(startDate: any) => value.start = startDate"
                @update:end-value="(endDate: any) => value.end = endDate" locale="pt-BR" />
            <div class="p-3 border-t">
                <Button variant="outline" size="sm" @click="clearDates" class="w-full">
                    Limpar datas
                </Button>
            </div>
        </PopoverContent>
    </Popover>
</template>
<script lang="ts" setup>
import type { DateRange } from "reka-ui"
import type { Ref } from "vue"
import {
    CalendarDate,
    DateFormatter,
    getLocalTimeZone,
} from "@internationalized/date"
import { ref, watch } from 'vue';
import { cn } from '../../../lib/utils';
import { Popover, PopoverTrigger, PopoverContent } from '@/components/ui/popover'
import { CalendarIcon } from "lucide-vue-next"
import { Button } from '@/components/ui/button';
import { RangeCalendar } from '@/components/ui/range-calendar';

interface Option {
    start: string;
    end: string;
}

interface FilterProps {
    modelValue: any;
    queryParams: Record<string, any>;
    filter: {
        id: string;
        label: string;
        name: string;
        component: any;
        options: Option;
    };
}
const props = defineProps<FilterProps>();

const df = new DateFormatter("pt-BR", {
    dateStyle: "medium",
})

 
const emit = defineEmits(['update:modelValue']); 

const initialStart: [number, number, number] | null = props.filter.options.start
    ? (props.filter.options.start.split('-').map((part) => parseInt(part, 10)) as [number, number, number])
    : null;

const initialEnd: [number, number, number] | null = props.filter.options.end
    ? (props.filter.options.end.split('-').map((part) => parseInt(part, 10)) as [number, number, number])
    : null;


const value = ref({
    start: initialStart ? new CalendarDate(...initialStart) : undefined,
    end: initialEnd ? new CalendarDate(...initialEnd) : undefined,
}) as Ref<DateRange>

const clearDates = () => {
    value.value = {
        start: undefined,
        end: undefined,
    } as DateRange;
    emit('update:modelValue', undefined);
};

watch(value, (newValue) => {
    const startDate = newValue.start?.toDate(getLocalTimeZone()).toLocaleDateString().replace(/\//g, '-');
    const endDate = newValue.end?.toDate(getLocalTimeZone()).toLocaleDateString().replace(/\//g, '-');
    emit('update:modelValue',  { name: props.filter.name, value: JSON.stringify({ start: startDate, end: endDate }) });
});
</script>