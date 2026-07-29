var timerIdSessValid = setInterval(check_sess, 500000);

function check_sess(){
  $.ajax({
    url: 'ajax/sync_current_period.php',
    method: 'POST',
    dataType: 'json',
    success: function(dat) {
      if (!dat || dat.valid != 1) {
        window.location = self.location;
        return;
      }

      if (dat.refreshTimeRegistration == 1) {
        if (dat.stopDTStr) {
          window.toriStopDTStr = dat.stopDTStr;
        }

        get_time_registration_div_content();
        build_in_delay_expl();

        if (typeof update_clock === 'function') {
          update_clock();
        }
      }
    },
    error: function() {
      window.location = self.location;
    }
  });
}

function hide_day_change_layers(){
  var layerDiv = document.getElementById('layer_div');
  var layerQuestionDiv = document.getElementById('layer_question_div');

  if ( layerDiv ){
    layerDiv.style.display='none';
  }

  if ( layerQuestionDiv ){
    layerQuestionDiv.style.display='none';
  }
}

function check_day_change(){
  hide_day_change_layers();

  $.post('ajax/check_day_change.php', RetSWT);
  function RetSWT(dat){
    if ( dat == 1 ){
      clearInterval( timerIdDayChange );
      hide_day_change_layers();
    }
  }
}

var timerIdDayChange = setInterval(check_day_change, 3000);

function day_continue_confirm()
{
  $.post('ajax/day_continue_confirm.php', RetSWT);
  function RetSWT(dat)
  {
    hide_day_change_layers();

    window.location=self.location;
  }
}

function day_continue_reject()
{
  $.post('ajax/day_continue_reject.php', RetSWT);
  function RetSWT(dat)
  {
    hide_day_change_layers();

    window.location=self.location;
  }
}

$(document).ready(function()
{
  var hidden, visibilityState, visibilityChange;

  if (typeof document.hidden !== "undefined")
  {
    hidden = "hidden", visibilityChange = "visibilitychange", visibilityState = "visibilityState";
  }
  else if (typeof document.msHidden !== "undefined")
  {
    hidden = "msHidden", visibilityChange = "msvisibilitychange", visibilityState = "msVisibilityState";
  }

  var document_hidden = document[hidden];

  document.addEventListener(visibilityChange, function()
  {
    if(document_hidden != document[hidden])
    {
      if(document[hidden])
      {
        //alert('hidden');
      }
      else
      {
        check_sess();
      }

      document_hidden = document[hidden];
    }
  });
});

check_pause_state();

function st_month_inc()
{
  $.post('ajax/stat_month_inc.php', RetSWT);
  function RetSWT(dat)
    {
      window.location=self.location;
    }
}

function st_month_dec()
{
  $.post('ajax/stat_month_dec.php', RetSWT);
  function RetSWT(dat)
  {
    window.location=self.location;
  }
}

function st_month_def()
{
  $.post('ajax/stat_month_def.php', RetSWT);
  function RetSWT(dat)
  {
    window.location=self.location;
  }
}

function get_time_registration_div_content()
{
  $.post('ajax/get_time_registration_div.php', RetSWT6 );
  function RetSWT6(dat6)
  {
    if ( document.getElementById('time_registration_div') ){ document.getElementById('time_registration_div').innerHTML = dat6; }
  }
}

// function switch_day_state( next ) {
//   $.post('ajax/switch_day_state.php', { next: next }, RetSWT);
//   function RetSWT(dat) {
//     if ( dat == 1 ){
//       get_time_registration_div_content();
//     } else {
//       alert( dat );
//     }
//   }
//   build_in_delay_expl();
// }

function switch_day_state(next, callback) {
  $.post('ajax/switch_day_state.php', {next: next}, function(dat) {
    if (dat.trim() === "1") {
      if (typeof callback === 'function') callback();
      else get_time_registration_div_content();
    } else {
      alert(dat);
    }
  });
  build_in_delay_expl();
}

function rollback_state()
{
  var perform=confirm('будет осуществлен возврат к предыдущему состоянию регистрации времени. Продолжить?')
  if ( perform == true )
  {
    switch_day_state(0, function() {
      window.location.reload();
    });
  }
}

function reg_in_work_with_delay()
{
  reg_in_work();

  set_delay();
}

function reg_in_work()
{
  switch_day_state( 1 );
}

function reg_out_work()
{
  switch_day_state( 1 );
}

function reg_eat_start() {
  switch_day_state(1, function() {
    $.get('ajax/set_lunch.php', function(html) {
      $('#lunchPauseFullScreen').remove();

      if ($.trim(html) !== '') {
        $('body').append(html);
      }
      else {
        get_time_registration_div_content();
      }
    });
  });
}

function reg_eat_stop() {
  switch_day_state(1, function() {
    $('#lunchPauseFullScreen').remove();
    get_time_registration_div_content();
    build_in_delay_expl();

    if (typeof update_clock === 'function') {
      update_clock();
    }
  });
}

$(document).ready(function() {
  $.get('ajax/get_current_state.php', function(state) {
    if (parseInt(state, 10) === 3) {
      $.get('ajax/set_lunch.php', function(html) {
        $('#lunchPauseFullScreen').remove();

        if ($.trim(html) !== '') {
          $('body').append(html);
        }
      });
    }
    else {
      $('#lunchPauseFullScreen').remove();
    }
  });
});

