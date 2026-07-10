// Attach the session CSRF token to every same-origin state-changing request.
(function(window, document) {
  'use strict';

  function getCookie(name) {
    var prefix = name + '=';
    var cookies = document.cookie ? document.cookie.split(';') : [];

    for (var i = 0; i < cookies.length; i++) {
      var cookie = cookies[i].replace(/^\s+/, '');

      if (cookie.indexOf(prefix) === 0) {
        return decodeURIComponent(cookie.substring(prefix.length));
      }
    }

    return '';
  }

  function isUnsafeMethod(method) {
    return !/^(GET|HEAD|OPTIONS)$/i.test(method || 'GET');
  }

  function isSameOrigin(url) {
    var anchor = document.createElement('a');
    anchor.href = url;

    return anchor.protocol === window.location.protocol
      && anchor.host === window.location.host;
  }

  if (window.XMLHttpRequest) {
    var originalOpen = window.XMLHttpRequest.prototype.open;
    var originalSend = window.XMLHttpRequest.prototype.send;

    window.XMLHttpRequest.prototype.open = function(method, url) {
      this.toriRequestMethod = method;
      this.toriRequestUrl = url;
      return originalOpen.apply(this, arguments);
    };

    window.XMLHttpRequest.prototype.send = function() {
      var token = getCookie('TORI_CSRF_TOKEN');

      if (
        token
        && isUnsafeMethod(this.toriRequestMethod)
        && isSameOrigin(this.toriRequestUrl)
      ) {
        this.setRequestHeader('X-CSRF-Token', token);
      }

      return originalSend.apply(this, arguments);
    };
  }

  if (window.fetch) {
    var originalFetch = window.fetch;

    window.fetch = function(input, options) {
      var requestOptions = options ? Object.assign({}, options) : {};
      var requestUrl = typeof input === 'string' ? input : input.url;
      var requestMethod = requestOptions.method
        || (typeof input !== 'string' && input.method)
        || 'GET';
      var token = getCookie('TORI_CSRF_TOKEN');

      if (token && isUnsafeMethod(requestMethod) && isSameOrigin(requestUrl)) {
        var headers = new Headers(
          requestOptions.headers
          || (typeof input !== 'string' ? input.headers : undefined)
          || {}
        );
        headers.set('X-CSRF-Token', token);
        requestOptions.headers = headers;
      }

      return originalFetch.call(window, input, requestOptions);
    };
  }
})(window, document);

function unset_cookie(csrfToken){
  var token = csrfToken || window.TORI_CSRF_TOKEN || '';

  if (!token) {
    var prefix = 'TORI_CSRF_TOKEN=';
    var cookies = document.cookie ? document.cookie.split(';') : [];

    for (var i = 0; i < cookies.length; i++) {
      var cookie = cookies[i].replace(/^\s+/, '');

      if (cookie.indexOf(prefix) === 0) {
        token = decodeURIComponent(cookie.substring(prefix.length));
        break;
      }
    }
  }

  $.post('ajax/delete_cookie.php', {_csrf: token}, RetSWT1 );
  function RetSWT1(dat1) {
    console.log(dat1);
  }    
}

function set_delay(/* userID */){
  $.post('ajax/set_delay_by_entrance.php', RetSWT1);
  function RetSWT1(dat1) {
    add_expl();

    if ( document.getElementById('explBtn') ){ document.getElementById('explBtn').disabled = false; }
  }
}

function set_delay_for_user_by_SY( userID ){
  $.post('ajax/set_delay_by_entrance.php', { userID: userID }, RetSWT1);
  function RetSWT1(dat1) 
  {
  }
}

function delay_set( delayId, userId ){
  if ( document.getElementById('delay_explanation_delay') ){ document.getElementById('delay_explanation_delay').style.display='block'; }

  $.post('ajax/get_delay_explanation.php', {mode: 1, delayId: delayId, userId: userId}, RetSWT2 );
  function RetSWT2(dat2){
    if ( document.getElementById('delay_explanation_delay') ){ document.getElementById('delay_explanation_delay').innerHTML = dat2; }
  } 
}

function close_explanation( mode ){
  if ( document.getElementById('delayAgreed') ){
    var disableState = document.getElementById('delayAgreed').disabled;

    if ( disableState == false ){
      if ( mode == 0 ){
        alert( "Опоздания без указания причины считаются опозданиями без уважительной причины" );
      }
    }
  } 
  if ( document.getElementById('delay_explanation_delay') ){ document.getElementById('delay_explanation_delay').style.display='none'; }
}

function set_explanation( mode, delayID ){
  if ( document.getElementById('delayExplanation') ){
    var delayExplanationV = document.getElementById('delayExplanation').value;
    var delayExplanationSUV = document.getElementById('delayExplanationSU').value;

    $.post('ajax/set_delay_explanation.php', { delayExplanationSU: delayExplanationSUV, delayExplanation: delayExplanationV, mode: mode, delayID: delayID },RetSWT1);
    function RetSWT1(dat1) {
      if ( mode == 0 ){
        build_in_delay_expl();
      }
      else{ 
        show_delay_table();
      }
      if ( dat1 == 2 ){
        alert( "Сведения по объяснению к опозданию изменены" );
      }
      else if ( dat1 == 0 ){
        alert( "Невозможно изменить уже заквитированных объяснений к опозданиям" );
      }
      if (typeof get_time_registration_div_content === 'function') {
        get_time_registration_div_content();
      }
    }
  } 
  if ( document.getElementById('delay_explanation_delay') ){ document.getElementById('delay_explanation_delay').style.display='none'; }
}

function set_add_times_height(){
  $.post('ajax/get_add_times_block_height.php', {},RetSWT1);
  function RetSWT1(dat3) {
    var datVal = parseInt( dat3, 10 );
    
    var tableHeight = 0;

    if ( document.getElementById('addTimesTable') ){ 
      tableHeight = document.getElementById('addTimesTable').offsetHeight;
    }
     
    datVal = datVal + tableHeight;
    if ( datVal > 360 ){
      datVal = 360;
    } 

    if ( document.getElementById('delay_explanation_add_time') ){ document.getElementById('delay_explanation_add_time').style.height = datVal + "px"; }
  }   
}

