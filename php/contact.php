<?php
session_start();


// Function to validate against any email injection attempts
function IsInjected($str)
{
    $injections = array('(\n+)',
                        '(\r+)',
                        '(\t+)',
                        '(%0A+)',
                        '(%0D+)',
                        '(%08+)',
                        '(%09+)');
                        
    $inject = join('|', $injections);
    $inject = "/$inject/i";
    
    if(preg_match($inject,$str))
    {
        return true;
    }
    else
    {
        return false;
    }
}

function send_email($to='', $from='', $subject='', $html_content='', $text_content='', $headers='') 
{ 
    # Setup mime boundary
    $mime_boundary = 'Multipart_Boundary_x'.md5(time()).'x';

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"$mime_boundary\"\r\n";
    $headers .= "Content-Transfer-Encoding: 7bit\r\n";

    $body	 = "This is a multi-part message in mime format.\n\n";

    if (!empty($text_content))
    {
        # Add in plain text version
        $body	.= "--$mime_boundary\n";
        $body	.= "Content-Type: text/plain; charset=\"charset=us-ascii\"\n";
        $body	.= "Content-Transfer-Encoding: 7bit\n\n";
        $body	.= $text_content;
        $body	.= "\n\n";
    }
    
    if (!empty($html_content))
    {
        # Add in HTML version
        $body	.= "--$mime_boundary\n";
        $body	.= "Content-Type: text/html; charset=\"UTF-8\"\n";
        $body	.= "Content-Transfer-Encoding: 7bit\n\n";
        $body	.= $html_content;
        $body	.= "\n\n";
    }
    
    # Attachments would go here
    # But this whole email thing should be turned into a class to more logically handle attachments, 
    # this function is fine for just dealing with html and text content.

    # End email
    $body	.= "--$mime_boundary--\n"; # <-- Notice trailing --, required to close email body for mime's

    # Finish off headers
    $headers .= "From: $from\r\n";
    $headers .= "X-Sender-IP: $_SERVER[SERVER_ADDR]\r\n";
    $headers .= 'Date: '.date('n/d/Y g:i A')."\r\n";

    # Mail it out
    return @mail($to, $subject, $body, $headers);
}	

$your_email = 'nahuelfl@loncartechnologies.com'; // <<=== update to your email address

$name = '';
$visitor_email = '';
$country = '';
$user_message = '';

$name = htmlentities($_POST['name']);
$visitor_email = htmlentities($_POST['email']);
$country = htmlentities($_POST['country']);
$user_message = htmlentities($_POST['message']);

/*if (empty($_SESSION['6_letters_code']) || strcasecmp($_SESSION['6_letters_code'], $_POST['6_letters_code']) != 0)
{
    //Note: the captcha code is compared case insensitively.
    //if you want case sensitive match, update the check above to
    // strcmp()
    $errors .= "\n The captcha code does not match!";
}*/

if(isset($_POST['submit']))
{
    $name = htmlentities($_POST['name']);
    $visitor_email = $_POST['email'];
    $user_message = htmlentities($_POST['message']);

    ///------------Do Validations-------------
    if (empty($name) || empty($visitor_email))
    {
        $errors .= "\n Name and Email are required fields. ";	
    }
    
    if (IsInjected($visitor_email))
    {
        $errors .= "\n Bad email value!";
    }

    // reCaptcha validation
    $url = 'https://www.google.com/recaptcha/api/siteverify';

    $data = array(
        'secret' => "6Ld767EUAAAAAOVmu8IwM098GvhTzKSUtUdu17Qu",
        'response' => $_POST["g-recaptcha-response"]
    );

    $options = array(
        'http' => array (
            'method' => 'POST',
            'content' => http_build_query($data)
        )
    );

    $context  = stream_context_create($options);
		$verify = file_get_contents($url, false, $context);
		$captcha_success=json_decode($verify);

    if ($captcha_success->success == false) 
    {
        $errors .= "\n The captcha code does not match!";
    } 

    if (empty($errors))
    {
        //send the email
        $to = $your_email;
        $subject = "New contact submission";
        $from = $your_email;
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        
        $body = "A user $name submitted the contact form:\n\n".
            "Name: $name\n".
            "Email: $visitor_email \n".
            "Message: \n ".
            "$user_message\n";
            "IP: $ip\n";
        
        $html = "A user <b>$name</b> submitted the contact form: <br><br>".
            "<b>Name</b>: $name <br>".
            "<b>Email</b>: $visitor_email <br>".
            "<b>Message</b>: <br>".
            "<i>$user_message</i><br>";
            "<b>IP:</b> $ip<br><br>";
        
        send_email($to, $from, $subject, $html, $body);
        
        //-------------------------------------------
        // Redirect browser
        $url = "http://" . $_SERVER['SERVER_NAME'] . "/message-sent.html";
        header("Location: $url");
        die();
        //-------------------------------------------
    }
    else
        echo "<p>" . $errors . "</p>";
}
else
    echo "<p> fail </p>";
?>