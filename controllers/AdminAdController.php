<?php

/**
 * S5 Karim - Administrator: every ad (JOIN placements and users), filter by status, one ad in detail,
 * approve, reject (with a review note shown to the advertiser), delete.
 * Rule checked when approving: a placement never has more than max_ads approved ads on the same day.
 * Auth::requireRole('admin') is the first line of every action, Csrf::check() the second of every POST.
 */
final class AdminAdController extends Controller
{
    protected string $layout = 'admin';
    private \PDO $db;

    public function __construct()
    {
        $this->db = \Database::getConnexion();
    }

    public function index(): void
    {
        Auth::requireRole('admin');
        $status = (string) ($_GET['status'] ?? '');
        $status = isset(AdModel::STATUSES[$status]) ? $status : ''; // only a key of the fixed list reaches the SQL
        $sql = AdController::SELECT . ($status !== '' ? ' WHERE a.status = :status' : '')
            . " ORDER BY FIELD(a.status, 'pending', 'approved', 'rejected'), a.starts_at, a.id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($status !== '' ? ['status' => $status] : []);
        $this->render('admin/ads', [
            'title' => 'Ads | SKYDROP Admin',
            'ads' => array_map([AdModel::class, 'fromArray'], $stmt->fetchAll()),
            'status' => $status,
            'statuses' => AdModel::STATUSES,
            'counts' => $this->countByStatus(),
        ]);
    }

    public function show(string $id): void
    {
        Auth::requireRole('admin');
        $ad = (new AdController())->findById((int) $id) ?? abort(404);
        $this->render('admin/ad', [
            'title' => $ad->getTitle() . ' | SKYDROP Admin',
            'ad' => $ad,
            'placement' => (new AdminPlacementController())->findById($ad->getPlacementId()),
            'pageStyles' => ['css/ads.css'],
        ] + $this->formState());
    }

    public function approve(string $id): void
    {
        Auth::requireRole('admin');
        Csrf::check();
        $ad = (new AdController())->findById((int) $id) ?? abort(404);
        $back = route('admin-ad', ['id' => $ad->getId()]);
        if ($ad->getStatus() !== 'pending') {
            flash('error', 'This ad is already ' . strtolower($ad->getStatusLabel()) . '. Only a pending ad can be approved or rejected.');
            $this->redirect($back);
        }
        $placement = (new AdminPlacementController())->findById($ad->getPlacementId()) ?? abort(404);
        $fullDay = $this->firstFullDay($ad, $placement->getMaxAds());
        if ($fullDay !== null) {
            flash('error', sprintf('%s already shows %d approved ad%s on %s (at most %d at the same time), so this ad cannot be approved for these days. '
                . 'Reject it with a note asking for other dates, or raise the limit of the placement.',
                $placement->getName(), $placement->getMaxAds(), $placement->getMaxAds() === 1 ? '' : 's', $fullDay, $placement->getMaxAds()));
            $this->redirect($back);
        }
        $stmt = $this->db->prepare("UPDATE ads SET status = 'approved', review_note = NULL WHERE id = :id AND status = 'pending'");
        $stmt->execute(['id' => $ad->getId()]);
        flash('success', 'The ad "' . $ad->getTitle() . '" is approved. It is shown from ' . $ad->getStartsAt() . ' to ' . $ad->getEndsAt() . '.');
        $this->redirect($back);
    }

    /** Rule: rejecting needs a review note; the advertiser reads it on their list of ads. */
    public function reject(string $id): void
    {
        Auth::requireRole('admin');
        Csrf::check();
        $ad = (new AdController())->findById((int) $id) ?? abort(404);
        $back = route('admin-ad', ['id' => $ad->getId()]);
        if ($ad->getStatus() !== 'pending') {
            flash('error', 'This ad is already ' . strtolower($ad->getStatusLabel()) . '. Only a pending ad can be approved or rejected.');
            $this->redirect($back);
        }
        $v = (new Validator($_POST))->required('review_note')->minLen('review_note', 5)->maxLen('review_note', 255);
        if ($v->fails()) {
            $_SESSION['errors'] = $v->errors();
            $_SESSION['old'] = ['review_note' => (string) ($_POST['review_note'] ?? '')];
            $this->redirect($back);
        }
        $stmt = $this->db->prepare("UPDATE ads SET status = 'rejected', review_note = :note WHERE id = :id AND status = 'pending'");
        $stmt->execute(['note' => $v->get('review_note'), 'id' => $ad->getId()]);
        flash('success', 'The ad "' . $ad->getTitle() . '" is rejected. The advertiser sees your note.');
        $this->redirect($back);
    }

    public function delete(string $id): void
    {
        Auth::requireRole('admin');
        Csrf::check();
        $ad = (new AdController())->findById((int) $id) ?? abort(404);
        $stmt = $this->db->prepare('DELETE FROM ads WHERE id = :id');
        $stmt->execute(['id' => $ad->getId()]);
        flash('success', 'The ad "' . $ad->getTitle() . '" is deleted.');
        $this->redirect(route('admin-ads'));
    }

    /**
     * For the tile of the admin overview (slot "admin-dashboard", views/admin/ad-approvals.php):
     * ['count' => number of pending ads, 'ads' => the $limit pending ads that start first]. Empty for a non-admin.
     */
    public function waitingForApproval(int $limit = 5): array
    {
        if (!Auth::hasRole('admin')) {
            return ['count' => 0, 'ads' => []];
        }
        $stmt = $this->db->prepare(AdController::SELECT . " WHERE a.status = 'pending' ORDER BY a.starts_at, a.id LIMIT :limit");
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return ['count' => $this->countByStatus()['pending'], 'ads' => array_map([AdModel::class, 'fromArray'], $stmt->fetchAll())];
    }

    // =================================================================================================

    /** status => number of ads (every status, 0 when none). */
    private function countByStatus(): array
    {
        $out = array_fill_keys(array_keys(AdModel::STATUSES), 0);
        foreach ($this->db->query('SELECT status, COUNT(*) AS n FROM ads GROUP BY status') as $row) {
            $out[$row['status']] = (int) $row['n'];
        }
        return $out;
    }

    /**
     * The first day of the ad on which its placement already has $maxAds approved ads, or null when every day has room.
     * The SQL fetches the approved ads of the placement that overlap the ad's dates; PHP then counts them day by day
     * (at most 60 days), so two approved ads that do not overlap each other are not counted together.
     */
    private function firstFullDay(AdModel $ad, int $maxAds): ?string
    {
        $stmt = $this->db->prepare("SELECT starts_at, ends_at FROM ads WHERE placement_id = :placement AND status = 'approved'
            AND id <> :id AND starts_at <= :end AND ends_at >= :start");
        $stmt->execute(['placement' => $ad->getPlacementId(), 'id' => $ad->getId(), 'start' => $ad->getStartsAt(), 'end' => $ad->getEndsAt()]);
        $others = $stmt->fetchAll();
        for ($day = $ad->getStartsAt(); $day <= $ad->getEndsAt(); $day = date('Y-m-d', strtotime($day . ' +1 day'))) {
            $count = 0;
            foreach ($others as $other) {
                if ($other['starts_at'] <= $day && $other['ends_at'] >= $day) {
                    $count++;
                }
            }
            if ($count >= $maxAds) {
                return $day;
            }
        }
        return null;
    }

    /** Errors and typed values of the last failed POST (kept in the session across the redirect), read once. */
    private function formState(): array
    {
        $errors = $_SESSION['errors'] ?? [];
        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['errors'], $_SESSION['old']);
        return ['errors' => $errors, 'old' => $old];
    }
}
