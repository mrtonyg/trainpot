<?php
$db = 'db/messageflow.db';

class MyDB extends SQLite3
{
    function __construct()
    {
        $this->open('/home/phxis/public_html/messageflow/db/messageflow.db');
    }
}

$mdb = new MyDB();
require_once "lib/flowroute-sdk-v3-php/vendor/autoload.php";
require_once "lib/flowroute-sdk-v3-php/src/Configuration.php";

use FlowrouteNumbersAndMessagingLib\Models;


$username = '32627395'; //getenv('FR_ACCESS_KEY', true) ?: getenv('FR_ACCESS_KEY');
$password = '93fb827e61fc44ba51ac8a1b6cf301d6'; //getenv('FR_SECRET_KEY', true) ?: getenv('FR_SECRET_KEY');
$logfile = 'log/messageflow.log';
$db = 'db/messageflow.db';
// Instantiate API client and authenticate
$client = new FlowrouteNumbersAndMessagingLib\FlowrouteNumbersAndMessagingClient($username, $password);


// no matter what, we're returning JSON to the caller
header('Content-Type: application/json; charset=utf-8');

// pull the url segments apart for the path, op, key, endpoint number, subop

$_SERVER['REQUEST_URI_PATH'] = preg_replace('/\?.*/', '', $_SERVER['REQUEST_URI']);
$urlsegments = explode('/', trim($_SERVER['REQUEST_URI_PATH'], '/'));
$requestType = $_SERVER['REQUEST_METHOD'];
if ($urlsegments[0] == 'messageflow') { // we're in the right place, carry on
    $op = $urlsegments[1]; // message.svc or incoming.svc
    $key = $urlsegments[2]; // accountkey
    if ($op == 'message.svc' && $requestType == 'POST') { // being asked to send a message
        $jsondata = file_get_contents('php://input');
        $messageData = json_decode($jsondata, true);
        $mobileNumber = $urlsegments[3];
        $subop = $urlsegments[4];
        $MessageBody = $messageData['MessageBody'];
        $Reference = $messageData['Reference'];

        //here's where we should write the outgoing message to queue, or send direct,
        $sql = "INSERT INTO messageflow (AccountKey,MobileNumber,Message,Reference,SentFlag,SeenFlag,Direction,Stamp) VALUES ('" . $key . "','" . $mobileNumber . "','" . $MessageBody . "','" . $Reference . "','0','0','out','" . date('YmdHis') . "')";
//	$sql="SELECT name                                   FROM sqlite_master                                   WHERE type = 'table'                                   ORDER BY name";

//	$mdb= new MyDB();
        $ret = $mdb->exec($sql);

        if (!$ret) {
            addLogMessage($mdb->lastErrorMsg());
        } else {
            addLogMessage("Records created successfully");
        }
//	while($row=$ret->fetchArray(SQLITE3_ASSOC) ) {
//		echo $row['name']."\n";
//	}
//	$mdb->close();


        addLogMessage($op . '-' . $subop . '-' . $mobileNumber . '-' . $Reference . "\n");


        //return something pleasant to the caller
        $good_return = array("SendMessageWithReferenceExtendedResult" => ["ErrorMessage" => "", "MessageID" => 745026146, "MessagesRemaining" => 1491, "QueuedSuccessfully" => true]);
        echo json_encode($good_return) . "\n\n";
    }
    if ($op == 'message.svc' && $requestType == 'GET') { // being asked for waiting incoming messages
        $subop = $urlsegments[3];
        $checkid = $urlsegments[4];

        //here's where we should incoming message queue, or check direct,
        addLogMessage($op . '-' . $subop . '-' . $checkid . "\n");
        $sql = "SELECT * from messageflow where ID > " . $checkid . " and AccountKey='" . $key . "' and direction='in'";

        //return something pleasant to the caller
    }
} else { // not in the right place, we out.

}


function SendSMS($client, $from_did, $callback_url = NULL)
{
    global $test_number;

    $msg = new Models\Message();
    var_dump($from_did);
    $msg->from = $from_did;
    $msg->to = $test_number; // Replace with your mobile number to receive messages from your Flowroute account
    $msg->body = "This is a Test Message";
    if ($callback_url != NULL) {
        $msg->dlr_callback = $callback_url;
    }
    $messages = $client->getMessages();
    $result = $messages->CreateSendAMessage($msg);
    var_dump($result);
}

$test_number = '+12317407894';

//SendSMS($client,'+12317201661');

function addLogMessage($message)
{
    global $logfile;
    file_put_contents($logfile, date('YmdHis') . ':' . $message . "\n", FILE_APPEND);
}

?>
