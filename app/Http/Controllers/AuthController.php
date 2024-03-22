<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request) {
        $user = User::where('name', $request->login)->first();

        if (!$user) {
            return response()->json(['status'=>'notFound','message'=>'Пользователя с таким логином не существует'],200);
        }

        $user->makeVisible('password');

        if ( Hash::check($request->password, $user->password) ){
            // $user->tokens()->delete();
            $token = $user->createToken("tokenName");
            return response()->json([
                "status"=>"success",
                "message"=>"Вы успешно авторизовались.",
                'token' => $token->plainTextToken,
                'login' => $user->name
            ], 200);
        }

        return response()->json(['status'=>'badData','message'=>'Неверный пароль.'], 200);
    }

    public function user(Request $request) {
        return response()->json(['status'=>'success','data'=>$request->user()],200);
    }
}
