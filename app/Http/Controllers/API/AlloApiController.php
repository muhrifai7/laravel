<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Validator;
use Illuminate\Http\Request;
use App\Http\Controllers\Helpers\AlloHelperController;
use App\Http\Controllers\Helpers\EncryptHelper;
use Illuminate\Support\Facades\Redis;

/*
|--------------------------------------------------------------------------
| AlloApi Controller
|--------------------------------------------------------------------------
|
| Validate,.
| This controller will process all ALLO API MPC
| 
| @author: rangga.muharam@arkamaya.co.id 
| @update: 24 April 2021
*/

class AlloApiController extends Controller
{
     // Construct
     public function __construct() {
        $this->appSecret = env('APP_SECRET');
        $this->url_redirect = env('APP_REDIRECT_URL');
        $this->AlloHelperController = new AlloHelperController;
        $this->EncryptHelper = new EncryptHelper;
     }

     /**
     * Function: Create Code Challenge
     * body: 
     *	$request	: 
    */
    public function create_code_challenge(Request $request)
    {
        try {
            $verifier = $this->EncryptHelper->create_verifier();
            $codeChallenge = $this->EncryptHelper->hashingVerifier($verifier);
            $response = [
                'status' => true,
                'message' => 'Successfull',
                'code' => 200,
                'data' => $codeChallenge, 
            ];
            return response()->json($response, 200);
        } catch(Throwable $e) {
            report($e);
            $response = [
                'status' => false,
                'message' => 'Error',
                'code' => 500,
                'data' => null, 
            ];
            return response()->json($response, 500);
        }
    }

    /**
     * Function: TC Header
     * body: 
     *	$request	: 
    */
    public function create_header_allo(Request $request)
    {
        try{
            // Cek jika osType ada
            if(!empty($request->requestData['osType'])){
                $appId = env('APP_ID_MOBILE');
            } else {
                $appId = env('APP_ID_WEB');
            }
            // Construct Body Param from Request
            $body = $request->all();

            $now = round(microtime(true) * 1000);
            $header = [
                // uncomment this two lines if you want to compare encrypt and decrypt function
                // 'nonce' => '36059448',
                // 'timestamp'=> 1620647953091,
                //=======================================================================

                // comment this two lines if you want to compare encrypt and decrypt function
                'nonce' => strval(floor($this->EncryptHelper->random_0_1() * 100000000)), 
                'timestamp'=> $now,
                //=======================================================================

                'Content-Type'=> 'application/json', 
                'appId'=> $appId
            ];

            $body_str = json_encode($body);
            // construct Array
            $arr = [$header['appId'], $header['nonce'], $header['timestamp'], $this->appSecret, $body_str];
            // Sorting sesuai ASCII
            asort($arr,2);
            // Concat array
            $data = join('', $arr);
            
            // Create Hashing sha256 dan convert Hex to Bin
            $obj = $this->EncryptHelper->hashing($data);
            $obj = $this->EncryptHelper->to_str($obj);

            // Uncomment disini utk melihat value sebelum di Enkripsi, sebagai comparison dgn Fungsi Decrypt Header Sign
            // dd($this->EncryptHelper->to_hex($obj));

            // Load Private Key file
            $path = storage_path('app/key/private.key');
            $kh = openssl_pkey_get_private(file_get_contents($path));

            // Encrypt Object Data using private key
            $encrypted = openssl_private_encrypt($obj,$crypttext,$kh);          
            if($encrypted){
                // add sign key to Header array
                $header['sign'] = $this->EncryptHelper->to_hex($crypttext);
                $response = [
                    'status' => true,
                    'message' => 'Successfull Encrypted',
                    'code' => 200,
                    'data' => $header
                    ];
                
            } else {
                $response = [
                    'status' => false,
                    'message' => 'Unsuccessfull',
                    'code' => 200,
                    'data' => $header,
                ];
                
            }
            return response()->json($response, 200);
        }
            catch (Throwable $e) {
            report($e);
            $response = [
                'status' => false,
                'message' => 'Error',
                'code' => 500,
                'data' => null, 
            ];
            return response()->json($response, 500);
        }
    }

