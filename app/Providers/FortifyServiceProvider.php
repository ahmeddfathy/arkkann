<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        // إضافة حدث نجاح تسجيل الدخول لتحديث متغير الجلسة
        Fortify::authenticateUsing(function (Request $request) {
            $user = \App\Models\User::where('email', $request->email)->first();

            if ($user &&
                \Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
                return $user;
            }
        });

        // الاستماع لحدث تسجيل الدخول لإنشاء توكن جديد
        Event::listen(Login::class, function (Login $event) {
            Log::info('User logged in, preparing to update FCM token', [
                'user_id' => $event->user->id
            ]);

            // يمكن إضافة منطق هنا لإنشاء توكن جديد إذا لزم الأمر
            // على سبيل المثال، يمكن تعيين علامة في الجلسة لتحديث التوكن في الواجهة
            session(['new_login' => true]);
        });

        // إضافة معالج لحدث تسجيل الخروج لحذف التوكن
        Event::listen(Logout::class, function (Logout $event) {
            if ($event->user) {
                Log::info('User logged out, removing tokens', [
                    'user_id' => $event->user->id
                ]);

                // حذف توكنات Sanctum إذا كانت موجودة
                if (class_exists('\Laravel\Sanctum\PersonalAccessToken')) {
                    DB::table('personal_access_tokens')
                        ->where('tokenable_id', $event->user->id)
                        ->where('tokenable_type', get_class($event->user))
                        ->delete();
                }

                // تحديث توكن FCM
                \App\Models\User::where('id', $event->user->id)
                    ->update(['fcm_token' => null]);
            }
        });

        // إضافة معالج لاستجابة تسجيل الخروج
        $this->app->singleton(\Laravel\Fortify\Contracts\LogoutResponse::class, function () {
            return new class implements \Laravel\Fortify\Contracts\LogoutResponse {
                public function toResponse($request)
                {
                    return redirect('/');
                }
            };
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
