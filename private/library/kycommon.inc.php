<?php

function curl_update_cacert($force = false)
{
    global $http_response_header;

    $update = false;

    if (!file_exists(CURL_CACERT_PATH)) {
        $update = true;
    } else {
        if (time() - filemtime(CURL_CACERT_PATH) > CURL_CACERT_UPDATE) {
            $update = true;
        }
    }

    if ($force) {
        $update = true;
    }

    if ($update) {
        $context = stream_context_create(array(
            "http" => array(
                "ignore_errors"    => true,
            ),
        ));

        $response = @file_get_contents(CURL_CACERT_URL, false, $context);

        preg_match("/HTTP\/1\.[0|1|x] ([0-9]{3})/", $http_response_header[0], $matches);
        $status_code = $matches[1];

        if ($status_code == "200") {
            @file_put_contents(CURL_CACERT_PATH, $response);
            @chmod(CURL_CACERT_PATH, 0774);
        }
    }
}

function curl_exec_cacert($session) {
    // Set cacert.pem to SSL
    curl_setopt($session, CURLOPT_CAINFO, CURL_CACERT_PATH);

    $response = curl_exec($session);

    if (curl_errno($session)) {
        curl_update_cacert(true);
        $response = curl_exec($session);
    }

    return $response;
}