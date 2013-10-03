<?php
require_once('EasyDownloader.php');

error_reporting(0);

// example : path/index.php?path=images/spiderman.jpg

$config = array(
     //'siteUrl' => '',
     //'fileName' => '',
     'filePath' => $_GET['path'],
     'queryString' => '?path=', // default ?path=
     'resume' => true, // default true
     'maxSpeed' => 20, // default 40
     'maxConnection' => 9, // default 9 - max connection support from server
);

$users = array(
     'admin' => array('username' => 'admin', 'password' => 'admin', 'maxSpeed' => 100, 'resume' => true),
     'guest' => array('username' => 'guest', 'password' => 'guest', 'maxSpeed' => 20, 'resume' => false),
);

$ED = new EasyDownloader( $config );

$ED->login( $users );

//echo $ED->getFileSize();  get file size by byte

//echo $ED->kiloByteToMegaByte($ED->byteToKiloByte($ED->getFileSize()));      get file size by mega byte

//echo $ED->byteToKiloByte($ED->getFileSize());  get file size by kilobyte

//echo $ED->byteToMegaByte($ED->getFileSize());  get file size by mega byte

//echo $ED->getFileTime(); get file time

//echo $ED->getMimeType(); get file mime type

$ED->startDownload();    //start download
