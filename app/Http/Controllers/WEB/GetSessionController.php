<?php

namespace App\Http\Controllers\WEB;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class GetSessionController extends Controller
{
    // Construct
	public function __construct() {
        // set API URL dari APP_ENV
           $this->url_redirect = env('APP_REDIRECT_URL');
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
        $randomString = session()->getId();
        Cookie::queue('authSession', $randomString, 10);
        $response = [
            'status' => true,
            'message' => '',
            'code' => 200,
            'data' => $randomString
        ];
        //   return session()->getId();
          if(!empty($request['osType']))
          {
              return $response;
              // You can use View/Blade to return the response for Mobile Apps
              // return view('mpccallback',['response' =>json_encode($response)]);
          } else {
              return redirect()->away($this->url_redirect.'login_register?redirectPageType='.$request['redirectPageType'].'&sessionId='.$randomString);
          }
        //   return response()->json($response, 200);
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
}
