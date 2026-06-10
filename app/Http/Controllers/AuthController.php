<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function showLogin()
    {
        return Inertia::render('Auth/Login');
    }

    public function showRegister()
    {
        return Inertia::render('Auth/Register');
    }

    public function showPasswordReset()
    {
        return Inertia::render('Auth/PasswordReset');
    }

    public function passwordReset(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::where('email', $validated['email'])->first();
        
        if (!$user) {
            return back()->withErrors(['email' => 'Пользователь с таким email не найден']);
        }

        $user->password = $validated['password'];
        $user->save();

        return redirect('/login')->with('success', 'Пароль успешно изменен');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            
            if (Auth::user()->isAdmin()) {
                return redirect('/admin/dashboard');
            }
            
            return redirect('/');
        }

        return back()->withErrors(['email' => 'Неверный email или пароль']);
    }

    public function register(Request $request)
    {
        $messages = [
            'name.required' => 'Поле "Имя" обязательно для заполнения.',
            'name.string' => 'Поле "Имя" должно быть строкой.',
            'name.max' => 'Поле "Имя" не может быть длиннее 255 символов.',
            'email.required' => 'Поле "Email" обязательно для заполнения.',
            'email.email' => 'Поле "Email" должно быть действительным адресом электронной почты.',
            'email.unique' => 'Пользователь с таким email уже зарегистрирован.',
            'password.required' => 'Поле "Пароль" обязательно для заполнения.',
            'password.min' => 'Пароль должен быть не менее 8 символов.',
            'password.confirmed' => 'Пароли не совпадают.',
        ];

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ], $messages);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => 'user',
        ]);

        Auth::login($user);
        return redirect('/');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    /**
     * Перенаправление на авторизацию через Яндекс.
     */
    public function redirectToYandex()
    {
        // stateless: не хранит OAuth state в сессии — иначе после редиректа с Яндекса
        // часто ломается проверка state (localhost vs 127.0.0.1, новая сессия и т.д.).
        return Socialite::driver('yandex')->stateless()->redirect();
    }

    /**
     * Callback после авторизации в Яндекс.
     */
    public function handleYandexCallback(Request $request)
    {
        try {
            $yandexUser = Socialite::driver('yandex')->stateless()->user();
        } catch (\Throwable $e) {
            Log::warning('yandex_oauth_failed', ['message' => $e->getMessage()]);

            return redirect('/login')->withErrors([
                'email' => 'Не удалось авторизоваться через Яндекс. Попробуйте еще раз.',
            ]);
        }

        $yandexId = (string) $yandexUser->getId();

        $rawEmail = $yandexUser->getEmail();
        $emailFromYandex = is_string($rawEmail) && filter_var($rawEmail, FILTER_VALIDATE_EMAIL)
            ? $rawEmail
            : null;

        $name = trim((string) ($yandexUser->getName() ?: $yandexUser->getNickname())) ?: 'Пользователь Яндекс';

        // Уникальный email в БД, если Яндекс не отдал почту (в кабинете OAuth включите доступ к email).
        $loginEmail = $emailFromYandex ?? ('yandex+'.$yandexId.'@oauth.internal');

        $user = User::where('yandex_id', $yandexId)->first();

        if (! $user && $emailFromYandex) {
            $user = User::where('email', $emailFromYandex)->first();
        }

        if (! $user) {
            $user = User::create([
                'name' => $name,
                'email' => $loginEmail,
                'yandex_email' => $emailFromYandex,
                'yandex_id' => $yandexId,
                'password' => Str::random(32),
                'role' => 'user',
            ]);
        } else {
            $updates = [
                'name' => $name,
                'yandex_id' => $yandexId,
                'yandex_email' => $emailFromYandex ?? $user->yandex_email,
            ];

            if ($emailFromYandex) {
                $taken = User::where('email', $emailFromYandex)->where('id', '!=', $user->id)->exists();
                if (! $taken) {
                    $updates['email'] = $emailFromYandex;
                }
            }

            $user->update($updates);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        // Используем route для надежности
        return redirect()->route('cabinet');
    }
}
