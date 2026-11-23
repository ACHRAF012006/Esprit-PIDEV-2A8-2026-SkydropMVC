<?php



/**
 * S1 - Administrator overview (back office, workshop Part 6): accounts by role and the newest accounts, then the tiles
 * that modules register for the "admin-dashboard" slot.
 */
final class AdminDashboardController extends Controller
{
    protected string $layout = 'admin';

    public function index(): void
    {
        Auth::requireRole('admin');
        $users = new AdminUserController();
        $byRole = $users->countByRole();
        $perWeek = $users->newAccountsPerWeek(8);
        $this->render('admin/dashboard', [
            'title' => 'Platform overview | SKYDROP Admin',
            'byRole' => $byRole,
            'roleLabels' => UserModel::ROLES,
            'total' => array_sum($byRole),
            'latest' => array_slice($users->getAllUsers(), 0, 5),
            'byStatus' => $users->countByStatus(),
            'weekLabels' => array_map(static fn (array $w): string => date('j M', strtotime($w['week'])), $perWeek),
            'weekCounts' => array_column($perWeek, 'n'),
            'pageScripts' => ['js/dashboard.js'],
        ]);
    }
}