function set_delay_notificationc_count(){
  if ( document.getElementById('notifDelayBtn') ){
    $.post('ajax/get_delay_notif_count.php', {},RetSWT2);
    function RetSWT2(dat2) {
      document.getElementById('notifDelayBtn').innerHTML = dat2;
    }
  }
}

function set_add_time_notificationc_count(){
  if ( document.getElementById('notifBtn') ){
    $.post('ajax/get_add_time_notif_count.php', {},RetSWT2);
    function RetSWT2(dat2) { 
      document.getElementById('notifBtn').innerHTML = dat2;
    }
  }
}

function save_changes_time(userID) {
  if (document.getElementById('add_stop_time')) {
    var stopWorking = document.getElementById('add_stop_time').value;
    var visitId = document.getElementById('change_visit_id')
      ? document.getElementById('change_visit_id').value
      : 0;

    if (!visitId || visitId <= 0) {
      alert("Не найдена запись рабочего дня для изменения.");
      return;
    }

    if (!stopWorking) {
      alert("Укажите дату и время ухода.");
      return;
    }

    $.post(
      'ajax/set_change_out_time.php',
      {
        visit_id: visitId,
        add_stop_time: stopWorking
      },
      function(dat1) {
        if (dat1 == 2) {
          alert("Время ухода изменено.");
          location.reload();
        }
        else {
          alert(dat1);
        }
      }
    );
  }

  if (document.getElementById('add_stop_eat_time')) {
    var stopEat = document.getElementById('add_stop_eat_time').value;
    var visitIdEat = document.getElementById('change_visit_id')
      ? document.getElementById('change_visit_id').value
      : 0;

    if (!visitIdEat || visitIdEat <= 0) {
      alert("Не найдена запись рабочего дня для изменения.");
      return;
    }

    if (!stopEat) {
      alert("Укажите дату и время окончания обеда.");
      return;
    }

    $.post(
      'ajax/set_change_stop_eat.php',
      {
        visit_id: visitIdEat,
        add_stop_eat_time: stopEat
      },
      function(dat2) {
        if (dat2 == 2) {
          alert("Время изменено.");
          location.reload();
        }
        else {
          alert(dat2);
        }
      }
    );
  }

  if (document.getElementById('delay_out_time')) {
    document.getElementById('delay_out_time').style.display = 'none';
  }
}

function show_entrance_page(){
  if ( document.getElementById('entrance_approvement') ){
    $.post('ajax/get_entrances.php', RetSWT1);
    function RetSWT1(dat1) {
      document.getElementById('entrance_approvement').innerHTML = dat1;    

      tableHeight = document.getElementById('entrance_approvement_table_users').offsetHeight + 20;
      tableWidth = document.getElementById('entrance_approvement_table_users').offsetWidth;
      win_h = $(window).height();
      win_w = $(window).width();

      if ( tableHeight > win_h ){ tableHeight = win_h; }
      if ( tableWidth > win_w ){ tableWidth = win_w; }

      document.getElementById('entrance_approvement').style.height = tableHeight + "px";
      document.getElementById('entrance_approvement').style.width = tableWidth + "px";
    }
  }   	
} 

function set_new_entrance_time( userID, inTime, newInTime, superUserID ){
  var perform=confirm('Будет изменено время прихода сотрудника на рабочее место. Продолжить?')
  if ( perform == true ){
    $.post('ajax/teast_time_adjust.php', { userID: userID, inTime: newInTime}, RetSWT1);
    function RetSWT1(dat1){
      var adjInTime = 0;
      var adjDelay = 0;
      if ( dat1 == 1 ){
        var perform=confirm('Устанавливаемое время превышает максимально допустимое время прихода для выбранного сотрудника.\nБудет добавлена или обновлена информация по опозданию. Продолжить?')
        if ( perform == true ){
          adjInTime = 1;
          adjDelay = 1;
        }
      }
      else if ( dat1 == 0 ){
        adjInTime = 1;
      }
         
      if ( adjInTime == 1 ){
        $.post('ajax/adj_in_time.php', { userID: userID, inTime: newInTime },RetSWT6);
        function RetSWT6(dat6){
          if ( dat6 < 0 ){
            var perform=confirm('Вычитаемое время превышает общее время присутствия на рабочем месте.\nУдалить сведения о присутствии сотрудника за текущий день?')
            if ( perform == true ){
              $.post('ajax/delete_user_visitiong_info_by_currentDay.php', { userID: userID },RetSWT7);
              function RetSWT7(dat7){
                $.post('ajax/set_user_alert.php', { userID: userID, messageMode: 1 },RetSWT8);  
                function RetSWT8(dat8){  
                  window.location="entrance_management.php";
                }
              }
            }
            else
            {
            }
          } 
          else{
            $.post('ajax/set_user_alert.php', { userID: userID, messageMode: 2 },RetSWT19);
            function RetSWT19(dat19){
              window.location="entrance_management.php";
            }
          }  
        } 
      }
      if ( adjDelay == 1 ){
        set_delay_for_user_by_SY( userID );
      }
    }
  }  
}

function fill_alerts_by_user( userID, date, startTime, messStr ){
  add_addition_time_by_alert( userID, date, startTime, messStr );
} 

function add_addition_time_by_alert( userID, date, startTime, messStr ){
  if ( document.getElementById('delay_explanation_add_time_part') ){
    $.post('ajax/get_add_time_part.php', { by_alert: 1 },RetSWT1);
    function RetSWT1(dat1) {  
      document.getElementById('delay_explanation_add_time_part').innerHTML = dat1;
      document.getElementById('delay_explanation_add_time_part').style.display='block';
      document.getElementById('add_time_part_date').value = date;
      document.getElementById('add_time_part_start_time').value = startTime;
      document.getElementById('add_time_part_stop_time').value = "18:00";
      document.getElementById('add_time_part_start_date').value = date;
      document.getElementById('add_time_part_stop_date').value = date;
      document.getElementById('add_time_part_base').value = 4;
      document.getElementById('add_time_part_base_1').value = 8;
      document.getElementById('add_time_part_desc').value = messStr;
      document.getElementById('add_time_part_desc_1').value = messStr;
      document.getElementById('exclude_weekend_holidays').checked = false;
    }
  }
}

