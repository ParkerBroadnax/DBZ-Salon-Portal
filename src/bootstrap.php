<?php

declare(strict_types=1);

use Salon\BookingService;
use Salon\Csrf;
use Salon\Database;
use Salon\Env;
use Salon\MessageService;
use Salon\ServiceRepository;

$root = dirname(__DIR__);

if (is_file($root . '/vendor/autoload.php')) {
    require $root . '/vendor/autoload.php';
} else {
    spl_autoload_register(static function (string $class) use ($root): void {
        if (str_starts_with($class, 'Salon\\')) {
            $file = $root . '/src/' . str_replace('\\', '/', substr($class, 6)) . '.php';
            if (is_file($file)) {
                require $file;
            }
        }
    });
    require_once $root . '/src/helpers.php';
}

Env::load($root . '/.env');

// Log errors instead of hiding them (the original used error_reporting(0)) or showing them to visitors.
error_reporting(E_ALL);
ini_set('display_errors', Env::get('APP_DEBUG') === '1' ? '1' : '0');
ini_set('log_errors', '1');

if (session_status() === PHP_SESSION_NONE && PHP_SAPI !== 'cli') {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'cookie_secure' => !empty($_SERVER['HTTPS']),
    ]);
}

$db = Database::fromEnv();
$serviceRepo = new ServiceRepository($db);
$services = $serviceRepo->all();

/**
 * Where a form handler redirects after a POST: the current page by file name, never a path taken
 * from the request, so a URL like //example.com/... can't turn into a redirect to another site.
 */
function redirect_target(string $anchor): string
{
    return basename($_SERVER['SCRIPT_NAME'] ?? 'index.php') . $anchor;
}

/**
 * Handle a booking POST, then redirect (Post/Redirect/Get) so a refresh can't double-book.
 */
function handle_booking(PDO $db, ServiceRepository $serviceRepo): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !isset($_POST['book'])) {
        return;
    }

    $target = redirect_target('#book');

    if (!Csrf::valid($_POST['csrf'] ?? null)) {
        $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Your session expired. Please try again.'];
        header('Location: ' . $target, true, 303);
        exit;
    }

    $booking = new BookingService($db, $serviceRepo);
    $result = $booking->validate($_POST);

    if ($result['errors']) {
        $_SESSION['flash'] = ['type' => 'danger', 'text' => implode(' ', $result['errors'])];
        $_SESSION['old'] = old_input($_POST, ['name', 'email', 'phone', 'adate', 'atime', 'service_id']);
    } else {
        try {
            $number = $booking->create($result['data']);
            $_SESSION['flash'] = [
                'type' => 'success',
                'text' => "Thank you for booking. Your appointment number is {$number}. We will call you shortly to confirm.",
            ];
        } catch (Throwable $e) {
            error_log('Booking failed: ' . $e->getMessage());
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Something went wrong. Please try again.'];
        }
    }

    header('Location: ' . $target, true, 303);
    exit;
}

/**
 * Handle a contact form POST, then redirect (Post/Redirect/Get) so a refresh can't resend it.
 */
function handle_contact(PDO $db): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !isset($_POST['contact'])) {
        return;
    }

    $target = redirect_target('#contact');

    if (!Csrf::valid($_POST['csrf'] ?? null)) {
        $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Your session expired. Please try again.'];
        header('Location: ' . $target, true, 303);
        exit;
    }

    $messages = new MessageService($db);
    $result = $messages->validate($_POST);

    if ($result['errors']) {
        $_SESSION['flash'] = ['type' => 'danger', 'text' => implode(' ', $result['errors'])];
        $_SESSION['old'] = old_input($_POST, ['name', 'email', 'subject', 'body']);
    } else {
        try {
            $messages->create($result['data']);
            $_SESSION['flash'] = ['type' => 'success', 'text' => 'Thank you for your message. We will get back to you soon.'];
        } catch (Throwable $e) {
            error_log('Contact message failed: ' . $e->getMessage());
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Something went wrong. Please try again.'];
        }
    }

    header('Location: ' . $target, true, 303);
    exit;
}

/** Read and clear one-time messages and previous input for the booking and contact forms. */
function take_flash(): array
{
    $flash = $_SESSION['flash'] ?? null;
    $old = $_SESSION['old'] ?? [];
    unset($_SESSION['flash'], $_SESSION['old']);
    return [$flash, $old];
}
