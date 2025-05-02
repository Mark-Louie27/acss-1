<?php
class PublicController
{
    public function showHomepage()
    {
        require_once __DIR__ . '/../views/home.php';
    }

    public function searchSchedules()
    {
        // Placeholder for schedule search
        header('Content-Type: application/json');
        echo json_encode(['schedules' => []]);
        exit;
    }
}
