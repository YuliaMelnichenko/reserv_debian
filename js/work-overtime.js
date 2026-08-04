$(document).ready(function () {
    function loadList(hours) {
        hours = parseFloat(hours) || 9;
        let period = $('#period_select').val();
        let start = '', end = '';

        if (period === 'custom') {
            start = $('#custom_start').val();
            end = $('#custom_end').val();

            if (!start || !end) {
                $('#results_table tbody').html('<tr><td colspan="3">Укажите даты для поиска</td></tr>');
                return;
            }
        }

        $('#results_table tbody').html('<tr><td colspan="3">Загрузка...</td></tr>');

        $.ajax({
            url: 'work_overtime.php',
            data: {action: 'load', hours: hours, period: period, start: start, end: end},
            dataType: 'json',
            success: function(resp) {
                if (resp.status !== 'success') {
                    alert('Ошибка: ' + (resp.message || 'неизвестная ошибка'));
                    $('#results_table tbody').html('');
                    return;
                }
                const rows = resp.data;
                if (!rows.length) {
                    $('#results_table tbody').html('<tr><td colspan="3">Нет сотрудников, подходящих под критерий.</td></tr>');
                    return;
                }
                let html = '';
                rows.forEach(r => {
                    html += `<tr>
                                <td>${escapeHtml(r.fio)}</td>
                                <td>${r.overtime_count}</td>
                                <td><button class="btn" onclick="showDetails(${r.id}, ${hours}, '${encodeURIComponent(r.fio)}')"> ➜ </button></td>
                            </tr>`;
                });
                $('#results_table tbody').html(html);
            },
            error: function(xhr, status, err) {
                console.error(err);
                alert('Сервер недоступен');
                $('#results_table tbody').html('');
            }
        });
    }

    $('#btn_search').on('click', function() {
        const hours = $('#hours_input').val();
        loadList(hours);
    });

    $('#period_select').on('change', function() {
        if ($(this).val() === 'custom') {
            $('#custom_range_block').show();
        } else {
            $('#custom_range_block').hide();
        }
        const hours = $('#hours_input').val();
        loadList(hours);

    });

    loadList($('#hours_input').val());

    $('#modal_close, #modal_overlay').on('click', function() {
        closeModal();
    });
});

function showDetails(empId, hours, fioEncoded) {
    hours = parseFloat(hours) || 9;
    const period = $('#period_select').val() || 'quarter';
    var fio = decodeURIComponent(fioEncoded || '');
    let start = '', end = '';

    if (period === 'custom') {
        start = $('#custom_start').val();
        end = $('#custom_end').val();
    }
    $('#modal_title').text('Сотрудник: ' + fio);
    $('#details_table tbody').html('<tr><td colspan="3">Загрузка...</td></tr>');
    $('#modal_overlay').show();
    $('#modal_details').show();

    $.ajax({
        url: 'work_overtime.php',
        data: {action: 'details', id: empId, hours: hours, period: period, start: start, end: end},
        dataType: 'json',
        success: function(resp) {
            if (resp.status !== 'success') {
                alert('Ошибка: ' + (resp.message || 'неизвестная ошибка'));
                $('#details_table tbody').html('');
                return;
            }
            const rows = resp.data;
            if (!rows.length) {
                $('#details_table tbody').html('<tr><td colspan="3">Нет записей.</td></tr>');
                return;
            }
            let html = '';
            rows.forEach(r => {
                html += `<tr>
                            <td style="border: 1px solid #ccc; padding: 6px;">${formatDate(r.date)}</td>
                            <td style="border: 1px solid #ccc; padding: 6px;">${r.hours_total}</td>
                            <td style="border: 1px solid #ccc; padding: 6px;">
                                ${r.outside_hours === '—' ? '' : r.outside_hours}
                            </td>
                        </tr>`;
            });
            $('#details_table tbody').html(html);
        },
        error: function() {
            alert('Ошибка запроса деталей');
            $('#details_table tbody').html('');
        }
    });
}

function closeModal() {
    $('#modal_details').hide();
    $('#modal_overlay').hide();
    $('#details_table tbody').html('');
}

function escapeHtml(text) {
    if (text === null || text === undefined) return '';

    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    const parts = dateStr.split('-');
    if (parts.length !== 3) return dateStr;
    return parts[2] + '.' + parts[1] + '.' + parts[0];
}
