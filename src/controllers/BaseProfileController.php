<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../repositories/ProfileRepository.php';
require_once __DIR__ . '/../repositories/SpecializationRepository.php';
require_once __DIR__ . '/../repositories/CourseRepository.php';
require_once __DIR__ . '/../services/ProfilePictureService.php';

use Src\Repositories\ProfileRepository;
use Src\Repositories\SpecializationRepository;
use Src\Repositories\CourseRepository;
use Src\Services\ProfilePictureService;

/**
 * BaseProfileController
 * ─────────────────────
 * Extends BaseController, which already owns:
 *   • $this->db          (PDO connection)
 *   • $this->userRoles   (from $_SESSION['roles'])
 *   • requireRole()
 *   • requireAnyRole()
 *   • getCurrentUserId()
 *
 * This class adds shared profile() and searchCourses() logic used by all
 * four role controllers. It does NOT declare its own __construct(); each child
 * continues to call parent::__construct() normally, then adds one extra line:
 *
 *     $this->initProfileDependencies();
 *
 * That one call wires up the four repository/service objects using the $db
 * and $authService that the child has already set up.
 *
 * Child classes must set these four properties:
 *
 *   protected string $redirectPath      — e.g. '/chair/profile'
 *   protected string $viewPath          — e.g. __DIR__.'/../views/chair/profile.php'
 *   protected string $fallbackRoleName  — e.g. 'Program Chair'
 *   protected bool   $withExpertiseLevel — true only for DirectorController
 *
 * Child classes that need a role guard override profile() and call
 * parent::profile() inside:
 *
 *   public function profile(): void {
 *       $this->requireAnyRole('chair', 'dean');
 *       parent::profile();
 *   }
 */
abstract class BaseProfileController extends BaseController
{
    // ── Child classes configure these four properties ─────────────────────────
    protected string $redirectPath      = '';
    protected string $viewPath          = '';
    protected string $fallbackRoleName  = '';
    protected bool   $withExpertiseLevel = false;

    // ── $authService is declared `private` in each child controller. ─────────
    // ── Do NOT redeclare it here — a protected base declaration would         ──
    // ── conflict with the child's private one (PHP visibility rules).         ──
    // ── $this->authService resolves correctly at runtime through the child.   ──

    // ── Wired up by initProfileDependencies() ────────────────────────────────
    protected ProfileRepository        $profileRepo;
    protected SpecializationRepository $specializationRepo;
    protected CourseRepository         $courseRepo;
    protected ProfilePictureService    $pictureService;

    // =========================================================================
    // Call this at the END of each child's __construct(),
    // AFTER $this->authService has been assigned.
    // BaseController::__construct() has already set $this->db.
    // =========================================================================
    protected function initProfileDependencies(): void
    {
        $this->profileRepo        = new ProfileRepository($this->db);
        $this->specializationRepo = new SpecializationRepository($this->db);
        $this->courseRepo         = new CourseRepository($this->db);
        $this->pictureService     = new ProfilePictureService($this->db);
    }

    // =========================================================================
    // searchCourses — shared across all four controllers
    // =========================================================================

    public function searchCourses(): void
    {
        try {
            if (!$this->authService->isLoggedIn()) {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized']);
                exit;
            }

            $query   = trim($_GET['query'] ?? '');
            $courses = $this->courseRepo->search($query);

            header('Content-Type: application/json');
            echo json_encode($courses);
        } catch (PDOException $e) {
            http_response_code(500);
            error_log('searchCourses PDO [' . static::class . ']: ' . $e->getMessage());
            echo json_encode(['error' => 'Database error while fetching courses.']);
        } catch (Exception $e) {
            http_response_code(500);
            error_log('searchCourses [' . static::class . ']: ' . $e->getMessage());
            echo json_encode(['error' => 'An error occurred while fetching courses.']);
        }
        exit;
    }

    // =========================================================================
    // profile — shared GET / POST handler
    // =========================================================================

