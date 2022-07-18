<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 *
 */
class UserController extends ApiController
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Bad request!', $validator->messages()->toArray());
        }

        $error = false;

        /** @var User $user */
        $user = User::where('email', $request->get('email'))->first();
        if (!$user) {
            $error = true;
        } else {
            if (!Hash::check($request->get('password'), $user->password)) {
                $error = true;
            }
        }

        if ($error) {
            return $this->sendError('Bad credentials!');
        }

        $token = $user->createToken('Practica');

        return $this->sendResponse([
            'token' => $token->plainTextToken,
            'user' => $user->toArray()
        ]);
    }

    public function register(Request $request): JsonResponse
    {
      $validator = Validator::make($request->all(),[
          'email' => 'required|email|unique:users,email',
          'name' => 'required|max:50',
          'password' => 'required|min:5'
      ]);

      if($validator->fails()) {
          return $this->sendError('Bad Request!', $validator->messages()->toArray());
      }

      $user = new User();

      $user->name = $request->input('name');
      $user->email = $request->input('email');
      $user->password = Hash::make($request->input('password'));
      $user->email_verified_at = now();

      $user->save();

      return $this->sendResponse($user->toArray());
    }
}
