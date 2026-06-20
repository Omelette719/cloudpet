<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Notifications\ResetPassword;

Notification::fake();

$user = User::first() ?: User::factory()->create();

Password::broker()->sendResetLink(['email' => $user->email]);

$sent = Notification::sent($user, ResetPassword::class);
var_export($sent);

return 0;
