# openkm-client
A generic unofficial REST API client for PHP for connecting with an OpenKM Server.


http://github.com/ruvart/openkm-client  
Ruvart <contacto@ruvart.com>


I created this client/library because I couldn't find the official one. Every single link I tried to follow in the OpenKM documentation for the PHP library threw an error, a missing file, or something similar.

Not all the API endpoints that OpenKM creates have been implemented in this client because I made it in a hurry and only implemented the ones that I needed for my project. However, I hope this library can still be helpful to you.

For any issues:
http://github.com/ruvart/openkm-client/issues

To be honest, I will only attend to them when I have time. I hope you can understand.

`*Note:` The file name must contain the full route plus the file name or only the file's ID that OpenKM generates.



Installation
-----------
PHP 8.0 or above.

``` sh
$ composer require ruvart/openkm-client
```


Basic Usage
-----------
``` php
$okmClient = new OpenKMClient($url,$user,$password, true);

//This funtion gets the route to the users folder (OpenKM User), it's the equivalent to C:\Users\user-name
//You can user any valid route if you don't wanna use the user's folder
$root = $okmClient->getRoot();

//Is a valid file
if ( $okmClient->documentIsValid($root . $file_name) ) {
    echo "It's a valid file";
}
else {
    echo "It's not a valid file";
}


//Filezise
$properties = $okmClient->documentGetProperties($root . $file_name);
if ( !empty($properties) && !empty($properties['actualVersion']) && !empty($properties['actualVersion']['size']) ) {
    echo $properties['actualVersion']['size'];
}


//Mime info
$properties = $okmClient->documentGetProperties($root . $file_name);
if ( !empty($properties) && !empty($properties['mimeType']) ) {
    echo $properties['mimeType'];
}


//Uploads a file
$okmClient->documentCreateSimple($root . $file_name, $route_to_local_file);


//rename a file
$okmClient->documentRename($root . $file_name, $only_new_name);


//copy a file
$okmClient->documentCopy($root . $file_name, $destiny_route);


//Stream a file
$okmClient->documentGetContent($root . $file_name, 'PHP:output');


//Deletes a file
$okmClient->documentDelete($root . $file_name);


//Make folder / dir
$okmClient->folderCreateSimple($root . $folder_name);
```