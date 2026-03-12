import { Controller } from '@hotwired/stimulus';

const PRIORITY_COLORS = {
    low: '#6b7280',
    medium: '#3b82f6',
    high: '#f97316',
    critical: '#ef4444',
};

export default class extends Controller {
    connect() {
        this.selectEl = this.element;
        this.selectEl.style.display = 'none';

        this.buttonGroup = document.createElement('div');
        this.buttonGroup.className = 'd-flex flex-wrap gap-2';

        Array.from(this.selectEl.options).forEach((option) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-sm priority-selector-btn';
            btn.textContent = option.value === '' ? '—' : option.text;
            btn.dataset.value = option.value;

            const color = PRIORITY_COLORS[option.value] ?? null;
            if (color) {
                btn.dataset.color = color;
            }

            btn.addEventListener('click', () => {
                this.selectEl.value = option.value;
                this.updateButtons();
            });

            this.buttonGroup.appendChild(btn);
        });

        this.selectEl.parentNode.insertBefore(this.buttonGroup, this.selectEl.nextSibling);
        this.updateButtons();
    }

    updateButtons() {
        const current = this.selectEl.value;

        this.buttonGroup.querySelectorAll('button').forEach((btn) => {
            const active = btn.dataset.value === current;
            const color = btn.dataset.color ?? null;

            btn.classList.toggle('active', active);

            if (color) {
                btn.style.borderColor = color;
                btn.style.color = active ? '#fff' : color;
                btn.style.backgroundColor = active ? color : 'transparent';
            } else {
                // "Aucune" button
                btn.style.borderColor = '';
                btn.style.color = '';
                btn.style.backgroundColor = '';
                btn.classList.toggle('btn-outline-secondary', !active);
                btn.classList.toggle('btn-secondary', active);
            }
        });
    }

    disconnect() {
        if (this.buttonGroup) {
            this.buttonGroup.remove();
        }
        this.selectEl.style.display = '';
    }
}
