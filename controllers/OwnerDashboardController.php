<?php


/**
 * S1 - Overview of the shop owner / advertiser back office. Modules add their tiles through the
 * "owner-dashboard" slot (my shop and plan, products, orders, ads...).
 */
final class OwnerDashboardController extends Controller
{
    protected string $layout = 'owner';

    public function index(): void
    {
        Auth::requireRole('shop_owner', 'advertiser', 'admin');
        $user = (new AuthController())->findById((int) Auth::id()) ?? abort(403);
        $this->render('owner/dashboard', [
            'title' => 'Overview | SKYDROP Back office',
            'user' => $user,
        ]);
    }
}
