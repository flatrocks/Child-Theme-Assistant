<?php
error_reporting(E_ALL  ^  E_NOTICE  ^  E_DEPRECATED);

require_once  'PHP/CompatInfo.php';

$source  =  dirname(__FILE__)  .  DIRECTORY_SEPARATOR  .  'child_theme_assistant.php';

$info  =  new  PHP_CompatInfo();
echo  '<pre>';
$info->parseFile($source);
echo  '</pre>';
//  you  may  also  use  unified  method:  $info->parseData($source);

/*
function  recurse_zip($src,&$zip,$path_length)  {
  $dir  =  opendir($src);
  while(false  !==  (  $file  =  readdir($dir))  )  {
  if  ((  $file  !=  '.'  )  &&  (  $file  !=  '..'  ))  {
  if  (  is_dir($src  .  '/'  .  $file)  )  {
  recurse_zip($src  .  '/'  .  $file,$zip,$path_length);
  }
  else  {
  $zip->addFile($src  .  '/'  .  $file,substr($src  .  '/'  .  $file,$path_length));
  }
  }
  }
  closedir($dir);
}
//Call  this  function  with  argument  =  absolute  path  of  file  or  directory  name.
function  compress($src)
{
  if(substr($src,-1)==='/'){$src=substr($src,0,-1);}
  $arr_src=explode('/',$src);
  $filename=end($src);
  unset($arr_src[count($arr_src)-1]);
  $path_length=strlen(implode('/',$arr_src).'/');
  $f=explode('.',$filename);
  $filename=$f[0];
  $filename=(($filename=='')?  'backup.zip'  :  $filename.'.zip');}
  $zip  =  new  ZipArchive;
  $res  =  $zip->open($filename,  ZipArchive::CREATE);
  if($res  !==  TRUE){
  echo  'Error:  Unable  to  create  zip  file';
  exit;}
  if(is_file($src)){$zip->addFile($src,substr($src,$path_length));}
  else{
  if(!is_dir($src)){
  $zip->close();
  @unlink($filename);
  echo  'Error:  File  not  found';
  exit;}
  recurse_zip($src,$zip,$path_length);}
  $zip->close();
  header("Location:  $filename");
  exit;
}
*/

function  Zip($source,  $destination)
{
  if  (extension_loaded('zip')  ===  true) {
    if  (file_exists($source)  ===  true) {
      $zip  =  new  ZipArchive();
      
      if  ($zip->open($destination,  ZIPARCHIVE::CREATE)  ===  true) {
        $source  =  realpath($source);
        if  (is_dir($source)  ===  true) {
          $files  =  new  RecursiveIteratorIterator(new  RecursiveDirectoryIterator($source),  RecursiveIteratorIterator::SELF_FIRST);
          foreach  ($files  as  $file) {
            $file  =  realpath($file);
            if  (is_dir($file)  ===  true) {
              $zip->addEmptyDir(str_replace($source  .  '/',  '',  $file  .  '/'));
            }
            else  if  (is_file($file)  ===  true) {
              $zip->addFromString(str_replace($source  .  '/',  '',  $file),  file_get_contents($file));
            }
          }
        } elseif (is_file($source)  ===  true) {
          $zip->addFromString(basename($source),  file_get_contents($source));
        }
        return  $zip->close();
      } 
    }
  }
  return  false;
}
