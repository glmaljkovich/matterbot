<?php

define("GIPHY_API_KEY", $_ENV["GIPHY_API_KEY"]);
define("GIPHY_API_URL", $_ENV["GIPHY_API_URL"]);
define("GIPHY_LANG", $_ENV["GIPHY_LANG"]);
define("MATTERMOST_API_KEY", $_ENV["MATTERMOST_API_KEY"]);
define("BASE_URL", $_ENV["BASE_URL"]);

// The proxy will not accept GET, PUT or DELETE requests
header("Access-Control-Allow-Methods: POST, OPTIONS");
// The proxy will always return JSON responses
header('Content-Type: application/json');


function proxy() {
    $command = urldecode($_POST["command"]);
    switch ($command) {
        case '/gif':
            $search = urlencode($_POST["text"]);
            if (str_contains($search, "--new")) {
                choose($search, $_POST['response_url']);
            } else {
                search($search);
            }
            break;
        default:
            http_response_code(404);
            break;
    }
}

function choose($query, $response_url) {
    $api_key = select_api_key();
    $cleaned_query = str_replace('--new', '', $query);
    $url = GIPHY_API_URL . "/gifs/search?api_key=" .  $api_key
                         . "&q=" . $cleaned_query
                         . "&limit=10&offset=0&rating=g"
                         . "&lang=" . GIPHY_LANG;
    $results = get($url);
    $response = array(
        'response_type' => 'in_channel',
        'channel_id' => $_POST["user_id"],
        'username' => 'giphy',
        'attachments' => []
    );
    $attachment = array(
        'text' => 'Choose a gif ' . $response_url . ' ' . BASE_URL . '/choose',
        'fields' => [],
        'actions' => []
    );
    $i = 0;
    foreach ($results["data"] as $result) {
        $gif = $result["images"]["original"]["url"];
        // Add Field
        $field = array(
            'short' => true,
            'title' => strval($i),
            'value' => '![gif](' . $gif . ')'
        );
        array_push($attachment['fields'], $field);
        // Add Action
        $action = array(
            'id' => strval($i),
            'name' => strval($i),
            'integration' => array(
                'url' => BASE_URL . '/choose',
                'context' => array(
                    'gif' => $gif,
                    'query' => $cleaned_query,
                    'response_url' => $response_url,
                    'channel_id' => $_POST["channel_id"]
                )
            )
        );
        array_push($attachment['actions'], $action);

        $i++;
    }

    array_push($response['attachments'], $attachment);

    echo json_encode($response, JSON_UNESCAPED_SLASHES);
}

function search($query) {
    $api_key = select_api_key();
    $url = GIPHY_API_URL . "/gifs/search?api_key=" .  $api_key
                         . "&q=" . $query
                         . "&limit=10&offset=0&rating=g"
                         . "&lang=" . GIPHY_LANG;
    $results = get($url);
    $gif = select_gif($results);
    $response = array(
        'response_type' => 'in_channel',
        'text' => '![gif](' . $gif . ')',
        'username' => 'giphy'
    );
    echo json_encode($response, JSON_UNESCAPED_SLASHES);
}

function select_api_key() {
    $keys = explode(",", GIPHY_API_KEY);
    if (count($keys) < 2) {
        return GIPHY_API_KEY;
    } else {
        return $keys[random_int(0, count($keys) - 1)];
    }
}

function select_gif($response) {
    $random_result_index = random_int(0, 9);
    return $response["data"][$random_result_index]["images"]["original"]["url"];
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