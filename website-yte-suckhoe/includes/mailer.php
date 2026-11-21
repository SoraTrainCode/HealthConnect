<?php
function hc_send_mail(string $to,string $subject,string $body): bool
{
 if(!filter_var($to,FILTER_VALIDATE_EMAIL)||!filter_var(cfg('mail_from'),FILTER_VALIDATE_EMAIL)) return false;
 // Gui qua API HTTPS cua Brevo (cong 443) - dung khi hosting chan SMTP (587).
 // Can cau hinh: "mail_transport" => "brevo" va "brevo_api_key" => "..." trong config.local.php.
 // Dia chi mail_from phai da duoc xac thuc (verified sender) trong tai khoan Brevo.
 if(cfg('mail_transport')==='brevo') {
  $apiKey=trim((string)cfg('brevo_api_key'));
  if($apiKey===''||!function_exists('curl_init')) return false;
  $payload=json_encode([
   'sender'=>['name'=>'HealthConnect','email'=>cfg('mail_from')],
   'to'=>[['email'=>$to]],
   'subject'=>$subject,
   'textContent'=>$body,
  ],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  $ch=curl_init('https://api.brevo.com/v3/smtp/email');
  curl_setopt_array($ch,[
   CURLOPT_POST=>true,
   CURLOPT_POSTFIELDS=>$payload,
   CURLOPT_RETURNTRANSFER=>true,
   CURLOPT_TIMEOUT=>15,
   CURLOPT_HTTPHEADER=>['api-key: '.$apiKey,'Content-Type: application/json','Accept: application/json'],
  ]);
  $resp=curl_exec($ch);
  $code=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);
  curl_close($ch);
  if($code===201) return true;
  error_log('HealthConnect: Brevo send failed. HTTP='.$code.' response='.substr((string)$resp,0,500));
  return false;
 }
 require_once __DIR__.'/vendor/PHPMailer/Exception.php';
 require_once __DIR__.'/vendor/PHPMailer/PHPMailer.php';
 require_once __DIR__.'/vendor/PHPMailer/SMTP.php';
 try {
  $mail=new PHPMailer\PHPMailer\PHPMailer(true);
  $mail->CharSet='UTF-8';$mail->Timeout=10;$mail->SMTPDebug=0;
  if(cfg('mail_transport')==='smtp') {
   $mail->isSMTP();$mail->Host=cfg('smtp_host');$mail->Port=(int)cfg('smtp_port');
   $mail->SMTPAuth=cfg('smtp_auth')!=='0';$mail->Username=cfg('smtp_username');$mail->Password = cfg('smtp_password');
   $security=cfg('smtp_security');
   if(!in_array($security,['tls','ssl'],true)) {
    if(!in_array($mail->Host,['127.0.0.1','localhost','::1'],true))return false;
    $mail->SMTPAutoTLS=false;$security='';
   }
   $mail->SMTPSecure=$security;
  }
  $mail->setFrom(cfg('mail_from'),'HealthConnect');$mail->addAddress($to);$mail->Subject=$subject;$mail->Body=$body;
  return $mail->send();
 } catch(Throwable $ex) {error_log('HealthConnect: email transport failed. Check local SMTP settings.');return false;}
}