   /**
     * Function: Allo Auth-Page untuk Register atau Login
     * body: 
     * return url untuk nanti diload di webview atau location.href
     *	$request	: 
    */
    public function allo_auth_page(Request $request)
    {
        try{
                // Get Coookies name authSession From Request Cookies
                // Jangan lupa setting cookies di header untuk HIT API ini
                $authSession = $request->cookie('authSession');

                $trxNo = $this->AlloHelperController->create_transaction_no();
                $verifier = $this->EncryptHelper->create_verifier();
                $codeChallenge = $this->EncryptHelper->hashingVerifier($verifier);

                // Check Request Validation
                $rules = [
                    'redirectPageType' => 'required'
                ];
                $validator = Validator::make($request->all(), $rules);
                if($validator->fails()) 
                {
                    // return response gagal
                    $response = [
                        'status' => false,
                        'code' => 400,
                        'message' => $validator->errors()->first(), 
                        'data' =>$request->all(),
                        
                    ];
                    return response()->json($response, 200);
                }
                
                // Construct default body param - default utk WEB
                $dataBody = [
                    'transactionNo' => $trxNo,
                    'requestData' => [
                        'responseType' => 'CODE',
                        'codeChallengeMethod' => 'SHA256',
                        'codeChallenge' => $codeChallenge,
                        'authorizationPageType' => strtoupper($request->redirectPageType)
                    ]
                ];

                if(!empty($request->osType)) {
                    if( (strtoupper($request->osType) === 'ANDROID') || (strtoupper($request->osType) === 'IOS') ){
                        $rules = [
                            'osType' => 'required',
                            'deviceId' => 'required'
                        ];
                        $validator = Validator::make($request->all(), $rules);
                        if($validator->fails()) 
                        {
                            // return response gagal
                            $response = [
                                'status' => false,
                                'code' => 500,
                                'message' => $validator->errors()->first(), 
                                'data' =>$request->all(),
                                
                            ];
                            return response()->json($response, 200);
                        }
                    } else {
                        $response = [
                            'status' => false,
                            'code' => 500,
                            'message' => 'please use android or ios for osType param', 
                            'data' =>$request->all(),
                            
                        ];
                        return response()->json($response, 200);
                    }
                    // Ini dataBody utk Mobile, overrride yang default value
                    $dataBody['requestData']['osType'] = strtolower($request->osType);
                    $dataBody['requestData']['deviceId'] = $request->deviceId;
                }
                // create Request
                $request_header = new Request();
                $request_header->merge($dataBody);
                // Call fungsi utk create Header
                $response_create_header = json_decode($this->create_header_allo($request_header)->getContent());
                if($response_create_header->status){
                    $data_header = $response_create_header->data; // Nonce bisa diambil dari sini $data_header->nonce

                    // Get url MPC AUTH PAGE
                    $url_mpc = env('MPC_URL_AUTH_PAGE');

                    // HIT MPC API AUTH PAGE
                    // create Request
                    $req_hit_allo = new Request();
                    $req_hit_allo->merge(['mpc_url' => $url_mpc,'dataHeader' => $data_header , 'dataBody' => $dataBody]);
                    $resp_hit_allo = $this->AlloHelperController->mpc_hit_api($req_hit_allo);
                    // Cek response Success
                    if($resp_hit_allo['message'] == 'Success')
                    {
                        // passing PAGE key ke Front End
                        $resp_hit_allo['url_page'] = $resp_hit_allo['responseData']['authorizationPageUri'];
                        // passing code_verifier untuk nanti digunakan FrontEnd sebagai param HIT Request Token
                        $resp_hit_allo['codeVerifier'] = $verifier;
                        // Decode AuthorizationPageUri, untuk mengamnbil equipmentId (Web) atau osType dan deviceId (Mobile)
                        $url_components = parse_url($resp_hit_allo['url_page']);
                        parse_str($url_components['fragment'], $params);
                        if(!empty($params['equipmentId'])) {
                            $resp_hit_allo['equipmentId'] = $params['equipmentId'];
                            // Set Redis untuk nanti digunakan di Callback
                            $redis = Redis::connection();
                            $redis->set($authSession, json_encode([
                                            'verifier' =>  $verifier,
                                            'equipmentId' => $params['equipmentId']
                                            ])
                                        );
                        } elseif (!empty($params['osType'])) {
                            $resp_hit_allo['osType'] = $params['osType'];
                            $resp_hit_allo['deviceId'] = $params['deviceId'];
                            $redis = Redis::connection();
                            $redis->set($authSession, json_encode([
                                            'verifier' =>  $verifier,
                                            'osType' => $params['osType'],
                                            'deviceId' => $params['deviceId']
                                            ])
                                        );
                        }
                        $response = [
                            'status' => true,
                            'message' => $resp_hit_allo['message'],
                            'code' => 200,
                            'data' => $resp_hit_allo
                        ];
                    } else {
                        $error = [
                            'modul' => 'allo_auth_page',
                            'actions' => 'Hit Allo MPC',
                            'error_log' => $resp_hit_allo,
                            'device' => "0" 
                            ];
                        $response = [
                            'status' => false,
                            'message' => $resp_hit_allo['message'],
                            'code' => 500,
                            'data' => $resp_hit_allo
                        ];
                    }


                    
                } else {
                    $response = [
                        'status' => false,
                        'message' => $response_create_header->message,
                        'code' => 500,
                        'data' => $request_header 
                    ];
                }
            
                return response()->json($response, 200);
            }
            catch (Throwable $e) {
                $error = [
                    'modul' => 'allo_auth_page',
                    'actions' => 'Hit Allo Auth Page',
                    'error_log' => $e,
                    'device' => "0" 
                    ];
                report($e);
                $response = [
                    'status' => false,
                    'message' => 'Error',
                    'code' => 500,
                    'data' => null, 
                ];
                return response()->json($response, 500);
        }
    }

