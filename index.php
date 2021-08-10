<?php

define("GIPHY_API_KEY", $_ENV["GIPHY_API_KEY"]);
define("GIPHY_API_URL", $_ENV["GIPHY_API_URL"]);
define("GIPHY_LANG", $_ENV["GIPHY_LANG"]);
define("MATTERMOST_API_KEY", $_ENV["MATTERMOST_API_KEY"]);

// The proxy will not accept GET, PUT or DELETE requests
header("Access-Control-Allow-Methods: POST, OPTIONS");
// The proxy will always return JSON responses
header('Content-Type: application/json');


function proxy() {
    search(urldecode($_POST["command"]));
}

function parse_command($command) {
    return explode(" ", urldecode($command));
}

function search($query) {
    $url = GIPHY_API_URL . "/gifs/search?api_key=" . GIPHY_API_KEY
                         . "&q=" . $query
                         . "&limit=10&offset=0&rating=g"
                         . "&lang=" . GIPHY_LANG;
    $results = get($url);
    $gif = select_gif($results);
    $response = array(
        'response_type' => 'in_channel',
        'text' => $query . '\n' . '![gif](' . $gif . ')',
        'url' => $url
    );
    echo json_encode($response, JSON_UNESCAPED_SLASHES);
}

function select_gif($response) {
    return $response["data"][0]["images"]["original"]["url"];
}


// REST helpers

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

function get($url) {
    return json_decode(file_get_contents($url), true);
}

proxy();

?>