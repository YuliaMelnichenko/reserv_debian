function CommReq( req_num )
{
	$.post('ajax/commit_req.php', {num: req_num}, Func);

	function Func(dat) 
	{
		if ( dat.length==0 )
		{
    	window.location=self.location;
		} 
		else
		{
			alert (dat);
		}
	}            
}

function CalcReq( req_num )
{
	$.post('ajax/calc_req.php', {num: req_num}, Func);

	function Func(dat) 
	{
		if ( dat.length==0 )
		{
			$.post('ajax/form_req_mess.php', {num: req_num}, Func1);

			function Func1(dat) 
			{
				$("#calc_report").html(dat);   
				document.getElementById('calc_report').style.display='block';
			}
		}
		else
		{	
			alert (dat);
			window.location=self.location;	
		}
	}
}

function CloseCalcRep()
{
	window.location=self.location;	
}




function News_Init()
{  
	$.post('ajax/news_init.php', Lnc); 
	function Lnc( cont )
	{
		$("#news_add").html(cont);   
		document.getElementById('news_add').style.display='block';
	}
}

function News_add( type )
{  
	$.post('ajax/news_add.php',{ type: type }, Lnc); 
	function Lnc( cont ) 
	{
		$("#news_add").html(cont);   
		document.getElementById('news_add').style.display='block';
	}  
}

function News_save( news_title, news_sh_cont, news_full_cont, type, news_date )
{ 
	$.post('ajax/news_check.php',{ news_title: news_title, news_sh_cont: news_sh_cont, news_full_cont: news_full_cont, type: type, news_date: news_date }, Lnc); 
	function Lnc( cont ) 
	{
		if ( cont != '1' )
		{
			alert(cont);
			return;
		}
		else
		{
			$.post('ajax/news_save.php', Lnc1); 
			function Lnc1( cont ) 
			{
				if ( cont != '1' )
				{
					alert(cont);
					return;
				}
				else
				{	
					alert("Новостное сообщение добавлено");
					document.getElementById('news_add').style.display='none';
				}
			}			
		}
	}  
}

function Art_Init()
{  
	$.post('ajax/art_init.php', Lnc); 
	function Lnc( cont )
	{
		$("#art_add").html(cont);   
		document.getElementById('art_add').style.display='block';
	}   
}

function Art_add( type )
{  
	$.post('ajax/art_add.php',{ type: type }, Lnc); 
	function Lnc( cont ) 
	{
		$("#art_add").html(cont);   
		document.getElementById('art_add').style.display='block';
	}  
}

function Art_save( art_title, art_full_cont, type, art_date, art_categ )
{ 
	$.post('ajax/art_check.php',{ art_title: art_title, art_full_cont: art_full_cont, type: type, art_date: art_date, art_categ: art_categ }, Lnc); 
	function Lnc( cont ) 
	{
		if ( cont != '1' )
		{
			alert(cont);
			return;
		}
		else
		{
			$.post('ajax/art_save.php', Lnc1); 
			function Lnc1( cont ) 
			{
				if ( cont != '1' )
				{
					alert(cont);
					return;
				}
				else
				{	
					alert("Статья добавлена");
					document.getElementById('art_add').style.display='none';
				}
			}			
		}
	} 
}

function News_Art_List( runfirst, target, mode )
{  
	$.post('ajax/news_art_list.php',{ runfirst: runfirst, target: target, mode: mode }, Lnc); 
	function Lnc( cont )
	{
		$("#news_art_list").html(cont);   
		document.getElementById('news_art_list').style.display='block';
	}
}

function News_Art_Remove( id )
{
	req=window.confirm('Подтвердить удаление?')
	if(req)
	{
		$.post('ajax/news_art_remove.php',{ id: id }, Lnc); 
  	function Lnc( cont )
		{
			if ( cont == '1')
			{
      	News_Art_List( 0, 0, 0 );
			}
			else
			{	
				alert( cont );
			}	
		}
	}
}
