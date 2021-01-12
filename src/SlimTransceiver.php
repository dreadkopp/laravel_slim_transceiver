<?php


namespace dreadkopp\LaravelSlimTransceiver;


use Illuminate\Http\Request;
use Slim\Http\Response;

class SlimTransceiver
{
    private function dispatchToSlim(Request $request):Response
    {


       if (session_status() !== PHP_SESSION_ACTIVE) {
           session_set_save_handler(session()->getHandler(), true);
           session_start();
        }
        return include public_path('sub_slim.php');

    }

    private function translateSlimResponse(Response $response)
    {
        $headers = $response->getHeaders();
        $body = $response->getBody();
        $code = $response->getStatusCode();
        $chunkSize = 4096;

        $content = '';
        $json = false;

        if ($body->isSeekable()) {
            $body->rewind();
        }
        $contentLength = $response->getHeaderLine('Content-Length');
        if (!$contentLength) {
            $contentLength = $body->getSize();
        }
        $amountToRead = $contentLength;

        while ($amountToRead > 0 && !$body->eof()) {
            $data = $body->read(min((int)$chunkSize, (int)$amountToRead));
            $content .= $data;

            $amountToRead -= strlen($data);

            if (connection_status() != CONNECTION_NORMAL) {
                break;
            }
        }

        if (isset($headers['Content-Type'])) {
            if (in_array('application/json',$headers['Content-Type'])){
                $content = json_decode($content,true);
                $json = true;
            }
        }

        if (isset($headers['Location'][0])) {
            $location = $headers['Location'][0];
            return \response()->redirectTo($location);
        }


        if ($json) {
            return \response()->json($content, $code);
        }

        return \response($content,$code)->withHeaders($headers);

    }

    public function handle()
    {
        $slim_response = $this->dispatchToSlim(\request());
        $response = $this->translateSlimResponse($slim_response);

        return $response;

    }
}
