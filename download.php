<?php 
if(empty($_GET['path']) || empty($_GET['name']))exit('缺少参数');
function downfile()
{
 $filename=realpath(".".$_GET['path']); //文件名

 $date=date("Ymd-H:i:m");

 Header( "Content-type:  application/octet-stream "); 

 Header( "Accept-Ranges:  bytes "); 

 Header( "Accept-Length: " .filesize(".".$_GET['path']));

 header( "Content-Disposition:  attachment;  filename= ".$_GET['name']); 

 echo file_get_contents(".".$_GET['path']);

 readfile(".".$_GET['path']); 
 
}

downfile();
?>