function show_alerts_page(){
  if ( document.getElementById('alert_approvement') ){
    $.post('ajax/get_alerts_count.php', RetSWT10);
    function RetSWT10(dat10) {
      if ( dat10 == 1 ){
        $.post('ajax/get_alerts.php', RetSWT1);
        function RetSWT1(dat1) {
          document.getElementById('alert_approvement').innerHTML = dat1;

          tableHeight = document.getElementById('alert_approvement_table_users').offsetHeight + 20;
          tableWidth = document.getElementById('alert_approvement_table_users').offsetWidth;
          win_h = $(window).height();
          win_w = $(window).width();

          if ( tableHeight > win_h ){ tableHeight = win_h; }
          if ( tableWidth > win_w ){ tableWidth = win_w; }

          document.getElementById('alert_approvement').style.height = tableHeight + "px";
          document.getElementById('alert_approvement').style.width = tableWidth + "px";
        }
      }
      else{
        window.location="index.php";
      }
    }
  }   	
} 

function set_alert_viewed( alertID ){
  $.post('ajax/set_alert_viewed.php', { alertID: alertID },RetSWT1);
  function RetSWT1(dat1) {
    update_alerts_page();
  }
} 

function update_alerts_page() {
  $.post('ajax/get_alerts_count.php', RetSWT1);
  function RetSWT1(dat1) { 
    if ( dat1 == 1 )
    {
      window.location="alerts.php";
    }
    else
    {
      window.location="index.php";
    }
  }
}

function pause_set_start(){
  $.post('ajax/go_back_pause_page_mode.php', RetSWT1);
  function RetSWT1(dat1) {
    show_pause_page();
  }  	
}

function pause_go_back(){
  $.post('ajax/go_back_pause_page_mode.php', RetSWT1);
  function RetSWT1(dat1) {
    show_pause_page();
  }  	
}

function show_pause_page(){
  if ( document.getElementById('pause_approvement') ){
    $.post('ajax/get_pause_page_mode.php', RetSWT1);
    function RetSWT1(dat1) {
      if ( dat1 == 1 ){
        show_pause_notification();
      }  
      if ( dat1 == 2 ){
        show_pause_by_user( -1 );
      }
      set_pause_notificationc_count();
    }
  }   	
}

function set_pause_notificationc_count(){
  if ( document.getElementById('notifPauseBtn') ){
    $.post('ajax/get_pause_notif_count.php', {},RetSWT2);
    function RetSWT2(dat2) {
      document.getElementById('notifPauseBtn').innerHTML = dat2;
    }
  }
}

function show_pause_notification(){
  if ( document.getElementById('pause_approvement') ){
    $.post('ajax/get_pause_notification_table.php', RetSWT1);
    function RetSWT1(dat1) {
      document.getElementById('pause_approvement').innerHTML = dat1;

      if ( document.getElementById('pause_approvement_table_users') && document.getElementById('pause_approvement') ){
        tableHeight = document.getElementById('pause_approvement_table_users').offsetHeight + 50;
        tableWidth = document.getElementById('pause_approvement_table_users').offsetWidth + 30;
        win_h = $(window).height();
        win_w = $(window).width();

        if ( tableHeight > win_h ){ tableHeight = win_h; }
        if ( tableWidth > win_w ){ tableWidth = win_w; }

        document.getElementById('pause_approvement').style.height = tableHeight + "px";
        document.getElementById('pause_approvement').style.width = tableWidth + "px";
        set_delay_notificationc_count();
      }
    }
  }   	
}

function show_pause_by_user( user ){
  if ( document.getElementById('pause_approvement') ){
    $.post('ajax/get_pauses_by_user.php', { user: user }, RetSWT1);
    function RetSWT1(dat1) {
      document.getElementById('pause_approvement').innerHTML = dat1;

      if ( document.getElementById('pause_approvement_table') && document.getElementById('pause_approvement') ){
        tableHeight = document.getElementById('pause_approvement_table').offsetHeight;
        tableWidth = document.getElementById('pause_approvement_table').offsetWidth;
        win_h = $(window).height() - 110;
        win_w = $(window).width();

        if ( tableHeight > win_h ){ tableHeight = win_h; }
        if ( tableWidth > win_w ){ tableWidth = win_w; }

        document.getElementById('pause_approvement').style.height = tableHeight + "px";
        document.getElementById('pause_approvement').style.width = 550 + "px";
        set_delay_notificationc_count();
      }  
    }
  }
}        

function show_add_time_table( showTable ){
  $.post('ajax/get_add_times.php', {},RetSWT1);
  function RetSWT1(dat1) {
    if ( document.getElementById('delay_explanation_add_time_part') ){ document.getElementById('delay_explanation_add_time_part').style.display='none'; }
    if ( document.getElementById('delay_explanation_head') ){ document.getElementById('delay_explanation_head').style.display='none'; }
    if ( document.getElementById('delay_explanation_add_time') ){
      document.getElementById('delay_explanation_add_time').innerHTML = dat1;
      document.getElementById('delay_explanation_add_time').style.display='block';
    }

    set_add_times_height();
    set_add_time_notificationc_count();
  }
  if ( document.getElementById('delay_explanation_head') ){ document.getElementById('delay_explanation_head').style.display='none'; }
  if ( document.getElementById('delay_explanation_add_time') ){ document.getElementById('delay_explanation_add_time').style.display='block'; }
  if ( showTable == 1 ){
    show_table();
  } 
}

function show_delay_page(){
  if ( document.getElementById('delay_approvement') ){
    $.post('ajax/get_delay_page_mode.php', RetSWT1);
    function RetSWT1(dat1) {
      if ( dat1 == 1 ){
        show_delay_notification();
      }  
      if ( dat1 == 2 ){
        show_delays_by_user( -1 );
      }
      set_delay_notificationc_count();
    }
  }   	
}

