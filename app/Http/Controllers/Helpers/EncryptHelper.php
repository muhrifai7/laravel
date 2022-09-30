<?php

namespace App\Http\Controllers\Helpers;

use App\Http\Controllers\Controller;
/*
|--------------------------------------------------------------------------
| EncryptHelper Controller
|--------------------------------------------------------------------------
|
| 
| This controller are Helpers for encrypt and decrypt 
| 
| @author: rangga.muharam@arkamaya.co.id 
| @update: 24 April 2021
*/

class EncryptHelper extends Controller
{
    function base64_encode_url($string) {
        return str_replace(['+','/','='], ['-','_',''], base64_encode($string));
    }

    function create_verifier(){
       return $this->base64_encode_url(random_bytes(32));
    }

    function random_0_1() 
    {
        return (float)rand() / (float)getrandmax();
    }

    function hashing($string){
        return hash('sha256', $string);
    }

    function hashingVerifier($string){
        $challengeBytes = hash("sha256", $string, true);
        return $this->base64_encode_url($challengeBytes);
    }

    function to_hex($data)
    {
        return (bin2hex($data));
    }

    function to_str($data)
    {
        return (hex2bin($data));
    }
}
