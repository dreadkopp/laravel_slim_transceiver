<?php


namespace App\Services\Slim;


use Illuminate\Http\Request;
use Slim\Http\Response;

class SlimTransceiver
{
    private function dispatchToSlim(Request $request):Response
    {

        session_start(
            [
                'save_path' => sys_get_temp_dir()
            ]
        );
        foreach (session()->all() as $key => $value) {
            $_SESSION[$key] = $value;
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

        return \response($content,$code);

    }

    private function migrateNativeSessionVars()
    {
        session()->flush();
        foreach($_SESSION as $key => $value) {
            session()->put($key,$value);
        }
        session_unset();
    }

    public function handle()
    {
        $slim_response = $this->dispatchToSlim(\request());
        $response = $this->translateSlimResponse($slim_response);
        $this->migrateNativeSessionVars();
        return $response;

    }
}