function show_delay_notification(){
  if ( document.getElementById('delay_approvement') ){
    $.post('ajax/get_delay_notification_table.php', RetSWT1);
    function RetSWT1(dat1) {
      document.getElementById('delay_approvement').innerHTML = dat1;

      if ( document.getElementById('delay_approvement_table_users') && document.getElementById('delay_approvement') ){
        tableHeight = document.getElementById('delay_approvement_table_users').offsetHeight + 50;
        tableWidth = document.getElementById('delay_approvement_table_users').offsetWidth + 25;
        win_h = $(window).height();
        win_w = $(window).width();

        if ( tableHeight > win_h ){ tableHeight = win_h; }
        if ( tableWidth > win_w ){ tableWidth = win_w; }

        document.getElementById('delay_approvement').style.height = tableHeight + "px";
        document.getElementById('delay_approvement').style.width = tableWidth + "px";
        set_delay_notificationc_count();

        $.post('ajax/get_delay_approvment_header_content.php', { width: tableWidth, offs: 12 }, RetSWT2);
        function RetSWT2(dat2) {
          document.getElementById('delayHeader').innerHTML = dat2;
        }
      }
    }
  }	
}

function show_delays_by_user( user ){
  if ( document.getElementById('delay_approvement') ){
    $.post('ajax/get_delays_by_user.php', { user: user }, RetSWT1);
    function RetSWT1(dat1) {
      document.getElementById('delay_approvement').innerHTML = dat1;

      if ( document.getElementById('delay_approvement_table') && document.getElementById('delay_approvement') ){
        tableHeight = document.getElementById('delay_approvement_table').offsetHeight;
        tableWidth = document.getElementById('delay_approvement_table').offsetWidth;
        win_h = $(window).height() - 110;
        win_w = $(window).width();

        if ( tableHeight > win_h ){ tableHeight = win_h; }
        if ( tableWidth > win_w ){ tableWidth = win_w; }

        document.getElementById('delay_approvement').style.height = tableHeight + "px";
        document.getElementById('delay_approvement').style.width = 1020 + "px";
        set_delay_notificationc_count();

        $.post('ajax/get_delay_approvment_header_content.php', { width: tWidth, offs: 8 }, RetSWT2);
        function RetSWT2(dat2) {
          document.getElementById('delayHeader').innerHTML = dat2;
        }
      }
    }
  }
}

function delay_go_back(){
  $.post('ajax/go_back_delay_page_mode.php', RetSWT1);
  function RetSWT1(dat1) {
    show_delay_page();
  }	
}

function delay_set_start(){
  $.post('ajax/go_back_delay_page_mode.php', RetSWT1);
  function RetSWT1(dat1) {
    show_delay_page();
  }  	
}

function accept_refuse_delay_for_user_final( addID, suDesc, accept, penaltyID, penDate, userID ){
  $.post('ajax/set_delay_penalty_info.php', { addID: addID, suDesc: suDesc, accept: accept, penaltyID: penaltyID, penDate: penDate, userID: userID }, RetSWT2);
  function RetSWT2(dat2) {
    show_delays_by_user( -1 );
    document.getElementById('delay_approvement_desc').style.display='none';
  }  
}

function accept_delay_for_user( addID, suDesc, penaltyID, penDate, userID ){
  if ( document.getElementById('delay_approvement_desc') ){
    document.getElementById('delay_approvement_desc').style.display='flex';
    document.getElementById('delay_part_desc_2').value = suDesc;
    document.getElementById('recIDTempVal').value = addID;
    document.getElementById('penIDTempVal').value = penaltyID;
    document.getElementById('penDateTempVal').value = penDate;
    document.getElementById('penUserIDTempVal').value = userID;
    document.getElementById('acceptTempVal').value = 1;  
  }
  else {
    alert("WArning");
  }
}

function refuse_delay_for_user( addID, suDesc, penaltyID, penDate, userID ){
  if ( document.getElementById('delay_approvement_desc') ){
    document.getElementById('delay_approvement_desc').style.display='flex';
    document.getElementById('delay_part_desc_2').value = suDesc;
    document.getElementById('recIDTempVal').value = addID;
    document.getElementById('penIDTempVal').value = penaltyID;
    document.getElementById('penDateTempVal').value = penDate;
    document.getElementById('penUserIDTempVal').value = userID;
    document.getElementById('acceptTempVal').value = -1;
  }
  else {
    alert("Warning");
  }
}

function mark_as_deleted_delay_for_user( addID ){
  var perform=confirm('Запись будет помечена как удаленная. Продолжить?')
  if ( perform == true ){
    var mode = 100;
    $.post('ajax/set_delay_state.php', { addID: addID, mode: mode });
  }   	
}

function mark_as_undeleted_delay_for_user( addID ){
  var perform=confirm('Запись будет восстановлена. Продолжить?')
  if ( perform == true ){
    var mode = 200;
    $.post('ajax/set_delay_state.php', { addID: addID, mode: mode });
  }
}

function show_add_times_by_user( user ){  
  if ( document.getElementById('add_time_content') ){  
    $.post('ajax/get_add_times_by_user.php', { user: user }, RetSWT1);
    function RetSWT1(dat1) {
      document.getElementById('add_time_content').innerHTML = dat1;   

      if ( document.getElementById('add_time_approvement_table') && document.getElementById('add_time_content') ){ 
        tableHeight = document.getElementById('add_time_approvement_table').offsetHeight;
        tableWidth = document.getElementById('add_time_approvement_table').offsetWidth;
        win_h = $(window).height() - 150;
        win_w = $(window).width();

        if ( tableHeight > win_h ){ tableHeight = win_h; }
        if ( tableWidth > win_w ){ tableWidth = win_w; }
      
        tWidth = 1095;

        document.getElementById('add_time_content').style.height = tableHeight + "px";
        document.getElementById('add_time_content').style.width = tWidth + "px";
        set_add_time_notificationc_count();

        $.post('ajax/get_time_approvment_header_content.php', { width: tWidth, offs: 8 }, RetSWT2);
        function RetSWT2(dat2) {
          document.getElementById('addTimeHeader').innerHTML = dat2;
        }
      }  
    }
  }   	
}