    /**
     * Function: Allo Request Token
     * body: Code, Verifier, equipmentId (Web), osType and deviceId (Mobile)
     *      
     *	$request	: 
    */
    public function allo_request_token(Request $request)
    {
        try
        {
            // Check Request Validation
            $rules = [
                'code' => 'required',
                'codeVerifier' => 'required'
            ];
            $validator = Validator::make($request->all(), $rules);
            if($validator->fails()) 
            {
                // return response gagal
                $response = [
                    'status' => false,
                    'code' => 400,
                    'message' => $validator->errors()->first(), 
                    'data' =>$request->all(),
                    
                ];
                return response()->json($response, 200);
            }
            // Construct default body param
            $dataBody = [
                "transactionNo"=>  $this->AlloHelperController->create_transaction_no(),
                "requestData" => [
                    "code" => $request['code'],
                    "codeVerifier" => $request['codeVerifier'],
                    "grantType" => "AUTHORIZATION_CODE"
                ]
            ];

            if(!empty($request->equipmentId)){
                $rules = [
                    'equipmentId' => 'required'
                ];
                $validator = Validator::make($request->all(), $rules);
                if($validator->fails()) 
                {
                    // return response gagal
                    $response = [
                        'status' => false,
                        'code' => 400,
                        'message' => $validator->errors()->first(), 
                        'data' =>$request->all(),
                        
                    ];
                    return response()->json($response, 200);
                }
                // Ini dataBody utk Mobile, overrride yang default value
                $dataBody['requestData']['equipmentId'] = $request->equipmentId;             
            } else if(!empty($request->osType)){
                $rules = [
                    'osType' => 'required',
                    'deviceId' => 'required'
                ];
                $validator = Validator::make($request->all(), $rules);
                if($validator->fails()) 
                {
                    // return response gagal
                    $response = [
                        'status' => false,
                        'code' => 400,
                        'message' => $validator->errors()->first(), 
                        'data' =>$request->all(),
                        
                    ];
                    return response()->json($response, 200);
                }
                // Ini dataBody utk Mobile, overrride yang default value
                $dataBody['requestData']['osType'] = $request->osType;
                $dataBody['requestData']['deviceId'] = $request->deviceId;
            }

            // HIT Create_Header Function
            // create Request
            $request_header = new Request();
            $request_header->merge($dataBody);
            // Call fungsi utk create Header
            $response_create_header = json_decode($this->create_header_allo($request_header)->getContent());
            if($response_create_header->status){
                // Header utk HIT authorize token
                $data_header = $response_create_header->data;
                // url auth token MPC
                $url_mpc = env('MPC_URL_REQUEST_TOKEN');
                // HIT MPC API AUTH PAGE
                // create Request
                $req_hit_allo = new Request();
                $req_hit_allo->merge(['mpc_url' => $url_mpc,'dataHeader' => $data_header , 'dataBody' => $dataBody]);
                $resp_hit_allo = $this->AlloHelperController->mpc_hit_api($req_hit_allo);
                // Cek response success
                if(strtoupper($resp_hit_allo['message']) == 'SUCCESS') { 
                    $response = [
                        'status' => true,
                        'message' => $resp_hit_allo['message'],
                        'code' => 200,
                        'data' => $resp_hit_allo
                    ];
                    return response()->json($response, 200);
                } else {
                    $error = [
                        'modul' => 'allo_auth_token',
                        'actions' => 'Hit Allo Auth Token API MPC',
                        'error_log' => $resp_hit_allo,
                        'device' => "0" 
                        ];
                    $response = [
                        'status' => false,
                        'message' => $resp_hit_allo['message'],
                        'code' => 400,
                        'data' => $resp_hit_allo 
                    ];
                }
                        
            } else {
                $response = [
                    'status' => false,
                    'message' => $response_create_header->message,
                    'code' => 400,
                    'data' => $request_header 
                ];
            }
        
            return response()->json($response, 200);
        } catch (Throwable $e) {
            report($e);
            $response = [
                'status' => false,
                'message' => 'Error',
                'code' => 500,
                'data' => null, 
            ];
            return response()->json($response, 500);
        }
    }

