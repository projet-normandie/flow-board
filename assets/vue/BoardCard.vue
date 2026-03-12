<template>
    <div
        class="card card-item"
        :class="priorityClass"
        :data-card-id="card.id"
    >
        <div class="card-body p-2">
            <div class="d-flex justify-content-between align-items-start gap-1">
                <h6 class="card-title mb-1 flex-grow-1">
                    <a
                        href="#"
                        class="text-decoration-none text-body"
                        @click.prevent="openAjaxModal(card.showUrl)"
                    >{{ card.title }}</a>
                </h6>
                <button
                    v-if="card.canEdit"
                    type="button"
                    class="btn btn-sm btn-link text-muted p-0 flex-shrink-0"
                    @click.stop="openAjaxModal(card.editUrl)"
                >
                    <i class="bi bi-pencil"></i>
                </button>
            </div>

            <div v-if="card.labels.length > 0" class="mb-1 d-flex flex-wrap gap-1">
                <span
                    v-for="label in card.labels"
                    :key="label.id"
                    class="badge rounded-pill"
                    :style="{ backgroundColor: label.color, fontSize: '0.65em' }"
                >{{ label.name }}</span>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-2">
                <div class="d-flex align-items-center gap-2">
                    <span
                        v-if="card.priority"
                        class="badge"
                        :class="`card-priority-badge-${card.priority}`"
                        style="font-size: 0.6em;"
                    >{{ priorityLabel }}</span>
                    <small v-if="card.dueDate" class="due-date" :class="dueDateClass">
                        <i class="bi bi-calendar-event me-1"></i>{{ card.dueDate }}
                    </small>
                    <small v-if="card.commentsCount > 0" class="text-muted">
                        <i class="bi bi-chat-left-text me-1"></i>{{ card.commentsCount }}
                    </small>
                </div>

                <div v-if="card.assignees.length > 0" class="avatar-group">
                    <img
                        v-for="assignee in card.assignees"
                        :key="assignee.id"
                        class="avatar-circle"
                        :src="assignee.gravatar"
                        :alt="assignee.fullName"
                        :title="assignee.fullName"
                    >
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { defineComponent, computed } from 'vue';

const PRIORITY_LABELS = {
    low: 'Basse',
    medium: 'Moyenne',
    high: 'Haute',
    critical: 'Critique',
};

export default defineComponent({
    name: 'BoardCard',

    props: {
        card: { type: Object, required: true },
    },

    setup(props) {
        const priorityClass = computed(() =>
            `card-priority-${props.card.priority ?? 'none'}`
        );

        const priorityLabel = computed(() =>
            PRIORITY_LABELS[props.card.priority] ?? props.card.priority
        );

        const dueDateClass = computed(() => {
            const diff = props.card.dueDateDiffDays;
            if (diff === null) return '';
            if (diff < 0) return 'due-date-overdue';
            if (diff < 3) return 'due-date-soon';
            return '';
        });

        const openAjaxModal = (url) => window.openAjaxModal(url);

        return { priorityClass, priorityLabel, dueDateClass, openAjaxModal };
    },
});
</script>
