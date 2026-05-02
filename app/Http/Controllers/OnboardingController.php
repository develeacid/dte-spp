<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;

class OnboardingController extends Controller
{
    public function showSetPassword(string $token)
    {
        $user = $this->findUserByToken($token);

        if (! $user) {
            return view('onboarding.invalid-token');
        }

        // Si ya tiene contraseña, saltar al 2FA
        if ($user->password !== null) {
            return redirect()->route('activar.2fa', ['token' => $token]);
        }

        return view('onboarding.set-password', compact('user', 'token'));
    }

    public function storePassword(Request $request, string $token)
    {
        $user = $this->findUserByToken($token);

        if (! $user) {
            return view('onboarding.invalid-token');
        }

        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('activar.2fa', ['token' => $token]);
    }

    public function showSetup2fa(string $token)
    {
        $user = $this->findUserByToken($token);

        if (! $user) {
            return view('onboarding.invalid-token');
        }

        if ($user->password === null) {
            return redirect()->route('activar.show', ['token' => $token]);
        }

        // Habilitar 2FA si no está habilitado (genera secret y QR)
        if (! $user->two_factor_secret) {
            Auth::login($user);
            app(EnableTwoFactorAuthentication::class)($user);
            $user->refresh();
        } else {
            Auth::login($user);
        }

        return view('onboarding.setup-2fa', [
            'user' => $user,
            'token' => $token,
            'qrCodeSvg' => $user->twoFactorQrCodeSvg(),
            'setupKey' => decrypt($user->two_factor_secret),
        ]);
    }

    public function confirm2fa(Request $request, string $token)
    {
        $user = $this->findUserByToken($token);

        if (! $user) {
            return view('onboarding.invalid-token');
        }

        $request->validate([
            'code' => ['required', 'string'],
        ]);

        Auth::login($user);

        try {
            app(ConfirmTwoFactorAuthentication::class)($user, $request->code);
        } catch (\Exception $e) {
            return back()->withErrors(['code' => 'El código ingresado no es válido. Intenta de nuevo.']);
        }

        // Activar la cuenta
        $user->update([
            'activated_at' => now(),
            'invitation_token' => null,
        ]);

        $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);

        return view('onboarding.complete', [
            'user' => $user,
            'recoveryCodes' => $recoveryCodes,
        ]);
    }

    private function findUserByToken(string $token): ?User
    {
        $user = User::where('invitation_token', $token)->first();

        if (! $user) {
            return null;
        }

        // Ya activado
        if ($user->isActivated()) {
            return null;
        }

        // Token expirado
        if ($user->isInvitationExpired()) {
            return null;
        }

        return $user;
    }
}
