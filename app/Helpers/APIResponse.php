<?php
//use App\Models\AppSettings;


if (!function_exists('_responsePeriods')) {

    /**
     * success response method.
     *
     * @return \Illuminate\Http\Response
     */
    function _responsePeriods($status, $data, $count, $total_pages, $per_page, $currentPage)
    {

        $response = [
            'status' => $status,
            'success' => true,
            'count' => (int) $count,
            'total_pages' => (int) $total_pages == 0 ? 1 : (int) $total_pages,
            'current_page' => (int) $currentPage == 0 ? 1 : (int) $currentPage,
            'per_page' => $per_page
        ];

        if (!empty($data)) {
            $response['data'] = $data;
        }
        return response()->json($response, 200);
    }
}

/**
 * Graph data responses
 */
if (!function_exists('_responseGraphPeriods')) {

    /**
     * success response method.
     *
     * @return \Illuminate\Http\Response
     */
    function _responseGraphPeriods($status, $data, $count)
    {

        $response = [
            'status' => $status,
            'success' => true,
            'count' => (int) $count,
        ];

        if (!empty($data)) {
            $response['data'] = $data;
        }
        return response()->json($response, 200);
    }
}


if (!function_exists('_sendData')) {

    /**
     * success response method.
     *
     * @return \Illuminate\Http\Response
     */
    function _sendData($code, $result)
    {

        $response = [
            'status' => $code,
            'success' => true,
        ];

        if (!empty($result)) {
            $response['data'] = $result->firstWhere("data");
            //$response['current_page'] = $result->current_page;
            //$response['first_page_url'] = $result->first_page_url;
            //$response['from'] = $result->from;
            //$response['last_page'] = $result->last_page;
            //$response['next_page_url'] = $result->next_page_url;
            //$response['per_page'] = $result->per_page;
            //$response['prev_page_url'] = $result->prev_page_url;
        }
        return response()->json($response, 200);
    }
}


//Random Password generator
if (!function_exists('_sendResponse')) {

    /**
     * success response method.
     *
     * @return \Illuminate\Http\Response
     */
    function _sendResponse($code, $message, $result = [])
    {
        $response = [
            'status' => $code,
            'success' => true,
            'message' => $message,
        ];

        $response['data'] = $result ?? null;
         
        return response()->json($response, 200);
    }
}

//Random Password generator
if (!function_exists('_sendError')) {

    /**
     * return error response.
     *
     * @return \Illuminate\Http\Response
     */
    function _sendError($code, $error, $errorMessages = [])
    {
        $response = [
            'status' => $code,
            'success' => false,
            'message' => $error,
        ];

        if (!empty($errorMessages)) {
            $response['error'] = $errorMessages;
        }

        return response()->json($response, $code);
    }
}