function add_time_go_back(){  
  $.post('ajax/go_back_add_time_page_mode.php', RetSWT1);
  function RetSWT1(dat1) {
    show_add_time_page();
  }  	
}

function add_time_set_start(){  
  $.post('ajax/go_back_add_time_page_mode.php', RetSWT1);                           
  function RetSWT1(dat1) {
    show_add_time_page();
  }  	
}

function accept_refuse_add_time_for_user_final( addID, suDesc, accept ) {
  $.post('ajax/set_add_times_info.php', { addID: addID, suDesc: suDesc, accept: accept }, RetSWT1);                           
  function RetSWT1(dat1) {
    show_add_times_by_user( -1 );
    document.getElementById('add_time_approvement_desc').style.display='none';
  }
}

function accept_add_time_for_user( addID, suDesc ){ 
  if ( document.getElementById('add_time_approvement_desc') ){
    document.getElementById('add_time_approvement_desc').style.display='block';
    document.getElementById('add_time_part_desc_2').value = suDesc;
    document.getElementById('recIDTempVal').value = addID;
    document.getElementById('acceptTempVal').value = 1;  
  }
}

function refuse_add_time_for_user( addID, suDesc ){ 
  if ( document.getElementById('add_time_approvement_desc') ){
    document.getElementById('add_time_approvement_desc').style.display='block';
    document.getElementById('add_time_part_desc_2').value = suDesc;
    document.getElementById('recIDTempVal').value = addID;
    document.getElementById('acceptTempVal').value = -1;
  }
}

function mark_as_deleted_add_time_for_user( addID ){  
  var perform=confirm('Запись будет помечена как удаленная. Продолжить?')
  if ( perform == true ){
    var mode = 100;
    $.post('ajax/set_add_times_state.php', { addID: addID, mode: mode });                           
  }
}

function mark_as_undeleted_add_time_for_user( addID ){  
  var perform=confirm('Запись будет восстановлена. Продолжить?')
  if ( perform == true ){
    var mode = 200;
    $.post('ajax/set_add_times_state.php', { addID: addID, mode: mode });
  }   	
}

function show_table(){
  if ( document.getElementById('add_times_table') ){
    $.post('ajax/get_add_times_table.php', RetSWT1);
    function RetSWT1(dat1) {
      document.getElementById('add_times_table').innerHTML = dat1;              
    }
  }   	
}

function show_pause_table(){
  if ( document.getElementById('pause_times_table') ){
    $.post('ajax/get_pause_times_table.php', RetSWT1);

                         
    function RetSWT1(dat1) {
      document.getElementById('pause_times_table').innerHTML = dat1;
    }
  }   	
}

function show_pause_sport_table(){
  if ( document.getElementById('pause_sport_times_table') ){
    $.post('ajax/get_pause_sport_table.php', RetSWT1);  
        
    function RetSWT1(dat1) {
      document.getElementById('pause_sport_times_table').innerHTML = dat1;
    }
  }   	
}

function show_delay_table(){
  if ( document.getElementById('delay_table') ){
    $.post('ajax/get_delay_table.php', RetSWT1);
    function RetSWT1(dat1) {
      document.getElementById('delay_table').innerHTML = dat1;
    }
  }   	
}

function ta_delete( delID ){	
  var perform=confirm('запись будет удалена. Продолжить?')
  if ( perform == true ){
    $.post('ajax/time_delete.php', {delID: delID}, RetSWT);
    function RetSWT(dat) {
      show_table();  
    }
  }
}

function cancel_time_add(){
  if ( document.getElementById('delay_explanation_add_time') ){ document.getElementById('delay_explanation_add_time').style.display='none'; }
}

function add_addition_time(){
  if ( document.getElementById('delay_explanation_add_time') && document.getElementById('delay_explanation_add_time_part') ){
    $.post('ajax/get_add_time_part.php', {},RetSWT1);
    function RetSWT1(dat1) { 
      document.getElementById('delay_explanation_add_time').style.display='none';
      document.getElementById('delay_explanation_add_time_part').innerHTML = dat1;
      document.getElementById('delay_explanation_add_time_part').style.display='block';
    }
  }
}

function cancel_part_time_add(){
  if ( document.getElementById('delay_explanation_add_time_part') ){ document.getElementById('delay_explanation_add_time_part').style.display='none'; }
  $.post('ajax/get_add_times.php', {},RetSWT1);
  function RetSWT1(dat1) { 
    if ( document.getElementById('delay_explanation_add_time') ){ 
      document.getElementById('delay_explanation_add_time').innerHTML = dat1; 
      document.getElementById('delay_explanation_add_time').style.display='block';
    }
  }
}

function add_training_time(){
  $.post('ajax/get_add_gym_time.php', RetSWT1);
  function RetSWT1(dat1) { 
    if ( document.getElementById('delay_explanation_sport_time') ){
      document.getElementById('delay_explanation_sport_time').innerHTML = dat1;
      document.getElementById('delay_explanation_sport_time').style.display='flex';
    }
  }
}

function close_add_sport_time(){
  location.reload();
  if ( document.getElementById('delay_explanation_sport_time') ){ document.getElementById('delay_explanation_sport_time').style.display='none'; }
}

