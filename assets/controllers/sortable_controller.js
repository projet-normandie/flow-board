import { Controller } from '@hotwired/stimulus';
import Sortable from 'sortablejs';

export default class extends Controller {
    connect() {
        this.element.querySelectorAll('.sortable-list').forEach((list) => {
            Sortable.create(list, {
                group: 'cards',
                animation: 150,
                ghostClass: 'sortable-ghost',
                onEnd: (event) => {
                    this.onEnd(event);
                },
            });
        });
    }

    async onEnd(event) {
        const cardId = event.item.dataset.cardId;
        const columnId = event.to.dataset.sortableColumnId;
        const position = (event.newIndex + 1) * 1000;

        try {
            const response = await fetch(`/cards/${cardId}/move`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ columnId: parseInt(columnId), position }),
            });

            if (!response.ok) {
                console.error('Failed to move card');
            }
        } catch (error) {
            console.error('Error moving card:', error);
        }

        this.updateCounters();
    }

    updateCounters() {
        this.element.querySelectorAll('.board-column').forEach((column) => {
            const count = column.querySelectorAll('.sortable-list .card-item').length;
            const badge = column.querySelector('.board-column-header .badge');
            if (badge) {
                badge.textContent = count;
            }
        });
    }
}
