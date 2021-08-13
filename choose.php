<?php
header("Access-Control-Allow-Methods: POST, OPTIONS");
header('Content-Type: application/json');


function reply($request) {
    $gif = $request['context']['gif'];
    $response_url = $request['context']['response_url'];
    $response = array(
        'response_type' => 'in_channel',
        'text' => '![gif](' . $gif . ')',
        'username' => 'giphy'
    );
    // Post gif
    post($response_url, $response);
    // Update bot menu
    $success_msg = array(
        'update' => array(
            'message' => 'Gif sent!'
        ),
        'ephemeral_text' => 'Gif sent!'
    );
    echo json_encode($success_msg, JSON_UNESCAPED_SLASHES);
}

function post($url, $data) {
    $options = array(
        'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
        )
    );
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    return $result;
}

reply($_POST)

?>