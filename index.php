<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

$client = new yar_client("http://api.oss.tools/server.php");

try {
    echo '<pre>',

    /* add */
    print_r(
        $client->add(
            'person', [
                'name' => 'Lawrence Cherone',
                'age' => '33',
            ]
        ),
        true
    ),
    
    /* update */
    print_r(
        $client->all('person'),
        true
    ),
    
    '</pre>';
    
} catch (Yar_Server_Exception $e) {
    echo '<span style="color:red;font-weight:bold">Oops! '.$e->getMessage().'</span>';
}
