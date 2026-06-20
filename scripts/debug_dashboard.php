<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use App\Models\User;
use Illuminate\Http\Request;

$kernel->bootstrap();

// Create user
$user = User::factory()->create();

// Simulate a session and authenticate
session()->put('login_test', true);
auth()->login($user);

$request = Request::create(route('dashboard'), 'GET');
$response = $kernel->handle($request);

echo "Status: " . $response->getStatusCode() . "\n";
if ($response->isRedirection()) {
    echo "Location: " . $response->headers->get('Location') . "\n";
}

$kernel->terminate($request, $response);
