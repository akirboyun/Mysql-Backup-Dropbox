<?php
error_reporting(E_ALL);
include 'php_class/DBBackup.class.php';   // DBBackup Dosyası içeri alındı

$sql_dosya_isimlendirme="backup/new_backup.sql"; // Yedeklenen SQL dosyası klasör ve ismi


/**
  Bağlantı Ayarları
*/
$db = new DBBackup
(
  array(
      'driver' => 'mysql',
      'host' => 'DATABASE HOST',  // Localhost
      'user' => 'DATABASE USER',
      'password' => 'DATABASE PASSWORD',
      'database' => 'DATABASE NAME'
    )
);
$backup = $db->backup(); 
if(!$backup['error'])
{
    /**
      Oluşturulan Dosya
    */
   $fp = fopen($sql_dosya_isimlendirme, 'a+');
         fwrite($fp, $backup['msg']);
         fclose($fp);
  //echo nl2br($backup['msg']); // Ekranda Görmek İstiyorsanız
} 
else 
{
  echo 'Bir Hata Oluştu.';
}



require_once("dropbox_class/DropboxClient.php"); //Dropbox api dosyası içeri alındı


/**
  Dropbox Uygulama bağlantı bilgileri
*/
$dropbox = new DropboxClient(array(
 'app_key' => "YOUR API KEY", 
 'app_secret' => "YOUR SECRET KEY",
 'app_full_access' => false,
),'en');

handle_dropbox_auth($dropbox); // see below


/**
  Upload Edilecek dosya bilgileri
*/

 $upload_name = $sql_dosya_isimlendirme;
 echo "<pre>";
 echo "\r\n\r\n<b>Uploading $upload_name:</b>\r\n";
 $meta = $dropbox->UploadFile($sql_dosya_isimlendirme, $upload_name);
 print_r($meta);
 echo "</pre>";
unlink($sql_dosya_isimlendirme); // Oluşturduğumuz Dosyayı Siliyoruz

// ================================================================================
// store_token, load_token, delete_token are SAMPLE functions! please replace with your own!
function store_token($token, $name)
{
 file_put_contents("tokens/$name.token", serialize($token));
}

function load_token($name)
{
 if(!file_exists("tokens/$name.token")) return null;
 return @unserialize(@file_get_contents("tokens/$name.token"));
}

function delete_token($name)
{
 @unlink("tokens/$name.token");
}
// ================================================================================

function handle_dropbox_auth($dropbox)
{
 // first try to load existing access token
 $access_token = load_token("access");
 if(!empty($access_token)) {
  $dropbox->SetAccessToken($access_token);
 }
 elseif(!empty($_GET['auth_callback'])) // are we coming from dropbox's auth page?
 {
  // then load our previosly created request token
  $request_token = load_token($_GET['oauth_token']);
  if(empty($request_token)) die('Request token not found!');
  
  // get & store access token, the request token is not needed anymore
  $access_token = $dropbox->GetAccessToken($request_token); 
  store_token($access_token, "access");
  delete_token($_GET['oauth_token']);
 }

 // checks if access token is required
 if(!$dropbox->IsAuthorized())
 {
  // redirect user to dropbox auth page
  $return_url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']."?auth_callback=1";
  $auth_url = $dropbox->BuildAuthorizeUrl($return_url);
  $request_token = $dropbox->GetRequestToken();
  store_token($request_token, $request_token['t']);
  die("Authentication required. <a href='$auth_url'>Click here.</a>");
 }
}