function part_time_add( byAlert ){
  if ( document.getElementById('add_time_certain') && document.getElementById('add_time_range') ){
    if ( document.getElementById('add_time_certain').checked ){ 
      if ( document.getElementById('add_time_part_start_dateTime') && document.getElementById('add_time_part_stop_dateTime') &&  document.getElementById('add_time_part_base') && document.getElementById('add_time_part_desc') ){
        var add_time_part_start_dt = document.getElementById('add_time_part_start_dateTime').value;
        var add_time_part_stop_dt = document.getElementById('add_time_part_stop_dateTime').value;
 
        if ( add_time_part_start_dt == add_time_part_stop_dt ){
          alert( "Длительность работы равна 0 !" );
          return;
        }
 
        if ( add_time_part_start_dt > add_time_part_stop_dt ){
          alert( "Время начала работ больше времени окончания работ!" );
          return;
        }
  
        var add_time_part_base = document.getElementById('add_time_part_base').value;
        var add_time_part_desk = document.getElementById('add_time_part_desc').value;

        $.post('ajax/add_time_part_certain.php', {add_time_part_start_dt: add_time_part_start_dt, add_time_part_stop_dt: add_time_part_stop_dt, 
                add_time_part_base: add_time_part_base, add_time_part_desk: add_time_part_desk, byAlert: byAlert }, RetSWT10);
        function RetSWT10(dat10) {  
          if ( dat10 == 1 ){
            if ( byAlert == 1 ){ 
              update_alerts_page();
            }
            else{
              show_add_time_table( 1 );
            }
          }                        
        }
      }
    }

    if ( document.getElementById('add_time_range').checked ){
      if (
        document.getElementById('add_time_part_base_1') &&
        document.getElementById('add_time_part_desc_1') &&
        document.getElementById('add_time_part_start_date') &&
        document.getElementById('add_time_part_stop_date') &&
        document.getElementById('add_time_part_start_time') &&
        document.getElementById('add_time_part_stop_time') &&
        document.getElementById('exclude_weekend_holidays')
      ) {
        var add_time_part_base = document.getElementById('add_time_part_base_1').value;
        var add_time_part_desk = document.getElementById('add_time_part_desc_1').value;
        var add_time_part_start_date = document.getElementById('add_time_part_start_date').value;
        var add_time_part_stop_date = document.getElementById('add_time_part_stop_date').value;
        var add_time_part_start_time = document.getElementById('add_time_part_start_time').value;
        var add_time_part_stop_time = document.getElementById('add_time_part_stop_time').value;
        var exclude_weekend_holidays = 0;

        if ( document.getElementById('exclude_weekend_holidays').checked ){
          exclude_weekend_holidays = 1;
        }

        if ( add_time_part_start_date == "" || add_time_part_stop_date == "" ){
          alert( "Укажите дату начала и дату окончания диапазона!" );
          return;
        }

        if ( add_time_part_start_time == "" || add_time_part_stop_time == "" ){
          alert( "Укажите время начала и время окончания работ!" );
          return;
        }

        if ( add_time_part_start_date > add_time_part_stop_date ){
          alert( "Дата начала диапазона больше даты окончания диапазона!" );
          return;
        }

        var add_time_part_start_dt = add_time_part_start_date + "T" + add_time_part_start_time;
        var add_time_part_stop_dt = add_time_part_stop_date + "T" + add_time_part_stop_time;

        if ( add_time_part_start_dt == add_time_part_stop_dt ){
          alert( "Длительность работы равна 0!" );
          return;
        }

        if ( add_time_part_start_dt > add_time_part_stop_dt ){
          alert( "Дата и время начала работ больше даты и времени окончания работ!" );
          return;
        }

        if ( add_time_part_start_time >= add_time_part_stop_time ){
          alert( "В диапазоне дат время начала должно быть меньше времени окончания в каждом дне!" );
          return;
        }

        $.post(
          'ajax/add_time_part_range.php',
          {
            add_time_part_start_date: add_time_part_start_date,
            add_time_part_stop_date: add_time_part_stop_date,
            add_time_part_start_time: add_time_part_start_time,
            add_time_part_stop_time: add_time_part_stop_time,
            add_time_part_base: add_time_part_base,
            add_time_part_desk: add_time_part_desk,
            exclude_weekend_holidays: exclude_weekend_holidays,
            byAlert: byAlert
          },
          RetSWT20
        );

        function RetSWT20(dat20) {  
          if ( dat20 == 1 ){ 
            if ( byAlert == 1 ){
              update_alerts_page();
            }
            else{
              show_add_time_table( 1 );
            }
          }
          else {
            alert(dat20);
          }
        }
      }
    }
  }
}

function save_entry (currentDay, startTime) {
  if ( document.getElementById('enter_training_date') && document.getElementById('enter_training_start_time') &&  document.getElementById('enter_training_stop_time')){
    var training_date = document.getElementById('enter_training_date').value;
    var training_start_time = document.getElementById('enter_training_start_time').value;
    var training_stop_time = document.getElementById('enter_training_stop_time').value;

    switch (true) {
      case currentDay > training_date:
        alert( "Дата тренировки некорректная!" );
        break;
      case startTime > training_start_time && currentDay === training_date:
        alert( "Время начала тренировки в прошлом" );
        break;
      case training_stop_time < training_start_time:
        alert("Время окончания тренировки меньше времени начала");
        break;
      default:
        $.post('ajax/change_sum_people.php', {training_date: training_date, training_start_time: training_start_time, training_stop_time: training_stop_time}, RetSWT1);
        function RetSWT1(dat1) {  
          if ( dat1 == 1 ) {
            alert( "На данное время превышен лимит записи! Выберите другую дату/время." );
          }
          else {
            $.post('ajax/gym_add_time.php', {training_date: training_date, training_start_time: training_start_time, training_stop_time: training_stop_time}, RetSWT2);
            function RetSWT2(dat2) {  
            if ( dat2 == 2 ) {
              alert( "Тренировка запланирована!" );
              location.reload();
            }
            else if ( dat2 == 1 ) {
              alert( "На данное время превышен лимит записи! Выберите другую дату/время." );
            }
            else {
              alert( "Ошибка" );
            }
          }                       
        }
      }
    }
  }
}

function delete_training_schedule () {
  $.post('ajax/delete_gym_schedule_window.php', RetSWT1);
  function RetSWT1(dat1) { 
    if ( document.getElementById('delete_gym_schedule_window') ){
      document.getElementById('delete_gym_schedule_window').innerHTML = dat1;
      document.getElementById('delete_gym_schedule_window').style.display='flex';
    }
  }
}

function delete_gym_schedule(date_train, start_time, stop_time) {
  $.post('ajax/delete_training_schedule.php', {date_train: date_train, start_time: start_time, stop_time: stop_time}, RetSWT1);
  function RetSWT1(dat1) {
    if ( dat1 == 2 ) {
      alert( "Тренировка удалена." );
      location.reload();
   }
   else {
     alert( "Ошибка" );
   }
  }
}

