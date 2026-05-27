import * as bootstrap from 'bootstrap';

window.bootstrap = bootstrap;

const SEARCHABLE_SELECT_NAMES = new Set([
    'school_id',
    'grade_id',
    'course_id',
    'weather_station_id',
    'responsible_user_id',
    'physical_variable_id',
    'category_id',
    'variable_id',
    'student_id',
    'teacher_id',
    'user_id',
    'activity_id',
    'guide_id',
    'laboratory_guide_id',
    'sensor_id',
    'environmental_event_id',
    'environmental_date_id',
]);

const EXCLUDED_SELECT_NAMES = new Set([
    'per_page',
    'status',
    'is_active',
    'source_type',
    'entry_type',
    'data_type',
    'document_type',
    'role',
    'mode',
    'delimiter',
]);

function normalizeText(value) {
    return String(value || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim();
}

function selectBaseName(select) {
    const rawName = select.getAttribute('name') || select.id || '';

    return rawName.replace(/\[\]$/, '');
}

function shouldEnhanceSelect(select) {
    if (!(select instanceof HTMLSelectElement) || select.multiple) {
        return false;
    }

    if (select.dataset.searchableSelect === 'false') {
        return false;
    }

    if (select.matches('[data-searchable-select], .js-searchable-select')) {
        return true;
    }

    const baseName = selectBaseName(select);

    if (EXCLUDED_SELECT_NAMES.has(baseName)) {
        return false;
    }

    if (SEARCHABLE_SELECT_NAMES.has(baseName)) {
        return true;
    }

    return select.options.length > 12;
}

class EcodataSearchableSelect {
    constructor(select) {
        this.select = select;
        this.isOpen = false;

        this.container = document.createElement('div');
        this.container.className = 'ecodata-searchable-select';

        if (select.classList.contains('form-select-lg')) {
            this.container.classList.add('ecodata-searchable-select-lg');
        }

        this.toggle = document.createElement('button');
        this.toggle.type = 'button';
        this.toggle.className = 'ecodata-searchable-toggle';
        this.toggle.setAttribute('aria-haspopup', 'listbox');
        this.toggle.setAttribute('aria-expanded', 'false');

        this.valueLabel = document.createElement('span');
        this.valueLabel.className = 'ecodata-searchable-value';

        this.caret = document.createElement('span');
        this.caret.className = 'ecodata-searchable-caret';
        this.caret.setAttribute('aria-hidden', 'true');

        this.toggle.append(this.valueLabel, this.caret);

        this.dropdown = document.createElement('div');
        this.dropdown.className = 'ecodata-searchable-dropdown';

        this.searchInput = document.createElement('input');
        this.searchInput.type = 'text';
        this.searchInput.className = 'ecodata-searchable-input';
        this.searchInput.placeholder = select.dataset.searchPlaceholder || 'Buscar...';
        this.searchInput.autocomplete = 'off';

        this.list = document.createElement('div');
        this.list.className = 'ecodata-searchable-list';
        this.list.setAttribute('role', 'listbox');

        this.empty = document.createElement('div');
        this.empty.className = 'ecodata-searchable-empty';
        this.empty.textContent = select.dataset.emptyText || 'Sin resultados';

        this.dropdown.append(this.searchInput, this.list, this.empty);
        this.container.append(this.toggle, this.dropdown);

        select.after(this.container);
        select.classList.add('ecodata-searchable-native');
        select.dataset.ecodataSearchableInitialized = 'true';
        select.ecodataSearchableInstance = this;

        this.bindEvents();
        this.observeSelect();
        this.refresh();
    }

    bindEvents() {
        this.toggle.addEventListener('click', (event) => {
            event.preventDefault();

            if (this.select.disabled) {
                return;
            }

            this.isOpen ? this.close() : this.open();
        });

        this.searchInput.addEventListener('input', () => this.renderOptions());

        this.searchInput.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                event.preventDefault();
                this.close();
                this.toggle.focus();
            }
        });

        this.select.addEventListener('change', () => this.refresh());

        document.addEventListener('click', (event) => {
            if (!this.container.contains(event.target)) {
                this.close();
            }
        });
    }

    observeSelect() {
        this.observer = new MutationObserver(() => this.refresh());
        this.observer.observe(this.select, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['class', 'disabled'],
        });
    }

    placeholder() {
        const emptyOption = Array.from(this.select.options).find((option) => option.value === '');

        return emptyOption?.textContent?.trim() || this.select.dataset.placeholder || 'Selecciona una opcion';
    }

    selectedLabel() {
        const selectedOption = this.select.selectedOptions[0];

        if (!selectedOption || selectedOption.value === '') {
            return this.placeholder();
        }

        return selectedOption.textContent.trim();
    }

    refresh() {
        this.valueLabel.textContent = this.selectedLabel();
        this.toggle.disabled = this.select.disabled;
        this.toggle.classList.toggle('is-invalid', this.select.classList.contains('is-invalid'));
        this.renderOptions();
    }

    open() {
        this.isOpen = true;
        this.container.classList.add('is-open');
        this.toggle.setAttribute('aria-expanded', 'true');
        this.searchInput.value = '';
        this.renderOptions();
        window.setTimeout(() => this.searchInput.focus(), 0);
    }

    close() {
        this.isOpen = false;
        this.container.classList.remove('is-open');
        this.toggle.setAttribute('aria-expanded', 'false');
    }

    renderOptions() {
        const query = normalizeText(this.searchInput.value);
        const options = Array.from(this.select.options)
            .filter((option) => !option.disabled)
            .filter((option) => normalizeText(option.textContent).includes(query));

        this.list.innerHTML = '';

        options.forEach((option) => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'ecodata-searchable-option';
            item.textContent = option.textContent.trim();
            item.setAttribute('role', 'option');
            item.setAttribute('aria-selected', String(option.selected));

            if (option.selected) {
                item.classList.add('is-selected');
            }

            item.addEventListener('click', () => {
                this.select.value = option.value;
                this.select.dispatchEvent(new Event('change', { bubbles: true }));
                this.close();
                this.toggle.focus();
            });

            this.list.appendChild(item);
        });

        this.empty.classList.toggle('d-none', options.length > 0);
    }
}

