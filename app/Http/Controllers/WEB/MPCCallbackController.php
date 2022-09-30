<?php

namespace App\Http\Controllers\WEB;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\API\AlloApiController;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Request as FacadesRequest;

/*
|--------------------------------------------------------------------------
| MPC Callback Controller
|--------------------------------------------------------------------------
|
| Catch Request from MPC API after user do Login or Register
| 
| @author: rangga.muharam@arkamaya.co.id 
| @update: 24 April 2021
*/

class MPCCallbackController extends Controller
{
	// Construct
	public function __construct() {
		 // set API URL dari APP_ENV
        $this->url_redirect = env('APP_REDIRECT_URL');
        $this->AlloApiController = new AlloApiController;
     }

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
		try
		{
		  // Construct Redis
		  $redis = Redis::connection();
		  // Get Browser Cookies untuk authSession
		  // Jangan lupa setting cookies di header untuk HIT API ini,
		  // nilainya HARUS SAMA dengan authSession pada saat HIT API LOGIN/REGISTER
		  // yang di setting di header nya
		  $value = FacadesRequest::cookie('authSession');
		  $response = $redis->get($value);
		  $authSession = json_decode($response);
		  if(!empty($authSession)) {
				  if(!empty($request['code'])){
					  
					  // Construct Mandatory Request
					  $request_auth_token = new Request();
					  $request_auth_token->merge([
						  'code' => $request['code'],
						  'codeVerifier' => $authSession->verifier
					  ]);
  
					  // Construct For Web
					  if(!empty($authSession->equipmentId)){ 
						  $request_auth_token->merge([
							  'equipmentId' => $authSession->equipmentId
						  ]);
					  } elseif (!empty($authSession->osType)) {
						  // Construct For Mobile
						  $request_auth_token->merge([
							  'osType' => $authSession->osType,
							  'deviceId' => $authSession->deviceId
						  ]);
					  }
					  $response = $this->AlloApiController->allo_request_token($request_auth_token);
					  $res = (array)$response->getData();
					  if($res['status']){
						  $res['data'] = (array)$res['data'];
						  $res['data']['responseData'] = (array)$res['data']['responseData'];
						  $response = [
							  'status' => true,
							  'message' => $res['message'],
							  'code' => 200,
							  'data' => $res['data']
						  ];
						  // dd($response);
						  // Jika Mobile
						  if(!empty($authSession->osType)){
							  $response = [
								  'status' => true,
								  'message' => $response['message'],
								  'code' => 200,
								  'data' => $res['data']
							  ];
							  Redis::del($value); // Flush Del key sesuai sessionId
							  return response()->json($response, 200);
							  // You can use View/Blade to return the response for Mobile Apps
							  // return view('mpccallback',['response' =>json_encode($response)]);
						  } else {
							  //WEB 
							  Redis::del($value); // Flush Del key sesuai sessionId
							  return redirect()->away($this->url_redirect.'mpccallback?response='.json_encode($response));
						  }
						  
					  } else {
						  $response = [
							  'status' => false,
							  'message' => "Please Re-login",
							  'code' => 205,
							  'data' => $request->all()
						  ];
						  return response()->json($response, 200);
					  }	
				  }
		  } else {
			  $response = [
				  'status' => false,
				  'message' => 'Please Re-login',
				  'code' => 205,
				  'data' => $request->all()
			  ];
			  return response()->json($response, 200);
		  }
  
		}
		catch(\Exception $e) 
		{
			$data = array(
				  "status" => false,
				  "message" => $e->getMessage()
			  );
			  
			  $this->logs($data);
			  
			return response()->json($data);
		}
	  }
  
	  /*
	   * Function: Log Request
	   * Param: 
	   *	$data	: mixed string/array
	   */
	  private function logs($data)
	  {
		  $file = 'logs_allo.txt';
		  
		  // Open the file to get existing content
		  if(file_exists($file)) {
			  $current = file_get_contents($file);
		  }else{
			  $current = '';
		  }
				  
		  // Append a new person to the file
		  if(is_array($data)) {
			  $current .= json_encode($data);
		  }else{
			  $current .= $data;
		  }
		  
		  // Write the contents back to the file
		  file_put_contents($file, $current);
		  //Storage::put($file, $current);
		  
	  }
	  
	  /*
	   * Function: Clear Log Request
	   * Param: 
	   *	void
	   */
	  private function clear_logs()
	  {
		  $file = 'logs_allo.txt';
		  
		  if(file_exists($file)) {
			  unlink($file);
		  }
		  
		  // Open the file to get existing content
		  file_put_contents($file, '');
		  
	  }
  }
  