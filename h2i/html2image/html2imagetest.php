<?php

#this example issues a http request to the mini webserver.
#which in turn will calll html2image linux to generate the image
#for the url given by para.
#make sure your output folder is writable.

#Here assume nweb is running with php on the same server. 
#you can change the IP below if it is not the case
$lines = file("http://127.0.0.1:8181/para=www.google.com&/temp/out.jpg");
echo "check the /temp/out.jpg is created or not\n"
?>
