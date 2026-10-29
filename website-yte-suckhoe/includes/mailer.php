<?php
function hc_send_mail(string $to,string $subject,string $body): bool
{
 if(!filter_var($to,FILTER_VALIDATE_EMAIL)||!filter_var(cfg('mail_from'),FILTER_VALIDATE_EMAIL)) return false;
 require_once __DIR__.'/vendor/PHPMailer/Exception.php';
 require_once __DIR__.'/vendor/PHPMailer/PHPMailer.php';
 require_once __DIR__.'/vendor/PHPMailer/SMTP.php';
 try {
  $mail=new PHPMailer\PHPMailer\PHPMailer(true);
  $mail->CharSet='UTF-8';$mail->Timeout=10;$mail->SMTPDebug=0;
  if(cfg('mail_transport')==='smtp') {
   $mail->isSMTP();$mail->Host=cfg('smtp_host');$mail->Port=(int)cfg('smtp_port');
   $mail->SMTPAuth=cfg('smtp_auth')!=='0';$mail->Username=cfg('smtp_username');$mail->Password=cfg('smtp_password');
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
