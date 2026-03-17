import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['display', 'input', 'clear'];
    static values = {
        url: String,
        csrf: String,
        current: String,
        placeholder: String,
    };

    connect() {
        this.savedDate = this.currentValue; // 'Y-m-d' or ''

        this.displayTarget.addEventListener('click', () => this.openPicker());
        this.inputTarget.addEventListener('change', () => this.saveDateValue(this.inputTarget.value));
        this.inputTarget.addEventListener('keydown', (e) => { if (e.key === 'Escape') this.closePicker(); });
        this.inputTarget.addEventListener('blur', () => this.closePicker());

        if (this.hasClearTarget) {
            this.clearTarget.addEventListener('click', (e) => {
                e.stopPropagation();
                this.saveDateValue('');
            });
        }
    }

    openPicker() {
        this.displayTarget.style.display = 'none';
        if (this.hasClearTarget) this.clearTarget.style.display = 'none';
        this.inputTarget.style.display = 'inline-block';
        this.inputTarget.value = this.savedDate;
        this.inputTarget.focus();
        this.inputTarget.showPicker?.();
    }

    closePicker() {
        this.inputTarget.style.display = 'none';
        this.displayTarget.style.display = 'inline';
        if (this.hasClearTarget && this.savedDate) {
            this.clearTarget.style.display = 'inline';
        }
    }

    async saveDateValue(dateStr) {
        if (dateStr === this.savedDate) {
            this.closePicker();
            return;
        }

        const formData = new FormData();
        formData.append('dueDate', dateStr);
        formData.append('_token', this.csrfValue);

        try {
            const response = await fetch(this.urlValue, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!response.ok) throw new Error('Server error');

            const data = await response.json();
            if (data.success) {
                this.savedDate = dateStr;
                this.currentValue = dateStr;
                this.updateDisplay(dateStr);
            }
        } catch (e) {
            console.error('Failed to save due date', e);
        }

        this.closePicker();
    }

    updateDisplay(dateStr) {
        if (!dateStr) {
            this.displayTarget.className = 'date-editable card-description-placeholder';
            this.displayTarget.textContent = this.placeholderValue;
            if (this.hasClearTarget) this.clearTarget.style.display = 'none';
            return;
        }

        const date = new Date(dateStr + 'T00:00:00');
        const now = new Date();
        now.setHours(0, 0, 0, 0);
        const diffDays = Math.floor((date - now) / 86400000);

        this.displayTarget.className = 'date-editable';
        if (diffDays < 0) this.displayTarget.classList.add('text-danger', 'fw-bold');
        else if (diffDays < 3) this.displayTarget.classList.add('text-warning', 'fw-bold');

        this.displayTarget.textContent = this.formatDate(date);
        if (this.hasClearTarget) this.clearTarget.style.display = 'inline';
    }

    formatDate(date) {
        const d = String(date.getDate()).padStart(2, '0');
        const m = String(date.getMonth() + 1).padStart(2, '0');
        return `${d}/${m}/${date.getFullYear()}`;
    }
}