function part_time_del( itemId ){
  var perform=confirm('запись будет удалена. Продолжить?')
  if ( perform == true ){
    $.post('ajax/del_time_part.php', {itemId: itemId }, RetSWT);
    function RetSWT(dat) {  
      if ( dat == 1 ){ 
        show_add_time_table( 1 );
      }
    }
  }
}

function set_pause_full_screen(){
  if ( document.getElementById('pauseFullScreen') ){
    win_w = $(window).width();
    win_h = $(window).height();
   
    document.getElementById('pauseFullScreen').style.height = ( win_h - 30 ) + "px"; 
    document.getElementById('pauseFullScreen').style.width = ( win_w - 30 ) + "px";  
  }
}
         
function check_pause_state( force ){
  $.post('ajax/is_there_pause.php', RetSWT);
  function RetSWT(dat) { 
    if ( dat == 0 ){
      if ( force == 1 ){
        window.location=self.location;
      }
      else{
        return;
      }
    } 
    else if ( dat == 1 ){
      $.post('ajax/get_pause_stop_content.php', RetSWT1);
      function RetSWT1(dat1) {
        $("body").html(dat1);  
      }
    }
    else if ( dat == 2 ){
      $.post('ajax/finalize_pause.php', RetSWT2);
      function RetSWT2(dat2) {
        if ( dat2 == 1 ){
          $.post('ajax/get_pause_result_content.php', RetSWT3 );
          function RetSWT3(dat3) {
            if ( document.getElementById('delay_explanation_add_time') ){
              document.getElementById('pause_result_head').innerHTML = dat3;
              document.getElementById('pause_result_head').style.display='block';

              if ( document.getElementById('resultContentTable') ){
                tableHeight = document.getElementById('resultContentTable').offsetHeight + 15;
                
                if ( tableHeight > 500 ){
                  tableHeight = 500;
                }  

                document.getElementById('pause_result_head').style.height = tableHeight + "px"; 
              }
            }
          }
        }
      }
    }
  }
}

function set_pause_header(){
  if ( document.getElementById('pause_head') ){
    document.getElementById('pause_head').style.display='block';
    $.post('ajax/get_pause_content.php', RetSWT);
    function RetSWT(dat) {
      document.getElementById('pause_head').innerHTML = dat;
    }
  }
}

function set_sport_pause() {
  if ( document.getElementById('sport_pause') ){
    document.getElementById('sport_pause').style.display='block'; 
    $.post('ajax/get_pause_sport_content.php', RetSWT);
    function RetSWT(dat){
      document.getElementById('sport_pause').innerHTML = dat;
    }
  }
}

function closeModal() {
  const modal = document.getElementById("remote_work");
  if (modal) {
    modal.style.display = "none";
    modal.innerHTML = '';
  }
}

async function saveRemoteWork() {
  const btn = document.getElementById('saveRemoteBtn');
  const sel = document.getElementById('supervisor');

  if (!sel) {
    alert('Ошибка: элемент выбора руководителя не найден'); 
    return;
  }

  const supervisorId = sel.value;
  if (!supervisorId) {
    alert('Выберите руководителя');
    return;
  }

  if (btn && btn.dataset.processing === '1') return;
  if (btn) {
    btn.dataset.processing = '1';
    btn.disabled = true;
  }

  try {
    const response = await fetch('ajax/remote_work.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8' },
      body: 'supervisor_id=' + encodeURIComponent(supervisorId),
      credentials: 'same-origin'
    });

    const text = await response.text();

    let data;
    try {
      data = JSON.parse(text);
    } catch (e) {
      data = { status: 'error', message: text };
    }

    if (!response.ok) {
      alert('Ошибка сервера: ' + (data.message || response.status));
      return;
    }

    if (data.status === 'success') {
      const employeeId = document.getElementById("employeeId").value;

      if (employeeId) {
        const icon = document.querySelector(`img.work-status[data-emp="${employeeId}"]`);
        if (icon) {
          icon.src = "img/remoteWorkIcon2.png";
          icon.title = "Работает удаленно";
        } else {
          console.warn('Иконка work-status для сотрудника не найдена в DOM, employeeId=', employeeId);
        }
      } else {
        console.warn("employeeId не найден в модале");
      }

      closeModal();
    } else {
      alert('Ошибка: ' + (data.message || 'unknown error'));
    }
  } catch (err) {
    alert('Connection error: ' + err.message);
  } finally {
    if (btn) {
      btn.dataset.processing = '0'; 
      btn.disabled = false;
    }
  }
}

// Новая функция — завершение удалённой работы
async function finishRemoteWork() {
  const btn = document.getElementById('finishRemoteBtn');
  if (!btn) return;

  if (btn.dataset.processing === '1') return;
  btn.dataset.processing = '1';
  btn.disabled = true;

  try {
    const response = await fetch('ajax/remote_work.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8' },
      body: 'action=finish',
      credentials: 'same-origin'
    });

    const text = await response.text();
    let data;
    try {
      data = JSON.parse(text);
    } catch (e) {
      data = { status: 'error', message: text };
    }

    if (!response.ok) {
      alert('Ошибка сервера: ' + (data.message || response.status));
      return;
    }

    if (data.status === 'success') {
      const employeeId = document.getElementById("employeeId") ? document.getElementById("employeeId").value : null;

      if (employeeId) {
        const icon = document.querySelector(`img.work-status[data-emp="${employeeId}"]`);
        if (icon) {
          icon.src = "img/in_work2.png";
          icon.title = "На рабочем месте";
        } else {
          console.warn('Иконка work-status для сотрудника не найдена в DOM, employeeId=', employeeId);
        }
      }
      closeModal();
      alert("Удаленная работа завершена");
    } else {
      alert('Ошибка: ' + (data.message || 'unknown error'));
    }
  } catch (err) {
    alert('Connection error: ' + err.message);
  } finally {
    btn.dataset.processing = '0';
    btn.disabled = false;
  }
}

