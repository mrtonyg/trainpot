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


GetMessages($client);





function GetMessages($client)
{
    $return_list = array();
    $limit = 1;
    $offset = 0;

    // Find all messages since January 1, 2017
    $startDate = new DateTime('2018-01-01', new DateTimeZone('Pacific/Nauru'));
    $endDate = NULL;

    do
    {
        $messages = $client->getMessages();
        $message_data = $messages->getLookUpASetOfMessages($startDate, $endDate, $limit, $offset);

        // Iterate through each number item
        foreach ($message_data->data as $item)
        {
            echo "---------------------------\nSMS MDR:\n";
            var_dump($item);
            echo "Attributes:" . $item->attributes . "\n";
            echo "Id:" . $item->id . "\nLinks:" . $item.links . "\nType:" . $item->type . "\n";
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