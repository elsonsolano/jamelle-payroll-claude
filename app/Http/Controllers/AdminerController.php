<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminerController extends Controller
{
    public function __invoke(Request $request)
    {
        $adminerPath = storage_path('app/adminer.php');

        if (!file_exists($adminerPath)) {
            abort(503, 'Adminer is not installed. Place adminer.php in storage/app/adminer.php and redeploy.');
        }

        if (!function_exists('adminer_object')) {
            function adminer_object()
            {
                class AdminerAutoLogin extends Adminer
                {
                    public function login($login, $password)
                    {
                        // Any credentials pass — access is already gated by Laravel's super-admin middleware.
                        return true;
                    }

                    public function credentials()
                    {
                        $conn = config('database.connections.' . config('database.default'));
                        return [$conn['host'], $conn['username'], $conn['password']];
                    }

                    public function database()
                    {
                        return config('database.connections.' . config('database.default'))['database'];
                    }
                }

                return new AdminerAutoLogin();
            }
        }

        include $adminerPath;
        exit;
    }
}
