<?php
// Nagios Notifier
// @author Rich Adams (http://richadams.me)

// Quick notifier for Nagios. I was sick of getting SMS all the time when an IM would do.
// Sends an IM if it can, otherwise it triggers an email.

////////////////////////////////////////////////////////////////////////////////////////////////////
// Configuration

// Jabber server configuration
$XMPP['server']   = "****";
$XMPP['port']     = "5222";
$XMPP['username'] = "****";
$XMPP['password'] = "****";
$XMPP['domain']   = "****";
$XMPP['resource'] = "NagNot";

// Acknowledgement timeout in seconds
$ACK_TIMEOUT = 60;

// The different status states in which an IM will be sent. If user status is not one of these, an
// email will be sent instead.
$CONFIG['notify_status'] = array("chat", "available", "away", "xa", "dnd");

////////////////////////////////////////////////////////////////////////////////////////////////////
// Get user input

function usage($exit_code = 0)
{
    echo "Usage: nagnot.sh  -i <im_address> -e <email_to_notify> -m <message> -s <subject>\n";
    echo "    Example: nagnot.sh -i test@example.com -e yournum@txt.att.net -m 'Something broke' -s 'Alert!'\n";
    exit($exit_code);
}

$options = getopt("i:e:m:s:");

// Check required parts
if (!isset($options["i"])) { echo "No IM address specified.\n"; usage(1); }
if (!isset($options["e"])) { echo "No email specified.\n"; usage(1); }
if (!isset($options["m"])) { echo "No message specified.\n"; usage(1); }
if (!isset($options["s"])) { echo "No subject specified.\n"; usage(1); }

// Set input.
$INPUT['email']   = $options["e"];
$INPUT['im']      = $options["i"];
$INPUT['subject'] = $options["s"];
$INPUT['message'] = $options["m"];

////////////////////////////////////////////////////////////////////////////////////////////////////
// Functions

// Outputs a message 
function output($m)
{
    echo "[".date("Y-m-d h:i:s", mktime())."] ".$m."\n";
}

////////////////////////////////////////////////////////////////////////////////////////////////////
// Do the work

include_once("xmpphp/XMPP.php");

output("Received instruction - (im:".$INPUT['im'].",email:".$INPUT['email'].",subject:".$INPUT['subject'].",message:".$INPUT['message'].")");

$im_sent = false;

// Try an IM
try 
{
    // Attempt to connect and get user roster.
    $conn = new XMPPHP_XMPP($XMPP['server'], 
                            $XMPP['port'], 
                            $XMPP['username'], 
                            $XMPP['password'], 
                            $XMPP['resource'], 
                            $XMPP['domain']);

    output("Connecting to IM server....");
    $conn->connect();
    $conn->processUntil('session_start');
    $conn->presence();
    output("Retrieving roster....");
    $conn->getRoster();    
    $roster = $conn->roster;
    
    // No specific event to signify end, so use timer instead. 3s should be enough.    
    $conn->processTime(3);
    
    // Determine status of user.
    $active = $roster->getPresence($INPUT['im']);
    output("User ".$INPUT['im']." state is '".$active['show']."'....");
    $online = in_array($active['show'], $CONFIG['notify_status']);
    
    // If they're online, then we're all good.
    if ($online)
    {
        output("Notifying user via IM....");
        $conn->message($INPUT['im'], 
                       $INPUT['subject']."\n".$INPUT['message']);

        // Wait for an acknowledgement (any message sent back)
        $payload = $conn->processUntil('message', $ACK_TIMEOUT);

        // non-zero payload array length means a message was received
        $im_sent = count(payload);
    }
    $conn->disconnect();
} 
catch(XMPPHP_Exception $e)
{
    output("XMPP Exception");
}

// If no IM sent, trigger a mail instead.
if (!$im_sent)
{
    output("IM not sent, notifying user via email to '".$INPUT['email']."'....");
    
    $headers = 'From: nagios@'.$XMPP['domain']."\r\n".
               'Reply-To: do-not-reply@'.$XMPP['domain']."\r\n".
               'X-Mailer: NagNot';
    $result = mail($INPUT['email'], $INPUT['subject'], $INPUT['message'], $headers);
    output("Email sent status: ".($result ? "sent":"error"));
}
else
{
    output("No email to be sent. Notified via IM.");  
}
