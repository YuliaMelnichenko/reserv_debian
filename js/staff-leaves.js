let currentType = 'Отпуск';

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('btn_vacations').addEventListener('click', () => {
            currentType = 'Отпуск';
            loadLeaves(currentType);
        });
        document.getElementById('btn_sick').addEventListener('click', () => {
            currentType = 'Больничный';
            loadLeaves(currentType);
        });
        document.getElementById('btn_business_trip').addEventListener('click', () => {
            currentType = 'Командировка';
            loadLeaves(currentType);
        });
        document.getElementById('btn_add').addEventListener('click', () => {
            openModal('add');
        });

        document.getElementById('addForm').addEventListener('submit', (e) => {
            e.preventDefault();

            const saveBtn = e.target.querySelector('button[type="submit"]');
            saveBtn.disabled = true;

            const formData = new FormData(e.target);
            const isEdit = document.getElementById('record_id').value !== '';
            formData.append('action', isEdit ? 'update' : 'add');

            fetch('staff_leaves.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.text())
            .then(text => {
                try {
                    const data =JSON.parse(text);

                    if (data.status === 'success') {
                        closeModal();
                        showToast("✅ Запись успешно обновлена");
                    } else {
                        alert('Ошибка: ' + data.message);
                    }
                } catch (err) {
                    console.error('Ошибка парсинга JSON: ', err);
                    console.warn('Ответ сервера: ', text);
                    alert('Ошибка парсинга ответа сервераю Проверь консоль.');
                }
            })
            .catch(err => {
                console.error('Ошибка добавления:', err);
            })
            .finally(() => {
                saveBtn.disabled = false;
            });
        });

        initArchiveFilterEvents();
        loadLeaves(currentType);
    });

    function renderLeaveRowsWithMergedNames(tbody, data, showActions = true) {
        tbody.innerHTML = "";

        if (!Array.isArray(data) || data.length === 0) {
            const tr = document.createElement('tr');

            tr.innerHTML = `
                <td colspan="6" align="center">
                    Нет записей
                </td>
            `;

            tbody.appendChild(tr);
            return;
        }

        const groupedRows = groupRowsByEmployeeName(data);

        groupedRows.forEach(group => {
            group.rows.forEach((row, rowIndex) => {
                const tr = document.createElement('tr');

                const borderClass = rowIndex === 0 ? 'employee-group-border' : '';

                let nameCell = "";

                if (rowIndex === 0) {
                    nameCell = `
                        <td rowspan="${group.rows.length}" valign="middle" class="merged-fio-cell ${borderClass}">
                            ${escapeHtml(group.name)}
                        </td>
                    `;
                }

                const actionsCell = showActions
                    ? `
                        <button id="btn_red" onclick="editLeave(${row.id})" title="Редактировать">
                            <img src="img/red2.png" alt="Редактировать" width="20" height="20">
                        </button>
                        <button id="btn_delete_leave" onclick="confirmDelete(${row.id})" title="Удалить">
                            <img src="img/delete.png" alt="Удалить" width="20" height="20">
                        </button>
                    `
                    : '';

                tr.innerHTML = `
                    ${nameCell}
                    <td class="${borderClass}">${formatDate(row.start_date)}</td>
                    <td class="${borderClass}">${formatDate(row.stop_date)}</td>
                    <td class="${borderClass}">${row.total_days}</td>
                    <td class="${borderClass}">${escapeHtml(row.event)}</td>
                    <td class="${borderClass}">${actionsCell}</td>
                `;

                tbody.appendChild(tr);
            });
        });
    }

    function groupRowsByEmployeeName(data) {
        const groups = [];
        let currentGroup = null;

        data.forEach(row => {
            const name = row.name || "";

            if (currentGroup === null || currentGroup.name !== name) {
                currentGroup = {
                    name: name,
                    rows: []
                };

                groups.push(currentGroup);
            }

            currentGroup.rows.push(row);
        });

        return groups;
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function loadLeaves (type) {
        document.getElementById('archive_filters').style.display = 'none';

        document.querySelectorAll('#event_buttons button.event-switch').forEach(btn => {
            btn.classList.remove('active');
        });

        if (type === 'Отпуск') {
            document.getElementById('btn_vacations').classList.add('active');
        } else if (type === 'Больничный') {
            document.getElementById('btn_sick').classList.add('active');
        } else if (type === 'Командировка') {
            document.getElementById('btn_business_trip').classList.add('active');
        }

        fetch('staff_leaves.php?action=load&type=' + encodeURIComponent(type))
            .then(res => res.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    const table = document.getElementById('leave_table');
                    const tbody = table.querySelector('tbody');
                    tbody.innerHTML = "";

                    renderLeaveRowsWithMergedNames(tbody, data);
                    table.style.display = 'table';
                } catch (err) {
                    console.error('Ошибка JSON: ', err);
                    console.warn('Ответ сервера: ', text);
                }
            });
    }

    function editLeave (id) {
        fetch(`staff_leaves.php?action=get&id=${id}`)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    openModal('edit', data.record);
                } else {
                    alert('ошибка загрузки');
                }
            });
    }

    function openModal(mode, record = null) {
        const modal = document.getElementById('modal');
        const title = document.getElementById('modalTitle');
        const nameInfo = document.getElementById('employeeName');
        const recordIdInput = document.getElementById('record_id');
        const employeeSelect = document.querySelector('[name="employee_id"]');
        const employeeBlock = document.getElementById('selectEmployeeBlock');
        const eventBlock = document.getElementById('selectEventBlock');

        document.getElementById('addForm').reset();
        recordIdInput.value = '';
        nameInfo.textContent = '';

        if (mode === 'add') {
            title.textContent = 'Добавление записи';

            employeeBlock.style.display = 'flex';
            modal.style.width = '655px'
            modal.style.height = '120px'

            employeeSelect.required = true;
        } else if (mode === 'edit' && record) {
            title.textContent = 'Внесите корректировки';
            recordIdInput.value = record.id;

            modal.style.width = '450px'
            modal.style.height = '140px'

            employeeSelect.required = false;

            document.querySelector('[name="start_date"]').value = record.start_date;
            document.querySelector('[name="stop_date"]').value = record.stop_date;

            nameInfo.textContent = 'Сотрудник: ' + record.fio;

            employeeBlock.style.display = 'none';
        }
        modal.style.display = 'flex';
    }

    function confirmDelete (id) {
        if (!confirm('Вы уверены, что хотите удалить запись?')) return;

        fetch('staff_leaves.php', {
            method: 'POST',
            body: new URLSearchParams({
                action: 'delete',
                record_id: id
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                showToast("запись удалена");
                loadLeaves(currentType || 'Отпуск');
            } else {
                alert('' + data.message);
            }
        })
        .catch(err => {
            console.error('Ошибка запроса: ', err);
            alert('Сервер недоступен');
        });
    }

function loadArchive() {
    currentType = 'Архив';

    document.querySelectorAll('#event_buttons button.event-switch').forEach(btn => {
        btn.classList.remove('active');
    });

    document.getElementById('btn_archive').classList.add('active');
    document.getElementById('archive_filters').style.display = 'block';

    if (!validateArchiveFilters(false)) {
        const table = document.getElementById('leave_table');

        if (table) {
            const tbody = table.querySelector('tbody');

            if (tbody) {
                tbody.innerHTML = '<tr><td colspan="6" align="center">Выберите даты ручного периода</td></tr>';
            }

            table.style.display = 'table';
        }

        return;
    }

    const params = new URLSearchParams(getArchiveFilterParams());
    params.append('action', 'archive');

    fetch('staff_leaves.php?' + params.toString())
        .then(res => res.text())
        .then(text => {
            let data;

            try {
                data = JSON.parse(text);
            } catch (err) {
                console.error('Ошибка JSON:', err);
                console.warn('Ответ сервера:', text);

                alert(
                    'Ошибка загрузки архива. Сервер вернул не JSON:\n\n' +
                    text.substring(0, 1000)
                );

                return;
            }

            if (data.error) {
                alert('Ошибка: ' + data.error);
                return;
            }

            const table = document.getElementById('leave_table');

            if (!table) {
                alert('Ошибка: таблица архива leave_table не найдена.');
                return;
            }

            const tbody = table.querySelector('tbody');

            if (!tbody) {
                alert('Ошибка: tbody таблицы архива не найден.');
                return;
            }

            tbody.innerHTML = '';

            if (!Array.isArray(data) || data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" align="center">Нет записей</td></tr>';
                table.style.display = 'table';
                return;
            }

            renderLeaveRowsWithMergedNames(tbody, data, false);
            table.style.display = 'table';
        })
        .catch(err => {
            console.error('Ошибка запроса архива:', err);
            alert('Ошибка запроса архива: ' + err);
        });
}

function getArchiveFilterParams() {
        const employeeId = document.getElementById('archive_employee_filter').value;
        const event = document.getElementById('archive_event_filter').value;
        const periodType = document.getElementById('archive_period_filter').value;
        const startDate = document.getElementById('archive_start_date_filter').value;
        const stopDate = document.getElementById('archive_stop_date_filter').value;

        return {
            employee_id: employeeId,
            event: event,
            period_type: periodType,
            start_date: startDate,
            stop_date: stopDate
        };
    }

    function validateArchiveFilters(showAlert = true) {
        const periodType = document.getElementById('archive_period_filter').value;
        const startDate = document.getElementById('archive_start_date_filter').value;
        const stopDate = document.getElementById('archive_stop_date_filter').value;

        if (periodType == 7) {
            if (!startDate || !stopDate) {
                if (showAlert) {
                    alert('Укажите дату начала и дату окончания периода.');
                }

                return false;
            }

            if (startDate > stopDate) {
                if (showAlert) {
                    alert('Дата начала периода не может быть позже даты окончания.');
                }

                return false;
            }
        }

        return true;
    }

    function initArchiveFilterEvents() {
        const filterIds = [
            'archive_employee_filter',
            'archive_event_filter',
            'archive_start_date_filter',
            'archive_stop_date_filter'
        ];

        filterIds.forEach(id => {
            const element = document.getElementById(id);

            if (element) {
                element.addEventListener('change', () => {
                    if (currentType === 'Архив') {
                        loadArchive();
                    }
                });
            }
        });
    }

    function openArchiveExcelPreview() {
        if (!validateArchiveFilters()) {
            return;
        }

        const params = new URLSearchParams(getArchiveFilterParams());
        params.append('action', 'archive_excel_preview');

        fetch('staff_leaves.php?' + params.toString())
            .then(res => res.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);

                    if (data.status !== 'success') {
                        alert('Ошибка: ' + data.message);
                        return;
                    }

                    renderArchiveExcelPreview(data);
                    document.getElementById('archiveExcelPreviewOverlay').style.display = 'flex';
                } catch (err) {
                    console.error('Ошибка JSON: ', err);
                    console.warn('Ответ сервера: ', text);
                    alert('Ошибка формирования предпросмотра. Проверь консоль.');
                }
            });
    }

    function renderArchiveExcelPreview(data) {
        let filtersHtml = '';

        filtersHtml += '<table class="archive-excel-filter-table">';
        filtersHtml += '<tr><td><b>Временной промежуток</b></td><td>' + escapeHtml(data.filters.period) + '</td></tr>';
        filtersHtml += '<tr><td><b>Сотрудник</b></td><td>' + escapeHtml(data.filters.employee) + '</td></tr>';
        filtersHtml += '<tr><td><b>Событие</b></td><td>' + escapeHtml(data.filters.event) + '</td></tr>';
        filtersHtml += '</table>';

        document.getElementById('archiveExcelPreviewFilters').innerHTML = filtersHtml;

        let tableHtml = '';

        tableHtml += '<table class="archive-excel-preview-table">';
        tableHtml += '<thead>';
        tableHtml += '<tr>';
        tableHtml += '<th>ФИО</th>';
        tableHtml += '<th>Дата начала</th>';
        tableHtml += '<th>Дата окончания</th>';
        tableHtml += '<th>Календарные дни</th>';
        tableHtml += '<th>Рабочие дни</th>';
        tableHtml += '<th>Рабочие часы</th>';
        tableHtml += '<th>Событие</th>';
        tableHtml += '</tr>';
        tableHtml += '</thead>';
        tableHtml += '<tbody>';

        if (!Array.isArray(data.rows) || data.rows.length === 0) {
            tableHtml += '<tr><td colspan="7" align="center">Нет данных для выгрузки</td></tr>';
        } else {
            data.rows.forEach(row => {
                tableHtml += '<tr>';
                tableHtml += '<td>' + escapeHtml(row.excel_name) + '</td>';
                tableHtml += '<td>' + formatDate(row.start_date) + '</td>';
                tableHtml += '<td>' + formatDate(row.stop_date) + '</td>';
                tableHtml += '<td>' + escapeHtml(row.calendar_days) + '</td>';
                tableHtml += '<td>' + escapeHtml(row.work_days) + '</td>';
                tableHtml += '<td>' + escapeHtml(row.work_hours) + '</td>';
                tableHtml += '<td>' + escapeHtml(row.event) + '</td>';
                tableHtml += '</tr>';
            });
        }

        tableHtml += '</tbody>';
        tableHtml += '</table>';

        document.getElementById('archiveExcelPreviewTable').innerHTML = tableHtml;
    }

    function closeArchiveExcelPreview() {
        document.getElementById('archiveExcelPreviewOverlay').style.display = 'none';
    }

    function getUserExportTimeString() {
        const now = new Date();
        const pad = value => String(value).padStart(2, '0');

        return pad(now.getDate()) + '.' +
            pad(now.getMonth() + 1) + '.' +
            now.getFullYear() + ' ' +
            pad(now.getHours()) + ':' +
            pad(now.getMinutes()) + ':' +
            pad(now.getSeconds());
    }

    function downloadArchiveExcel() {
        if (!validateArchiveFilters()) {
            return;
        }

        const params = new URLSearchParams(getArchiveFilterParams());
        params.append('action', 'archive_excel_export');
        params.append('export_time', getUserExportTimeString());

        window.location = 'staff_leaves.php?' + params.toString();
        closeArchiveExcelPreview();
    }

    function toggleArchiveManualPeriod() {
        const periodType = document.getElementById('archive_period_filter').value;
        const manualPeriod = document.getElementById('archive_manual_period');

        if (periodType == 7) {
            manualPeriod.style.display = 'inline';
        } else {
            manualPeriod.style.display = 'none';
        }

        if (currentType === 'Архив') {
            loadArchive();
        }
    }

    function closeModal() {
        document.getElementById('modal').style.display = 'none';
        document.getElementById('addForm').reset();
    }

    function formatDate (dateStr) {
        const date = new Date(dateStr);
        const day = ('0' + date.getDate()).slice(-2);
        const month = ('0' + (date.getMonth() + 1)).slice(-2);
        const year = date.getFullYear();
        return `${day}.${month}.${year}`;
    }

    function showToast (message = "✅ успешно", delay = 1500) {
        const toast = document.getElementById('toast');
        toast.textContent = message;

        toast.style.display = 'flex';

        setTimeout(() => {
            toast.style.display = 'none';

            if (currentType === 'Архив') {
                loadArchive();
            } else {
                loadLeaves(currentType || 'Отпуск');
            }
        }, delay);
    }
