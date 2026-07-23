function set_period() {
  var reportType = document.getElementById('report_type').value;
  var manualBlock = document.getElementById('manual_rep');
  var startReportDate = document.getElementById('report_start_date').value;
  var stopReportDate = document.getElementById('report_stop_date').value;

  if (reportType == 7) {
    manualBlock.style.display = 'flex';
    return;
  }

  manualBlock.style.display = 'none';

  $.ajax({
    type: 'POST',
    url: 'ajax/set_report_date_interval.php',
    data: {
      report_type: reportType,
      start_report_date: startReportDate,
      stop_report_date: stopReportDate
    },
    cache: false,
    success: function(response) {
      if (typeof response === 'string' && response.indexOf('Ошибка') !== -1) {
        alert(response);
        return;
      }

      window.location.reload();
    },
    error: function(xhr) {
      var message = 'Не удалось изменить отчетный период.';

      if (xhr.responseText) {
        message += '\n\n' + xhr.responseText;
      }

      alert(message);
    }
  });
}

function manual_report_set() {
  var reportType = document.getElementById('report_type').value;
  var startReportDate = document.getElementById('report_start_date').value;
  var stopReportDate = document.getElementById('report_stop_date').value;

  if (!startReportDate || !stopReportDate) {
    alert('Укажите дату начала и дату окончания периода.');
    return;
  }

  if (startReportDate > stopReportDate) {
    alert('Дата начала периода не может быть позже даты окончания.');
    return;
  }

  $.ajax({
    type: 'POST',
    url: 'ajax/set_report_date_interval.php',
    data: {
      report_type: reportType,
      start_report_date: startReportDate,
      stop_report_date: stopReportDate
    },
    cache: false,
    success: function(response) {
      if (typeof response === 'string' && response.indexOf('Ошибка') !== -1) {
        alert(response);
        return;
      }

      if (typeof response === 'string' && response.indexOf('Слишком большой диапазон') !== -1) {
        alert(response);
        return;
      }

      window.location.reload();
    },
    error: function(xhr) {
      var message = 'Не удалось применить ручной отчетный период.';

      if (xhr.responseText) {
        message += '\n\n' + xhr.responseText;
      }

      alert(message);
    }
  });
}

function ta_delete( delID ){
  var perform=confirm('Запись будет удалена. Продолжить?')
  if ( perform == true ){
    $.post('ajax/time_delete.php', {delID: delID}, RetSWT);
    function RetSWT(dat) {
      window.location=self.location;
    }
  }
}

function close_add_time_list(){
  document.getElementById('adds_list_header').style.display='none';
}

function close_pause_time_list(){
  document.getElementById('pauses_list_header').style.display='none';
}

function close_penalties_list(){
  document.getElementById('penalty_list_header').style.display='none';
}

///////////////////////

function show_selectors(){
  var report_type = document.getElementById('report_type').value;

  if ( report_type == 7 ){
    document.getElementById('manual_rep').style.display='flex';
  }
  else{
    document.getElementById('manual_rep').style.display='none';
  }
}

function update_clock(){
  $.post('ajax/get_current_day_time.php', RetSWT);
  function RetSWT(dat) {
    if ( document.getElementById('dateTimeFieldNav') ){
      document.getElementById('dateTimeFieldNav').innerHTML = dat;
    }
  }
}

var timerId = setInterval(update_clock, 10000);