    public function profile(): void
    {
        try {
            if (!$this->authService->isLoggedIn()) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Please log in to view your profile.'];
                header('Location: /login');
                exit;
            }

            $userId    = (int) $_SESSION['user_id'];
            $csrfToken = $this->authService->generateCsrfToken(); // available to view via $csrfToken

            // ── POST ─────────────────────────────────────────────────────────
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->handlePost($userId);
                header('Location: ' . $this->redirectPath);
                exit;
            }

            // ── GET — build all variables the view expects ────────────────────
            $user            = $this->profileRepo->getUserWithStats($userId);
            $specializations = $this->profileRepo->getSpecializations($userId);

            if (!$user) {
                throw new Exception('User not found.');
            }

            $facultyCount           = (int) ($user['facultyCount']          ?? 0);
            $coursesCount           = (int) ($user['coursesCount']           ?? 0);
            $specializationsCount   = (int) ($user['specializationsCount']   ?? 0);
            $pendingApplicantsCount = (int) ($user['pendingApplicantsCount'] ?? 0);
            $currentSemester        =       $user['currentSemester']         ?? '2nd';
            $lastLogin              =       $user['lastLogin']               ?? 'N/A';

            require_once $this->viewPath;
        } catch (Exception $e) {
            if (isset($this->db) && $this->db->inTransaction()) {
                $this->db->rollBack();
            }

            error_log('profile error [' . static::class . ']: ' . $e->getMessage());
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to load profile. Please try again.'];

            // Safe blank state — view will never crash on missing variables
            $user            = $this->blankUser($userId ?? 0);
            $specializations = [];
            $csrfToken       = '';
            $facultyCount    = $coursesCount = $specializationsCount = $pendingApplicantsCount = 0;
            $currentSemester = '2nd';
            $lastLogin       = 'N/A';

            require_once $this->viewPath;
        }
    }

    // =========================================================================
    // POST pipeline — private, called only from profile()
    // =========================================================================

    private function handlePost(int $userId): void
    {
        if (!$this->authService->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid CSRF token.'];
            return;
        }

        $data   = $this->collectPostData();
        $errors = [];

        try {
            $this->db->beginTransaction();

            // ── Profile picture ───────────────────────────────────────────────
            $picturePath = null;
            try {
                $picturePath = $this->pictureService->upload($userId);
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }

            // ── users table ───────────────────────────────────────────────────
            $this->validateUserFields($data, $errors);

            if (empty($errors)) {
                $userFields = array_intersect_key($data, array_flip([
                    'email',
                    'phone',
                    'username',
                    'first_name',
                    'middle_name',
                    'last_name',
                    'suffix',
                    'title',
                ]));
                if ($picturePath) {
                    $userFields['profile_picture'] = $picturePath;
                }
                $this->profileRepo->updateUser($userId, $userFields);
            }

            // ── faculty table ─────────────────────────────────────────────────
            $facultyId = $this->profileRepo->getFacultyId($userId);
            if (!$facultyId) {
                throw new Exception('Faculty record not found for this user.');
            }

            if (empty($errors)) {
                $facultyFields = array_intersect_key($data, array_flip([
                    'academic_rank',
                    'employment_type',
                    'classification',
                    'designation',
                    'advisory_class',
                    'bachelor_degree',
                    'master_degree',
                    'doctorate_degree',
                    'post_doctorate_degree',
                ]));
                $this->profileRepo->updateFaculty($facultyId, $facultyFields);
            }

            // ── Specialization action ─────────────────────────────────────────
            if (empty($errors) && !empty($data['action'])) {
                $this->handleSpecializationAction($data, $facultyId, $errors);
            }

            // ── Commit or rollback ────────────────────────────────────────────
            if (!empty($errors)) {
                $this->db->rollBack();
            } else {
                $this->db->commit();
                $this->syncSession($data, $picturePath);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Profile updated successfully.'];
            }
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log('profile POST PDO [' . static::class . ']: ' . $e->getMessage());
            $errors[] = 'Database error: ' . $e->getMessage();
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log('profile POST [' . static::class . ']: ' . $e->getMessage());
            $errors[] = $e->getMessage();
        }

        if (!empty($errors)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => implode('<br>', $errors)];
        }
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function collectPostData(): array
    {
        $p = fn(string $key): string => trim($_POST[$key] ?? '');

        return [
            // user fields
            'email'                 => $p('email'),
            'phone'                 => $p('phone'),
            'username'              => $p('username'),
            'first_name'            => $p('first_name'),
            'middle_name'           => $p('middle_name'),
            'last_name'             => $p('last_name'),
            'suffix'                => $p('suffix'),
            'title'                 => $p('title'),
            // faculty fields
            'classification'        => $p('classification'),
            'academic_rank'         => $p('academic_rank'),
            'employment_type'       => $p('employment_type'),
            'bachelor_degree'       => $p('bachelor_degree'),
            'master_degree'         => $p('master_degree'),
            'doctorate_degree'      => $p('doctorate_degree'),      // fixed: was 'dpost_doctorate_degree' in DirectorController
            'post_doctorate_degree' => $p('post_doctorate_degree'), // fixed: was mapped to bachelor_degree in DirectorController
            'advisory_class'        => $p('advisory_class'),
            'designation'           => $p('designation'),
            // specialization fields
            'expertise_level'       => $p('expertise_level'),
            'course_id'             => $p('course_id'),
            'specialization_index'  => $p('specialization_index'),
            'action'                => $p('action'),
        ];
    }

    private function validateUserFields(array $data, array &$errors): void
    {
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        }
        if (!empty($data['phone']) && !preg_match('/^\d{10,12}$/', $data['phone'])) {
            $errors[] = 'Phone number must be 10–12 digits.';
        }
    }

    private function handleSpecializationAction(array $data, int $facultyId, array &$errors): void
    {
        $courseId = (int) ($data['course_id'] ?? 0);

        try {
            switch ($data['action']) {

                case 'add_specialization':
                    if (!$courseId) {
                        $errors[] = 'A course is required to add a specialization.';
                        break;
                    }
                    $level = null;
                    if ($this->withExpertiseLevel) {
                        if (empty($data['expertise_level'])) {
                            $errors[] = 'Expertise level is required to add a specialization.';
                            break;
                        }
                        $level = $data['expertise_level'];
                    }
                    $this->specializationRepo->add($facultyId, $courseId, $level);
                    break;

                case 'remove_specialization':
                    if (!$courseId) {
                        $errors[] = 'A course ID is required to remove a specialization.';
                        break;
                    }
                    $this->specializationRepo->remove($facultyId, $courseId);
                    break;

                case 'update_specialization':
                    // Director only
                    if (!$courseId || empty($data['expertise_level'])) {
                        $errors[] = 'Course ID and expertise level are required to update a specialization.';
                        break;
                    }
                    $this->specializationRepo->update($facultyId, $courseId, $data['expertise_level']);
                    break;

                case 'edit_specialization':
                    // Client-side modal trigger only — no DB write needed
                    break;

                default:
                    error_log('profile: unknown action "' . $data['action'] . '" [' . static::class . ']');
                    break;
            }
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }

    private function syncSession(array $data, ?string $picturePath): void
    {
        foreach (['username', 'last_name', 'middle_name', 'suffix', 'title', 'first_name', 'email'] as $key) {
            if (!empty($data[$key])) {
                $_SESSION[$key] = $data[$key];
            }
        }
        if ($picturePath) {
            $_SESSION['profile_picture'] = $picturePath;
        }
    }

    private function blankUser(int $userId): array
    {
        return [
            'user_id'               => $userId,
            'username'              => '',
            'first_name'            => '',
            'last_name'             => '',
            'middle_name'           => '',
            'suffix'                => '',
            'email'                 => '',
            'phone'                 => '',
            'title'                 => '',
            'profile_picture'       => '',
            'employee_id'           => '',
            'department_name'       => '',
            'college_name'          => '',
            'role_name'             => $this->fallbackRoleName,
            'academic_rank'         => '',
            'employment_type'       => '',
            'classification'        => '',
            'bachelor_degree'       => '',
            'master_degree'         => '',
            'doctorate_degree'      => '',
            'post_doctorate_degree' => '',
            'advisory_class'        => '',
            'designation'           => '',
            'updated_at'            => date('Y-m-d H:i:s'),
        ];
    }
}
