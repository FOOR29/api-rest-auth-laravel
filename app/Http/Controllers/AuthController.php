<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\JWT;

class AuthController extends Controller
{
    //Registro
    public function register(Request $request)
    {
        $validator = validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'role' => 'required|string|in:admin,user', // se agrega validacion para el rol
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        User::create([
            'name' => $request->get('name'),
            'role' => $request->get('role'), // se guarda el rol
            'email' => $request->get('email'),
            'password' => bcrypt($request->get('password')),
        ]);
        return response()->json(['message' => 'User registered successfully'], 201);
    }

    //Login
    function login(Request $request)
    {
        $validator = validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:6',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        $credentials = $request->only('email', 'password');

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'Invalid Credentials'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }
    }

    //Obtener usuario
    public function getUser(){
        $user = Auth::user();
        return response()->json($user, 200);
    }

    //Logout
    public function logout(){
        JWTAuth::invalidate(JWTAuth::getToken());
        return response()->json(['message' => 'User logged out successfully'], 200);
    }
}
