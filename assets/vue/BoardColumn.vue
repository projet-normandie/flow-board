<template>
    <div class="board-column">
        <div class="board-column-header board-column-drag-handle d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-grip-vertical text-muted" style="cursor: grab;"></i>
                <span class="fw-semibold">{{ column.name }}</span>
            </div>
            <span class="badge bg-secondary">{{ localCards.length }}</span>
        </div>

        <draggable
            v-model="localCards"
            item-key="id"
            class="board-column-body"
            :group="{ name: 'cards' }"
            animation="150"
            ghost-class="sortable-ghost"
            @change="onCardsChange"
        >
            <template #item="{ element: card }">
                <BoardCard :card="card" />
            </template>
        </draggable>

        <div v-if="canAddCard" class="board-column-footer">
            <button
                type="button"
                class="btn btn-sm btn-link text-muted w-100"
                @click="openAjaxModal(column.newCardUrl)"
            >
                <i class="bi bi-plus-lg me-1"></i>Ajouter une carte
            </button>
        </div>
    </div>
</template>

<script>
import { defineComponent, ref } from 'vue';
import draggable from 'vuedraggable';
import BoardCard from './BoardCard.vue';

export default defineComponent({
    name: 'BoardColumn',

    components: { draggable, BoardCard },

    props: {
        column: { type: Object, required: true },
        canAddCard: { type: Boolean, default: false },
    },

    setup(props) {
        const localCards = ref([...props.column.cards]);

        async function moveCard(card, columnId, position) {
            try {
                await fetch(card.moveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ columnId, position }),
                });
            } catch (error) {
                console.error('Error moving card:', error);
            }
        }

        function onCardsChange(event) {
            if (event.added) {
                const card = event.added.element;
                const position = (event.added.newIndex + 1) * 1000;
                moveCard(card, props.column.id, position);
            } else if (event.moved) {
                const card = event.moved.element;
                const position = (event.moved.newIndex + 1) * 1000;
                moveCard(card, props.column.id, position);
            }
        }

        const openAjaxModal = (url) => window.openAjaxModal(url);

        return { localCards, onCardsChange, openAjaxModal };
    },
});
</script>
