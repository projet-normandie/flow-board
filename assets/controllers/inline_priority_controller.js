import { Controller } from '@hotwired/stimulus';

const PRIORITY_COLORS = {
    low: '#6b7280',
    medium: '#3b82f6',
    high: '#f97316',
    critical: '#ef4444',
};

export default class extends Controller {
    static targets = ['badge'];
    static values = {
        url: String,
        csrf: String,
        current: String,
        labels: Object,
    };

    connect() {
        this.dropdown = null;
        this.outsideClickHandler = null;
        this.badgeTarget.addEventListener('click', (e) => {
            e.stopPropagation();
            this.dropdown ? this.closeDropdown() : this.openDropdown();
        });
    }

    openDropdown() {
        this.dropdown = document.createElement('div');
        this.dropdown.className = 'priority-dropdown';

        // "None" option
        this.dropdown.appendChild(this.createBtn('', '—'));

        // Priority options in order
        ['low', 'medium', 'high', 'critical'].forEach((value) => {
            this.dropdown.appendChild(this.createBtn(value, this.labelsValue[value] ?? value, PRIORITY_COLORS[value]));
        });

        this.element.appendChild(this.dropdown);

        this.outsideClickHandler = () => this.closeDropdown();
        setTimeout(() => document.addEventListener('click', this.outsideClickHandler), 0);
    }

    createBtn(value, label, color = null) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'priority-dropdown-btn';
        btn.textContent = label;

        const isActive = value === this.currentValue;

        if (color) {
            btn.style.borderColor = color;
            btn.style.color = isActive ? '#fff' : color;
            btn.style.backgroundColor = isActive ? color : 'transparent';
        } else if (isActive) {
            btn.classList.add('active');
        }

        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            this.selectPriority(value);
        });

        return btn;
    }

    async selectPriority(value) {
        this.closeDropdown();

        if (value === this.currentValue) return;

        const formData = new FormData();
        formData.append('priority', value);
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
                this.currentValue = value;
                this.updateBadge(value);
            }
        } catch (e) {
            console.error('Failed to save priority', e);
        }
    }

    updateBadge(value) {
        const badge = this.badgeTarget;
        badge.className = '';

        if (value) {
            badge.classList.add('badge', `card-priority-badge-${value}`);
            badge.textContent = this.labelsValue[value] ?? value;
        } else {
            badge.classList.add('text-muted');
            badge.textContent = '—';
        }
    }

    closeDropdown() {
        if (this.dropdown) {
            this.dropdown.remove();
            this.dropdown = null;
        }
        if (this.outsideClickHandler) {
            document.removeEventListener('click', this.outsideClickHandler);
            this.outsideClickHandler = null;
        }
    }

    disconnect() {
        this.closeDropdown();
    }
}