function initSearchableSelects(root = document) {
    const selects = root instanceof HTMLSelectElement
        ? [root]
        : Array.from(root.querySelectorAll('select'));

    selects.forEach((select) => {
        if (select.dataset.ecodataSearchableInitialized === 'true' || !shouldEnhanceSelect(select)) {
            return;
        }

        new EcodataSearchableSelect(select);
    });
}

function observeDynamicSelects() {
    let queued = false;

    const observer = new MutationObserver((mutations) => {
        const shouldInit = mutations.some((mutation) => (
            Array.from(mutation.addedNodes).some((node) => {
                if (!(node instanceof HTMLElement)) {
                    return false;
                }

                return node.matches('select, option') || Boolean(node.querySelector('select'));
            })
        ));

        if (!shouldInit || queued) {
            return;
        }

        queued = true;

        window.requestAnimationFrame(() => {
            queued = false;
            initSearchableSelects();
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true,
    });
}

window.ecodataSearchableSelects = {
    init: initSearchableSelects,
    refresh(root = document) {
        initSearchableSelects(root);

        const selects = root instanceof HTMLSelectElement
            ? [root]
            : Array.from(root.querySelectorAll('select[data-ecodata-searchable-initialized="true"]'));

        selects.forEach((select) => {
            select.ecodataSearchableInstance?.refresh();
        });
    },
};

document.addEventListener('DOMContentLoaded', () => {
    initSearchableSelects();
    observeDynamicSelects();
});

document.addEventListener('shown.bs.modal', (event) => {
    initSearchableSelects(event.target);
});