function add_expl()
{
  $.post('ajax/get_add_time_notif_count.php', RetSWT2);
  function RetSWT2(dat2)
  {
    if ( document.getElementById('notifBtn') )
    {
      document.getElementById('notifBtn').innerHTML = dat2;
    }
  }
  $.post('ajax/get_delay_notif_count.php', RetSWT2);
  function RetSWT2(dat2)
  {
    if ( document.getElementById('notifDelayBtn') )
    {
      document.getElementById('notifDelayBtn').innerHTML = dat2;
    }
  }

  $.post('ajax/get_explanation_head.php', RetSWT1);
  function RetSWT1(dat1)
  {
    if ( document.getElementById('delay_explanation_head') )
    {
      document.getElementById('delay_explanation_head').innerHTML=dat1;
      document.getElementById('delay_explanation_head').style.display='block';
    }
  }
}

function add_training_time()
{
  $.post('ajax/get_add_gym_time.php', RetSWT1);
  function RetSWT1(dat1)
  {
    if ( document.getElementById('delay_explanation_sport_time') )
    {
      document.getElementById('delay_explanation_sport_time').innerHTML = dat1;
      document.getElementById('delay_explanation_sport_time').style.display='block';
    }
  }
}

function close_add_sport_time()
{
  if ( document.getElementById('delay_explanation_sport_time') ){ document.getElementById('delay_explanation_sport_time').style.display='none'; }
}

function enter_out_time(){
  $.post('ajax/get_out_time.php', RetSWT1);
  function RetSWT1(dat1) {
    if ( document.getElementById('delay_out_time') ){
      document.getElementById('delay_out_time').innerHTML=dat1;
      document.getElementById('delay_out_time').style.display='flex';
    }
  }
}

function enter_stop_eat_time()
{
  $.post('ajax/get_eat_stop.php', RetSWT1);
  function RetSWT1(dat1)
  {
    if ( document.getElementById('delay_out_time') )
    {
      document.getElementById('delay_out_time').innerHTML=dat1;
      document.getElementById('delay_out_time').style.display='flex';
    }
  }
}

function close_out_time()
{
  if ( document.getElementById('delay_out_time') ){ document.getElementById('delay_out_time').style.display='none'; }
}

function as_add_time()
{
  $.post('ajax/get_add_times.php', RetSWT1);
  function RetSWT1(dat1)
  {
    if ( document.getElementById('delay_explanation_head') ){ document.getElementById('delay_explanation_head').style.display='none'; }
    if ( document.getElementById('delay_explanation_add_time') )
    {
      document.getElementById('delay_explanation_add_time').innerHTML = dat1;
      document.getElementById('delay_explanation_add_time').style.display='block';
    }

    $.post('ajax/get_add_time_notif_count.php', RetSWT2);
    function RetSWT2(dat2)
    {
      if ( document.getElementById('notifBtn') )
      {
        document.getElementById('notifBtn').innerHTML = dat2;
      }
    }
    $.post('ajax/get_delay_notif_count.php', RetSWT2);
    function RetSWT2(dat2)
    {
      if ( document.getElementById('notifDelayBtn') )
      {
        document.getElementById('notifDelayBtn').innerHTML = dat2;
      }
    }
  }
  if ( document.getElementById('delay_explanation_head') ){ document.getElementById('delay_explanation_head').style.display='none'; }
  if ( document.getElementById('delay_explanation_add_time') ){ document.getElementById('delay_explanation_add_time').style.display='block'; }
}

function close_explanation_head()
{
  if ( document.getElementById('delay_explanation_head') ){ document.getElementById('delay_explanation_head').style.display='none'; }
}

function build_in_delay_expl()
{
  $.post('ajax/get_delay_explanation_build_in.php', RetSWT2 );
  function RetSWT2(dat2)
  {
    if ( document.getElementById('delay_explanation_buildin') ){ document.getElementById('delay_explanation_buildin').innerHTML = dat2; }
    if ( document.getElementById('delay_explanation_delay') ){ document.getElementById('delay_explanation_delay').style.display = 'none'; }
  }
}
function build_in_add_work()
{
/*  $.post('ajax/get_add_time_build_in.php', RetSWT2 );
  function RetSWT2(dat2)
  {
    if ( document.getElementById('add_work_buildin') ){ document.getElementById('add_work_buildin').innerHTML = dat2; }
  }*/
}

function as_delay()
{
  if ( document.getElementById('delay_explanation_head') ){ document.getElementById('delay_explanation_head').style.display='none'; }
  if ( document.getElementById('delay_explanation_delay') ){ document.getElementById('delay_explanation_delay').style.display='block'; }

  $.post('ajax/get_delay_explanation.php', {}, RetSWT2 );
  function RetSWT2(dat2)
  {
    if ( document.getElementById('delay_explanation_delay') )
    {
      document.getElementById('delay_explanation_delay').innerHTML = dat2;
      if ( document.getElementById('explAddInfo') )
      {
        var blockHeight = document.getElementById('delay_explanation_delay').offsetHeight - 15;
        var addHeight = document.getElementById('explAddInfo').offsetHeight;

        document.getElementById('delay_explanation_delay').style.height = blockHeight + addHeight  + "px";
      }
    }
  }
}

build_in_delay_expl();
build_in_add_work();
get_time_registration_div_content();

function update_clock(){
  $.post('ajax/get_current_day_time.php', RetSWT);
  function RetSWT(dat)
  {
    if ( document.getElementById('dateTimeFieldNav') )
    {
      document.getElementById('dateTimeFieldNav').innerHTML = dat;
    }
  }
}

var timerId = setInterval(update_clock, 10000);