    /**
     * Function: Decrypt Header Sign
     * body: 
     *	$request	: 
    */
    public function decrypt_header(Request $request)
    {
        try{ 
                // Get Public Key
                $path = storage_path('app/key/public.crt');
                $kh = openssl_pkey_get_public(file_get_contents($path));

                // Convert Hex to Str
                $data = $this->EncryptHelper->to_str($request->requestData['sign']);
                // Decrypt data buffer with Public Key
                $decrypted = openssl_public_decrypt($data,$decryptedData,$kh);
                // dd(($decryptedData));

                if($decrypted){
                        $response = [
                            'status' => true,
                            'message' => 'Successfull Decrypted',
                            'code' => 200,
                            'data' => $this->EncryptHelper->to_hex($decryptedData), 
                        ];
                            
                        return response()->json($response, 200);
                } else {
                    return response()->json($decrypted, 500);
                }
            }
            catch (Throwable $e) {
                report($e);
                $response = [
                    'status' => false,
                    'message' => 'Error',
                    'code' => 500,
                    'data' => null, 
                ];
                return response()->json($response, 500);
        }
    }

     /**
     * Function: Refresh Token untuk mendapatkan accessToken yg baru
     * body: 
     *	$request	: 
    */
    public function allo_refresh_token(Request $request)
    {
        try {
            $rules = [
                'refreshToken' => 'required'
            ];
            $validator = Validator::make($request->all(), $rules);
            if($validator->fails()) 
            {
                // return response gagal
                $response = [
                    'status' => false,
                    'code' => 500,
                    'message' => $validator->errors()->first(), 
                    'data' =>$request->all(),
                    
                ];
                return response()->json($response, 200);
            }
            // Construct dataBody
            $dataBody = [
                'requestData' => [
                    'refreshToken' => $request->refreshToken,
                    'grantType' => 'REFRESH_TOKEN'
                ]
            ];
            // get trxNo
            $trxNo = $this->AlloHelperController->create_transaction_no();
            $dataBody['transactionNo'] = $trxNo;
            // Construct Header untuk di passing ke Create_Header_Allo function, nanti response header with sign key
            $header = $dataBody;
             // create Request
             $request_header = new Request();
             $request_header->merge($header);
             // Call fungsi utk create Header
             $response_create_header = json_decode($this->create_header_allo($request_header)->getContent());
             if($response_create_header->status){
                $data_header = $response_create_header->data;
                // GET REFRESH TOKEN URL 
                $url_mpc = ENV('MPC_URL_REFRESH_TOKEN');
                // HIT MPC API Refresh Token
                // create Request
                $req_hit_allo = new Request();
                $req_hit_allo->merge(['mpc_url' => $url_mpc,'dataHeader' => $data_header , 'dataBody' => $dataBody]);
                $resp_hit_allo = $this->AlloHelperController->mpc_hit_api($req_hit_allo);
                // Cek response success
                if(strtoupper($resp_hit_allo['message']) == 'SUCCESS') { 
                    $response = [
                        'status' => true,
                        'message' => $resp_hit_allo['message'],
                        'code' => 200,
                        'data' => $resp_hit_allo
                    ];
                } else {
                    $error = [
                        'modul' => 'refresh_token',
                        'actions' => 'Hit Allo Refresh Token API',
                        'error_log' => json_encode($resp_hit_allo),
                        'device' => "0" 
                        ];
                    $response = [
                        'status' => true,
                        'message' => $resp_hit_allo['message'],
                        'code' => 200,
                        'data' => $resp_hit_allo
                    ];
                }
             } else {
                $response = [
                    'status' => false,
                    'message' => $response_create_header->message,
                    'code' => 400,
                    'data' => $request_header 
                ];
             }

            return response()->json($response, 200);
        } catch(Throwable $e) {
            $error = [
                'modul' => 'refresh_token',
                'actions' => 'Hit Allo Refresh Token API',
                'error_log' => $e,
                'device' => "0" 
                ];
            report($e);
            $response = [
                'status' => false,
                'message' => 'Error',
                'code' => 500,
                'data' => null, 
            ];
            return response()->json($response, 500);
        }
    }

