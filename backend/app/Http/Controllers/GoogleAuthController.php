<?php

namespace App\Http\Controllers;

use App\Services\GoogleClientService;
use Illuminate\Http\Request;

class GoogleAuthController extends Controller
{
    public function callback(Request $request, GoogleClientService $clientService)
    {
        if ($request->has('error')) {
            session()->flash('google_error', 'خطا در احراز هویت گوگل: ' . $request->get('error'));
            return redirect('/admin/google-settings');
        }

        if (!$request->has('code')) {
            session()->flash('google_error', 'کد احراز هویت دریافت نشد.');
            return redirect('/admin/google-settings');
        }

        try {
            $clientService->authenticate($request->get('code'));
            session()->flash('google_success', 'اتصال به حساب گوگل با موفقیت برقرار شد.');
            return redirect('/admin/google-settings');
        } catch (\Exception $e) {
            session()->flash('google_error', 'خطا در اتصال به گوگل: ' . $e->getMessage());
            return redirect('/admin/google-settings');
        }
    }
}
