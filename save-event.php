<?php

error_reporting(E_ALL);
ini_set("display_errors", 1);

require dirname(__FILE__) . '/utils.php';
require_once 'ews.php';
set_include_path(get_include_path() . PATH_SEPARATOR . "/usr/share/php");
    
require_once "Mail.php";
require_once "Mail/mime.php";

$start = parseDateTime($_POST['start']);
$end = parseDateTime($_POST['end']);
$name = $_POST['name'];
$title = $_POST['title'];
$emailAddress = $_POST['emailAddress'];
$skypeId = $_POST['skypeId'];
$clientTimezone = $_POST['clientTimezone'];
$myEmailAddress = 'd.hilgarth@fire-development.com';
$startInClientTimezone = $_POST['startInClientTimezone'];
$endInClientTimezone = $_POST['endInClientTimezone'];

$body = 'Name: ' . $name . '<br/>' .
        'Email address: ' . $emailAddress . '<br/>' .
        'Skype ID: ' . $skypeId . '<br/>' .
        'Timezone: ' . $clientTimezone . '<br/>' .
        'Selected start time: ' . $startInClientTimezone . '<br/>' .
        'Selected end time: ' . $endInClientTimezone;

$ews->addCalendarEvent($title, $body, $start, $end, array($emailAddress, $myEmailAddress), null, null, false, 'HTML', 'Web calendar');

$headers = array ('From' => 'Self service call scheduler <email2>',
                  'To' => $myEmailAddress,
                  'Subject' => 'A call has been scheduled');

$mail = new Mail_mime(array("text_charset" => "utf-8",
                            "html_charset" => "utf-8",
                            "eol" => "\n"));
$body = '<div style="font-family: Calibri,sans-serif; font-size: 16; line-height: 1.25em;">' . 
            'Start: ' . $start->format('r') . '<br/>' .
            'End: ' . $end->format('r') . '<br/>' .
            $body .
        '</div>';
// set email body
$mail->setHTMLBody($body);

// prepare headers
foreach ($headers as $name => $value){
    $headers[$name] = $mail->encodeHeader($name, $value, "utf-8", "quoted-printable");
}
// also encode to value
$email_to = $mail->encodeHeader("to", $myEmailAddress, "utf-8", "quoted-printable");
// fetch message
$msgDone = $mail->get();
// let Mail_Mime finish the headers (adds e.g. MIME info)
$headers_done = $mail->headers($headers);
// send the email
$mailSend = Mail::factory('smtp',
                          array('host' => 'smtp.office365.com',
                                'port' => '587',
                                'auth' => true,
                                'username' => '<username2>',
                                'password' => '<password2>'));
$result = $mailSend->send($email_to, $headers_done, $msgDone);
var_dump($result);
?>