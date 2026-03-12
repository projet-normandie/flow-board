<template>
    <draggable
        v-model="localColumns"
        item-key="id"
        class="board-container"
        handle=".board-column-drag-handle"
        animation="150"
        ghost-class="sortable-ghost"
        @end="onColumnDragEnd"
    >
        <template #item="{ element: column }">
            <BoardColumn
                :column="column"
                :can-add-card="canAddCard"
            />
        </template>
    </draggable>
</template>

<script>
import { defineComponent, ref } from 'vue';
import draggable from 'vuedraggable';
import BoardColumn from './BoardColumn.vue';

export default defineComponent({
    name: 'BoardApp',

    components: { draggable, BoardColumn },

    props: {
        initialColumns: { type: Array, required: true },
        canAddCard: { type: Boolean, default: false },
    },

    setup(props) {
        const localColumns = ref([...props.initialColumns]);

        async function onColumnDragEnd(event) {
            const column = localColumns.value[event.newIndex];
            const position = (event.newIndex + 1) * 1000;

            try {
                await fetch(column.moveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ position }),
                });
            } catch (error) {
                console.error('Error moving column:', error);
            }
        }

        return { localColumns, onColumnDragEnd };
    },
});
</script>
