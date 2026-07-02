<html  onload = function()>
  <head>
    <meta charset="utf-8">
    <title>Т</title>
  </head>
  <body>
    <div id="tmpdiv" style="display: none"> content  </div>
  </body>
  <script type="text/javascript" charset="utf-8"> 
    window.onload = function(){
	    alert("Hello!");
      var elem = document.getElementById("tmpdiv");
        elem.style.display = 'block';
    }
  </script> 
</html>