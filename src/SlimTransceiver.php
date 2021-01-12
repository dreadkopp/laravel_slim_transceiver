<?php


namespace dreadkopp\LaravelSlimTransceiver;


use Illuminate\Http\Request;
use Slim\Http\Response;

class SlimTransceiver
{
    public function handle()
    {
        $this->createLocalSession();
        $slim_response = $this->dispatchToSlim(\request());
        $response = $this->translateSlimResponse($slim_response);
        $this->gatherLocalSession();
        
        return $response;
        
    }
    
    private function createLocalSession()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_set_save_handler(session()->getHandler(), true);
            session_name(session()->getSessionConfig()['cookie']);
            
            session_id(session()->getId());
            foreach (session()->all() as $key => $val) {
                $_SESSION[$key] = $val;
            }
        }
    }
    
    private function dispatchToSlim(Request $request): Response
    {
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
            if (in_array('application/json', $headers['Content-Type'])) {
                $content = json_decode($content, true);
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
        
        return \response($content, $code)->withHeaders($headers);
        
    }
    
    private function gatherLocalSession()
    {
        foreach ($_SESSION as $key => $val) {
            session()->put($key, $val);
        }
        session_unset();
    }
}