function remote_work() {
  const container = document.getElementById('remote_work');
  if (!container) return;
  
  container.style.display='block';

  $.ajax({
    url: 'ajax/remote_work.php',
    method: 'GET',
    success: function(html) {
      container.innerHTML = html;
    },  
    error: function(jqXHR) {
      alert('Ошибка загрузки формы: ' + jqXHR.status);
    }
  });
}

document.addEventListener('click', function (e) {
  const t = e.target;

  if (t.id === 'saveRemoteBtn') {
    e.preventDefault();
    saveRemoteWork();
    return;
  }

  if (t.id === 'finishRemoteBtn') {
    e.preventDefault();
    finishRemoteWork();
    return;
  }

  if (t.id === 'closeRemoteBtn') {
    e.preventDefault();
    closeModal();
    return;
  }
});

function resume_from_pause( pauseID ){
  $.post('ajax/resume_from_pause.php', { pauseID: pauseID }, RetSWT );
  function RetSWT(dat) {
    window.location=self.location;
  }
}

function close_pause_result_head(){
  if ( document.getElementById('pause_result_head') ){
    document.getElementById('pause_result_head').style.display='none';
    close_pause();
    check_pause_state();
  }
}

function set_pause_state(){
  if ( document.getElementById('pause_superusers') && document.getElementById('pause_desk') ){
    var superuserID = document.getElementById('pause_superusers').value;
    var desk = document.getElementById('pause_desk').value;

    $.post('ajax/set_pause.php', { superuserID: superuserID, desk: desk }, RetSWT );
    function RetSWT(dat) {
      if ( dat == 1 ){
        close_pause();
        check_pause_state();
      }
      else{
        alert( dat );
      } 
    }
  }
}

function set_pause_sport_state(){
  if ( document.getElementById('pause_desk') ){
    var desk = document.getElementById('pause_desk').value;

    $.post('ajax/set_pause_sport.php', { desk: desk }, RetSWT );
    function RetSWT(dat) {
      if ( dat == 1 ){
        close_sport_pause();
        check_pause_state();
      }
      else{
        alert( dat );
      }
    }
  }
}

function disclamer (state) {
  if (state == "3") {
    alert("Кнопка недоступна. Нажмите кнопку прихода с обеда!");
  }
  if (state === "0") {
    alert("Кнопка недоступна. Ваш рабочий день окончен!");
  }
}

function close_pause(){
  if ( document.getElementById('pause_head') ){
    document.getElementById('pause_head').style.display='none';
  }
}

function close_sport_pause(){
  if ( document.getElementById('sport_pause') ){
    document.getElementById('sport_pause').style.display='none';
  }
}

function make_div_scroll(){
  var horizScrollVal = document.getElementById('report_window').scrollLeft;
  document.getElementById('report_window_head').scrollLeft = horizScrollVal;

  var vertScrollVal = document.getElementById('report_window').scrollTop;
  document.getElementById('report_window_left').scrollTop = vertScrollVal;
}

function make_div_scroll_single(){
  var vertScrollVal = document.getElementById('report_window_single').scrollTop;
  document.getElementById('report_window_left').scrollTop = vertScrollVal;
}

document.addEventListener("DOMContentLoaded", () => {
  attachTooltipListeners();
});

function attachTooltipListeners() {
  const infElements = document.querySelectorAll('.work_time_rep .inf[data-tooltip]');

  infElements.forEach(el => {
    const tooltipId = el.getAttribute('data-tooltip');
    const tooltip = document.querySelector(`.time[data-tooltip-target="${tooltipId}"]`);

    if (!tooltip) {
      return;
    }
    el.addEventListener('mouseover', (event) => showTime(event, tooltip));
    el.addEventListener('mouseout', () => hideTime(tooltip));
    tooltip.addEventListener('mouseleave', () => hideTime(tooltip));
  });
}

function showTime (event, tooltip) {
  const el = event.currentTarget;
  const elRect = el.getBoundingClientRect();
  const scrollTop = window.scrollY || document.documentElement.scrollTop;
  const scrollLeft = window.scrollX || document.documentElement.scrollLeft;

  const tooltipWidth = 270;
  const spaceRight = window.innerWidth - elRect.right;

  tooltip.style.display = 'block';
  tooltip.style.position = 'absolute';
  tooltip.style.maxWidth = '300px';
  tooltip.style.top = `${elRect.top + scrollTop}px`;
  
  if (spaceRight > tooltipWidth + 20) {
    tooltip.style.left = `${elRect.left + scrollLeft + 60}px`;
  } else {
    tooltip.style.left = `${Math.max(elRect.left + scrollLeft - tooltipWidth - 60, 0)}px`;
  }
}

function hideTime (tooltip) {
  tooltip.style.display = 'none';
}

document.addEventListener("DOMContentLoaded", () => {
  bindPhoneTooltips();
});

function bindPhoneTooltips() {
  const phoneElems = document.querySelectorAll('.activ_text[data-phone-tooltip]');

  phoneElems.forEach(el => {
    const tooltipId = el.getAttribute('data-phone-tooltip');
    const tooltip = document.querySelector(`.phone_tooltip[data-phone-tooltip-target="${tooltipId}"]`);

    if (!tooltip) return;

    el.addEventListener('mouseover', () => {
      const rect = el.getBoundingClientRect();
      const scrollTop = window.scrollY || document.documentElement.scrollTop;
      const scrollLeft = window.scrollY || document.documentElement.scrollLeft;

      tooltip.style.display = 'block';
      tooltip.style.position = 'absolute';
      tooltip.style.top = `${rect.bottom + scrollTop + 5}px`;
      tooltip.style.left = `${rect.left + scrollLeft}px`;
    });

    el.addEventListener('mouseout', () => {
      tooltip.style.display = 'none';
    });
  });
}

document.addEventListener('keydown', function(event) {
  if (event.code === 'Enter') {
    event.preventDefault();
    document.getElementById('auth_btn').click();
  }
});

let count = 1;

function show_information() {
  count++;
  document.getElementById('inform').style.display = "block";
  if (count % 2 == 1) {
    document.getElementById('inform').style.display = "none";
  }
}
