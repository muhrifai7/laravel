<?php

namespace App\Http\Controllers\Helpers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;

class AlloHelperController extends Controller
{

    public function __construct() {
        // set API URL dari APP_ENV
        $this->url_api = env('API_URL');

    }
    
    /**
     * Function: Generate Transaction No
     * body: 
     * $request	: search
    */
    public function create_transaction_no()
    {
        try {
            $date = date('ymd');
            $middleNo = 'ARKTHG' . mt_rand(100000000000,999999999999) . mt_rand(10000000,99999999);;
            $data = $date . $middleNo;
            return $data;
        } catch (Throwable $e) {
            report($e);
            return $e;
        }
    }

    /**
     * Function: HIT ALLO MPC API, common function
     * body: 
     * $request	: search
    */
    public function mpc_hit_api(Request $request)
    {
        try {
            $client_mpc = new Client(); //GuzzleHttp\Client
            $response = $client_mpc->request('POST', $request->mpc_url,[
                'headers' => (array)$request->dataHeader,
                'json' => $request->dataBody
            ]);
            $body = $response->getBody();
            $response = json_decode($body, true);
            return $response;

        } catch (Throwable $e) {
            report($e);
            $response = [
                'status' => false,
                'message' => $response['message'],
                'code' => 500,
                'data' => null, 
            ];
            return response()->json($response, 500);
        }
    }

}
