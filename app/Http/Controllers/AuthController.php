<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(){

    }


    public function register(){
        return view('front.account.register');
    }


    public function processRegister(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'require|min:3',
            'email' => 'required|unique:users',
            'password' => 'require|min:5'
        ]);

        if ($validator->passes()) {

        }else{
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }
}