    /**
     * Function: Helper untuk Construct new accessToken dan refreshToken
     * body: 
     *	$request	: 
    */
    public function refresh_token_helper($request)
    {
        try{
            // Construct dataBody untuk Hit api Refresh Token
            $dataBodyRefreshToken = [
                'refreshToken' => $request['refreshToken']
            ];
            $request_refresh_token = new Request();
            $request_refresh_token->merge($dataBodyRefreshToken);
            //HIT Allo API Resfresh Token utk mendapatkan Pair accessToken dan refreshToken yg baru
            // $response_refresh_token = (array)json_decode($this->refresh_token($request_refresh_token)->getContent());
            return (array)json_decode($this->allo_refresh_token($request_refresh_token)->getContent());

            }
            catch (Throwable $e) {
                report($e);
                $response = [
                    'status' => false,
                    'message' => 'Error',
                    'code' => 500,
                    'data' => null, 
                ];
                return response()->json($response, 500);
        }
    }

     /**
     * Function: Fetch Member Allo Profile ALLO HIT API
     * body: 
     *	$request	: 
    */
    public function allo_member_profile(Request $request)
    {
        try {
            $rules = [
                'accessToken' => 'required',
                'refreshToken' => 'required'
            ];
            $validator = Validator::make($request->all(), $rules);
            if($validator->fails()) 
            {
                // return response gagal
                $response = [
                    'status' => false,
                    'code' => 500,
                    'message' => $validator->errors()->first(), 
                    'data' =>$request->all(),
                    
                ];
                return response()->json($response, 200);
            }
             // create new fresh accessToken dan refreshToken
             $response_refresh_token = $this->refresh_token_helper($request->all());
             $newAccessToken = $response_refresh_token['data']->responseData->accessToken;
             $newRefreshToken = $response_refresh_token['data']->responseData->refreshToken;
            
             // Construct dataBody untuk Hit api Member Profile
             $dataBody = [
                 'requestData' => [
                     'accessToken' => $newAccessToken
                 ]
             ];
             // Construct Header untuk di passing ke Create_Header_Allo function, nanti response header with sign key
             $header = $dataBody;
             // create Request
             $request_header = new Request();
             $request_header->merge($header);
             // Call fungsi utk create Header
             $response_create_header = json_decode($this->create_header_allo($request_header)->getContent());
             if($response_create_header->status){
                $data_header = $response_create_header->data;

                // Get url MPC Member_Existence from ENV
                $url_mpc = env('MPC_URL_GET_MEMBER_PROFILE');
                // HIT MPC API Member Existence
                // create Request
                $req_hit_allo = new Request();
                $req_hit_allo->merge(['mpc_url' => $url_mpc,'dataHeader' => $data_header , 'dataBody' => $dataBody]);
                $resp_hit_allo = $this->AlloHelperController->mpc_hit_api($req_hit_allo);
                // Cek response success
                if(strtoupper($resp_hit_allo['message']) == 'SUCCESS') { 
                    $resp_hit_allo['accessToken'] = $newAccessToken;
                    $resp_hit_allo['refreshToken'] = $newRefreshToken;
                        $response = [
                            'status' => true,
                            'message' => $resp_hit_allo['message'],
                            'code' => 200,
                            'data' => $resp_hit_allo
                        ];

                } else {
                    $error = [
                        'modul' => 'allo_member_profile',
                        'actions' => 'Hit Allo Member Profile API',
                        'error_log' => json_encode($resp_hit_allo),
                        'device' => "0" 
                        ];
                    $response = [
                        'status' => true,
                        'message' => $resp_hit_allo['message'],
                        'code' => 200,
                        'data' => $resp_hit_allo
                    ];
                }


             } else {
                $response = [
                    'status' => false,
                    'message' => $response_create_header->message,
                    'code' => 400,
                    'data' => $request_header 
                ];
             }

            return response()->json($response, 200);
        } catch(Throwable $e) {
            $error = [
                'modul' => 'allo_member_profile',
                'actions' => 'Hit Allo Member Profile API',
                'error_log' => $e,
                'device' => "0" 
                ];
            report($e);
            $response = [
                'status' => false,
                'message' => 'Error',
                'code' => 500,
                'data' => null, 
            ];
            return response()->json($response, 500);
        }
    }

}
