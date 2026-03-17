import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['progressBar', 'counter'];
    static values = { total: Number };

    async toggle(event) {
        const checkbox = event.target;
        const url = checkbox.dataset.url;
        const csrf = checkbox.dataset.csrf;
        const previousChecked = !checkbox.checked;

        const formData = new FormData();
        formData.append('_token', csrf);

        checkbox.disabled = true;
        try {
            const response = await fetch(url, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!response.ok) throw new Error('Server error');

            const data = await response.json();
            if (!data.success) throw new Error('Toggle failed');

            const label = checkbox.closest('.checklist-item-row').querySelector('.checklist-item-label');
            if (data.checked) {
                label.classList.add('text-decoration-line-through', 'text-muted');
            } else {
                label.classList.remove('text-decoration-line-through', 'text-muted');
            }

            this.updateProgress();
        } catch {
            checkbox.checked = previousChecked;
        } finally {
            checkbox.disabled = false;
        }
    }

    updateProgress() {
        const checkboxes = this.element.querySelectorAll('input[type="checkbox"]');
        const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
        const total = this.totalValue;

        if (this.hasProgressBarTarget) {
            this.progressBarTarget.style.width = total > 0 ? `${Math.round(checkedCount / total * 100)}%` : '0%';
        }
        if (this.hasCounterTarget) {
            this.counterTarget.textContent = `${checkedCount}/${total}`;
        }
    }
}
