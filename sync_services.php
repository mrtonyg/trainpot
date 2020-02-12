<?php
$db = 'db/messageflow.db';

class MyDB extends SQLite3
{
    function __construct()
    {
        $this->open('db/messageflow.db');
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

//{"data":{"id":"mdr2-7a9e93cc4dca11ea84593af42b61a46a","links":{"self":"https://api.flowroute.com/v2.1/messages/mdr2-7a9e93cc4dca11ea84593af42b61a46a"},"type":"message"}}

//query db for outgoing messages, send them.
$sql="SELECT * from outgoing where sentFlag=0";
$sql_ret=$mdb->query($sql);
while($row=$sql_ret->fetchArray(SQLITE3_ASSOC)) {
    $res=SendSMS($client,'+12317201662',$row["MobileNumber"],$row["Message"]);
    $s="UPDATE outgoing set pid='".$res."',sent=1 where ID='".$row["ID"]."'";
    $mdb->exec($s);

}
//SendSMS($client,'+12317201662','+12312064791','this is a testy');
//query provider for incoming messages, write db.
  //GetMessages($client);




function SendSMS($client, $from, $to,$body,$callback_url = NULL)
{
    //global $test_number;

    $msg = new Models\Message();
    //var_dump($from_did);
    $msg->from = $from;
    $msg->to = $to; // Replace with your mobile number to receive messages from your Flowroute account
    $msg->body = $body;
    if ($callback_url != NULL) {
        $msg->dlr_callback = $callback_url;
    }
    $messages = $client->getMessages();
    $result = $messages->CreateSendAMessage($msg);
    //var_dump($result);
    $return_list[]=$result->data;
    return $result->data->id;
}

function GetMessages($client)
{
    $return_list = array();
    $limit = 1;
    $offset = 0;

    // Find all messages since January 1, 2017
    //$startDate = new DateTime('2020-02-01', new DateTimeZone('Pacific/Nauru'));
    $endDate = NULL;
    $startDate = new DateTime;
    $startDate->modify('-2hours');
   do
    {
        $messages = $client->getMessages();
        echo "calling lookup on ";
        var_dump($startDate);
        var_dump($endDate);
        var_dump($limit);
        var_dump($offset);
        $message_data = $messages->getLookUpASetOfMessages($startDate, $endDate, $limit, $offset);

        // Iterate through each number item
        foreach ($message_data->data as $item)
        {
            echo "---------------------------\nSMS MDR:\n";
            var_dump($item);
            $return_list[] = $item->id;
        }

        // See if there is more data to process
        $links = $message_data->links;
        if (isset($links->next))
        {
            // more data to pull
            $offset += $limit;
        }
        else
        {
            break;   // no more data
        }
    }
    while (true);

    return $return_list;
}
