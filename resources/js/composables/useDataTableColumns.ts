import { Button } from '@/components/ui/button';
import type { BackendColumnDef } from '@/types/tables';
import { Link } from '@inertiajs/vue3';
import type { ComputedRef, Ref } from 'vue';
import { computed, h, ref, resolveComponent } from 'vue';

interface UseDataTableColumnsProps {
    columns: ComputedRef<BackendColumnDef[]>; // Usa a interface importada
    routeNameBase: ComputedRef<string>;
    can?: ComputedRef<{ edit_resource?: boolean; delete_resource?: boolean } | undefined>;
    deleteItem: (item: any) => void; // Função para deletar
    initialSortKey: ComputedRef<string | null>; // Prop para sort inicial
    initialSortDir: ComputedRef<'asc' | 'desc' | null>; // Prop para direção inicial
    actions?: any[];
}

export function useDataTableColumns({
    columns: backendColumns,
    routeNameBase,
    can,
    deleteItem,
    initialSortKey,
    initialSortDir,
    actions,
}: UseDataTableColumnsProps) {
    const currentSort: Ref<{ key: string | null; dir: 'asc' | 'desc' | null }> = ref({
        key: initialSortKey.value,
        dir: initialSortDir.value,
    });

    // Helper function to create action buttons
    const createActionButton = ({
        permission,
        variant,
        isLink = false,
        routeSuffix = null,
        onClick = null,
        title,
        icon,
        row,
        routeNameBase,
        fullRouteName = null,
        isHtml = false,
    }: {
        permission?: string;
        variant: string;
        isLink?: boolean;
        routeSuffix?: string | null;
        onClick?: ((item: any) => void) | null;
        title: string;
        icon: any;
        row: any;
        routeNameBase: string;
        fullRouteName?: string | null;
        isHtml?: boolean;
    }) => {
        // Check if user has permission
        const hasPermission = permission === undefined ? true : (can?.value?.[permission as keyof typeof can.value] ?? true);

        if (!hasPermission) return null;

        const props: Record<string, any> = {
            variant,
            size: 'sm',
            title,
        };

        // Add link props or click handler
        if (isLink && fullRouteName) {
            (props as any).as = Link;
            const params = row.original;
            (props as any).href = route(fullRouteName, { ...params });
        } else if (isLink && !fullRouteName && routeSuffix) {
            (props as any).as = Link;
            (props as any).href = route(`${routeNameBase}.${routeSuffix}`, row.original.id);
            // verificar se um html
        }
        if (isHtml) {
            if (isHtml && fullRouteName) {
                const params = row.original;
                console.log('params', params, fullRouteName);
                (props as any).as = 'a';
                (props as any).target = '_blank';
                (props as any).rel = 'noopener noreferrer';
                (props as any).href = route(fullRouteName, params);
            } else {
                (props as any).as = Link;
                (props as any).href = route(`${routeNameBase}.${routeSuffix}`, row.original.id);
            }
        } else if (onClick) {
            props.onClick = () => onClick(row.original);
        }

        // Return the button with icon
        return h(Button, props, () => [h(resolveComponent(icon), { class: 'h-4 w-4' })]);
    };

    // A função toggleSorting será exposta para ser chamada externamente (pelo ServerSideDataTable)
    const toggleSorting = (columnKey: string | undefined) => {
        if (!columnKey) return;

        if (currentSort.value.key === columnKey) {
            if (currentSort.value.dir === 'asc') {
                currentSort.value = { key: columnKey, dir: 'desc' };
            } else {
                currentSort.value = { key: null, dir: null };
            }
        } else {
            currentSort.value = { key: columnKey, dir: 'asc' };
        }
        console.log('[useDataTableColumns] Sort changed by external toggle:', currentSort.value);
    };

    const tableColumns: ComputedRef<BackendColumnDef[]> = computed(() => { 
        const processedColumns = backendColumns.value.map((col) => {
            const newCol = { ...col };
            const columnKey = newCol.accessorKey || newCol.id;

            // Simplificar: Apenas passar a string do header original
            // A renderização do header com DataTableColumnHeader será feita no ServerSideDataTable
            if (newCol.id !== 'actions' && !newCol.html) {
                // O header original (string) é mantido.
                // O ServerSideDataTable usará isso e o `sortable` para renderizar.
                newCol.enableSorting = newCol.sortable ?? true; // Garantir que a propriedade exista
            }

            // Processar célula se não for a coluna de ações e não for HTML
            if (newCol.id !== 'actions' && !newCol.html && !newCol.cell) {
                newCol.cell = ({ row }) => h('span', row.getValue(columnKey || ''));
            }

            // Processar coluna de ações (lógica mantida)
            if (newCol.id === 'actions') {
                newCol.header = 'Ações';
                newCol.cell = ({ row }) => { 
                    const actionButtons = [] as any[];
                    // ].filter(button => button !== null) as any[];

                    // Adicionar botões extras se existirem
                    if (actions && actions.length > 0) {
                        actions.forEach((action) => {
                            // Verificar se a condição foi passada e é satisfeita
                            if (!action.condition) {
                                const extraButton = createActionButton({
                                    ...action,
                                    row,
                                });

                                actionButtons.push(extraButton);
                            }
                        });

                        actionButtons.push(
                            createActionButton({
                                permission: 'delete_resource',
                                variant: 'destructive',
                                onClick: deleteItem,
                                title: 'Excluir',
                                icon: 'Trash2',
                                row,
                                routeNameBase: routeNameBase.value,
                            }),
                        );
                    }

                    return h('div', { class: 'flex justify-end space-x-2' }, actionButtons);
                };

                newCol.enableSorting = false;
                newCol.enableHiding = false;
            }

            return newCol;
        });

        return processedColumns;
    });

    return {
        tableColumns, // Definições de coluna preparadas (sem renderização de header)
        currentSort, // Estado de ordenação atual
        toggleSorting, // Função para ser chamada pelo componente da tabela
    };
}
