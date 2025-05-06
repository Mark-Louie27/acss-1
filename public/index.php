<?php
require __DIR__ . '/../vendor/autoload.php';

// Load environment
Dotenv\Dotenv::createImmutable(__DIR__ . '/../')->load();

// Initialize secure session
session_start([
    'cookie_lifetime' => 86400,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'cookie_httponly' => true,
    'use_strict_mode' => true
]);

// Include middleware
require_once __DIR__ . '/../src/middleware/AuthMiddleware.php';

// Define route handler functions
function handleAdminRoutes($path)
{
    AuthMiddleware::handle('admin'); // Require admin role
    http_response_code(404);
    echo "Admin routes not implemented";
    exit;
}

function handleVpaaRoutes($path)
{
    AuthMiddleware::handle('vpaa'); // Require vpaa role
    http_response_code(404);
    echo "VPAA routes not implemented";
    exit;
}

function handleDiRoutes($path)
{
    AuthMiddleware::handle('di'); // Require di role
    http_response_code(404);
    echo "DI routes not implemented";
    exit;
}

function handleDeanRoutes($path)
{
    error_log("Entering handleDeanRoutes with path: $path");
    AuthMiddleware::handle('dean'); // Require dean role

    require_once __DIR__ . '/../src/controllers/DeanController.php';
    $controller = new DeanController();

    // Normalize path for comparison
    $normalizedPath = '/' . trim($path, '/');
    error_log("Normalized path: $normalizedPath");

    switch ($normalizedPath) {
        case '/dean/dashboard':
            $controller->dashboard();
            break;

        case '/dean/schedule':
            $controller->mySchedule();
            break;

        case '/dean/classroom':
            $controller->classroom();
            break;

        case '/dean/faculty':
            $controller->faculty();
            break;

        case '/dean/search':
            $controller->search();
            break;

        case '/dean/courses':
            $controller->courses();
            break;

        case '/dean/curriculum':
            $controller->curriculum();
            break;

        case '/dean/profile':
            $controller->profile();
            break;

        case '/dean/settings':
            $controller->settings();
            break;

        case '/dean/logout':
            error_log("Routing to AuthController::logout");
            require_once __DIR__ . '/../src/controllers/AuthController.php';
            (new AuthController())->logout();
            exit;

        default:
            http_response_code(404);
            echo "Page not found";
            exit;
    }
}

function handleChairRoutes($path)
{
    error_log("Entering handleChairRoutes with path: $path");
    AuthMiddleware::handle('chair');

    require_once __DIR__ . '/../src/controllers/ChairController.php';
    $controller = new ChairController();

    // Normalize path for comparison
    $normalizedPath = '/' . trim($path, '/');
    error_log("Normalized path: $normalizedPath");

    switch ($normalizedPath) {
        case '/chair/dashboard':
            error_log("Routing to ChairController::dashboard");
            $controller->dashboard();
            exit;
        case '/chair/my_schedule':
            error_log("Routing to ChairController::my_schedule");
            $controller->mySchedule();
            exit;
        case '/chair/schedule_management':
            error_log("Routing to ChairController::createSchedule");
            $controller->manageSchedule();
            exit;
        case '/chair/classroom':
            error_log("Routing to ChairController::classroom");
            $controller->classroom();
            exit;
        case '/chair/sections':
            error_log("Routing to ChairController::sections");
            $controller->sections();
            exit;
        case '/chair/faculty':
            error_log("Routing to ChairController::faculty");
            $controller->faculty();
            exit;
        case '/chair/faculty/search':
            error_log("Routing to ChairController::search");
            $controller->search();
            exit;
        case '/chair/courses':
            error_log("Routing to ChairController::courses");
            $controller->courses();
            exit;
        case '/chair/curriculum':
            error_log("Routing to ChairController::curriculum");
            $controller->curriculum();
            exit;
        case '/chair/profile':
            error_log("Routing to ChairController::profile");
            $controller->profile();
            exit;
        case '/chair/logout':
            error_log("Routing to AuthController::logout");
            require_once __DIR__ . '/../src/controllers/AuthController.php';
            (new AuthController())->logout();
            exit;
        default:
            error_log("No matching chair route for: $normalizedPath");
            http_response_code(404);
            echo "404 Not Found: $normalizedPath";
            exit;
    }
}

function handleFacultyRoutes($path)
{
    AuthMiddleware::handle('faculty'); // Require faculty role
    http_response_code(404);
    echo "Faculty routes not implemented";
    exit;
}

// Simple router
$path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

// Public routes that don't require authentication
$publicRoutes = ['login', 'register', '', 'home', 'public/search', 'api/departments'];

if (in_array($path, $publicRoutes)) {
    require_once __DIR__ . '/../src/controllers/AuthController.php';
    $controller = new AuthController();

    switch ($path) {
        case 'login':
            $controller->login();
            break;
        case 'register':
            $controller->register();
            break;
        case '':
        case 'home':
            require_once __DIR__ . '/../src/controllers/PublicController.php';
            (new PublicController())->showHomepage();
            break;
        case 'public/search':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                require_once __DIR__ . '/../src/controllers/PublicController.php';
                (new PublicController())->searchSchedules();
            } else {
                http_response_code(405);
                echo "Method Not Allowed";
            }
            break;
        case 'public/sections':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                require_once __DIR__ . '/../src/controllers/PublicController.php';
                (new PublicController())->getDepartmentSections();
            } else {
                http_response_code(405);
                echo "Method Not Allowed";
            }
            break;
        case 'public/departments':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                require_once __DIR__ . '/../src/controllers/PublicController.php';
                (new PublicController())->getCollegeDepartments();
            } else {
                http_response_code(405);
                echo "Method Not Allowed";
            }
            break;
        case 'api/departments':
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $controller->getDepartments();
            } else {
                http_response_code(405);
                echo "Method Not Allowed";
            }
            break;
        default:
            http_response_code(404);
            echo "Page not found";
            break;
    }
    exit;
}

// Protected routes - require authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

// Get user role from session
$roleId = $_SESSION['role_id'] ?? null;

// Handle role-specific routes
switch ($roleId) {
    case 1: // Admin
        handleAdminRoutes($path);
        break;
    case 2: // VPAA
        handleVpaaRoutes($path);
        break;
    case 3: // DI
        handleDiRoutes($path);
        break;
    case 4: // Dean
        handleDeanRoutes($path);
        break;
    case 5: // Chair
        handleChairRoutes($path);
        break;
    case 6: // Faculty
        handleFacultyRoutes($path);
        break;
    default:
        http_response_code(403);
        echo "Unauthorized role";
        exit;
}

// If no route matched, show 404
http_response_code(404);
echo "Page not found";
exit;
