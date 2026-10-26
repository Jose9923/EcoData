function ecodataDisableSubmitForm(form, loadingText = 'Procesando...') {
    if (!form || form.dataset.submitted === 'true') {
        return false;
    }

    form.dataset.submitted = 'true';

    form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(function (button) {
        button.disabled = true;

        if (button.tagName === 'BUTTON') {
            button.dataset.originalHtml = button.innerHTML;
            button.innerHTML = `
                <span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
                ${loadingText}
            `;
        } else {
            button.dataset.originalValue = button.value;
            button.value = loadingText;
        }
    });

    return true;
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form').forEach(function (form) {
        if (form.classList.contains('js-confirm-delete') || form.dataset.managedSubmit === 'true') {
            return;
        }

        form.addEventListener('submit', function (event) {
            if (form.dataset.submitted === 'true') {
                event.preventDefault();
                return false;
            }

            ecodataDisableSubmitForm(form, form.dataset.loadingText || 'Procesando...');
        });
    });

    document.querySelectorAll('.js-confirm-delete').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();

            if (form.dataset.submitted === 'true') {
                return false;
            }

            const title = form.dataset.title || '¿Confirmar eliminación?';
            const text = form.dataset.text || 'Esta acción no se puede deshacer.';
            const confirmButtonText = form.dataset.confirmButton || 'Sí, eliminar';

            Swal.fire({
                icon: 'warning',
                title: title,
                text: text,
                showCancelButton: true,
                confirmButtonText: confirmButtonText,
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc3545',
                cancelButtonColor: getComputedStyle(document.documentElement)
                    .getPropertyValue('--school-primary')
                    .trim() || '#22c55e',
                reverseButtons: true,
                customClass: {
                    popup: 'rounded-4',
                    confirmButton: 'rounded-4 px-4 fw-semibold',
                    cancelButton: 'rounded-4 px-4 fw-semibold'
                }
            }).then(function (result) {
                if (result.isConfirmed) {
                    if (ecodataDisableSubmitForm(form, 'Eliminando...')) {
                        form.submit();
                    }
                }
            });
        });
    });
});

window.ecodataDataTableLanguage = {
    decimal: ',',
    thousands: '.',
    processing: 'Procesando...',
    search: 'Buscar:',
    lengthMenu: 'Mostrar _MENU_ registros',
    info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
    infoEmpty: 'Mostrando 0 a 0 de 0 registros',
    infoFiltered: '(filtrado de _MAX_ registros totales)',
    loadingRecords: 'Cargando...',
    zeroRecords: 'No se encontraron resultados',
    emptyTable: 'No hay datos disponibles en la tabla',
    paginate: {
        first: 'Primero',
        previous: 'Anterior',
        next: 'Siguiente',
        last: 'Último'
    },
    aria: {
        sortAscending: ': activar para ordenar ascendente',
        sortDescending: ': activar para ordenar descendente'
    }
};

document.addEventListener('DOMContentLoaded', function () {
    if (!window.jQuery || !$.fn.DataTable) {
        return;
    }

    $('.js-ecodata-datatable').each(function () {
        const table = $(this);

        if ($.fn.DataTable.isDataTable(this)) {
            table.DataTable().destroy();
        }

        const emptyText = table.data('empty') || 'No hay datos disponibles en la tabla';
        const zeroText = table.data('zero') || window.ecodataDataTableLanguage.zeroRecords || 'No se encontraron resultados';

        const paging = table.data('paging') !== false && table.data('paging') !== 'false';
        const searching = table.data('searching') !== false && table.data('searching') !== 'false';
        const info = table.data('info') !== false && table.data('info') !== 'false';
        const pageLength = parseInt(table.data('pageLength') || table.data('page-length') || 10, 10);

        const columnDefs = [
            {
                targets: -1,
                orderable: false,
                searchable: false,
                responsivePriority: 1
            },
            {
                targets: 0,
                responsivePriority: 2
            }
        ];

        table.DataTable({
            responsive: false,
            autoWidth: false,
            scrollX: true,
            paging: paging,
            searching: searching,
            info: info,
            ordering: true,
            pageLength: pageLength,
            lengthMenu: [5, 10, 15, 25, 50],
            columnDefs: columnDefs,
            language: {
                ...window.ecodataDataTableLanguage,
                emptyTable: emptyText,
                zeroRecords: zeroText
            }
        });
    });
});

window.ecodataDisableSubmitForm = ecodataDisableSubmitForm;

document.addEventListener('DOMContentLoaded', () => {
    const messages = document.getElementById('ecodata-messages');
    if (!messages) return;
    let payload;
    try { payload = JSON.parse(messages.textContent); } catch { return; }
    if (payload.errors.length) {
        Swal.fire({icon: 'error', title: 'Revisa la información', text: payload.errors.join('\n'), confirmButtonText: 'Entendido'});
        return;
    }
    for (const [kind, title] of [['error','Error'],['warning','Advertencia'],['success','Correcto']]) {
        if (payload[kind]) {
            Swal.fire({icon:kind, title, text:payload[kind], confirmButtonText:'Entendido'});
            break;
        }
    }
});
