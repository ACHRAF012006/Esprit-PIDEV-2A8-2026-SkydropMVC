<?php

/**
 * S5 Karim - Back office of an advertiser or a shop owner: MY ads.
 * List (JOIN placements, status tags), create, edit while the ad is pending, delete while it is pending or rejected.
 * The price is days x the placement's price per day, computed here (never read from the form); assets/js/ads.js only
 * shows a preview before saving.
 * Security: Auth::requireRole('advertiser', 'shop_owner') first, Csrf::check() on every POST, and every query on an ad
 * has "AND a.advertiser_id = :me": an advertiser never sees or changes the ad of another one.
 */
final class OwnerAdController extends Controller
{
    protected string $layout = 'owner';
    private \PDO $db;

    /** Longest ad, in days (both dates included). */
    public const MAX_DAYS = 60;

    public function __construct()
    {
        $this->db = \Database::getConnexion();
    }

    public function index(): void
    {
        Auth::requireRole('advertiser', 'shop_owner');
        $stmt = $this->db->prepare(AdController::SELECT . ' WHERE a.advertiser_id = :me ORDER BY a.created_at DESC, a.id DESC');
        $stmt->execute(['me' => Auth::id()]);
        $this->render('owner/ads', [
            'title' => 'My ads | SKYDROP Back office',
            'ads' => array_map([AdModel::class, 'fromArray'], $stmt->fetchAll()),
            'today' => date('Y-m-d'),
        ]);
    }

    /** Create (no id) or edit (?id=, only while pending). */
    public function form(): void
    {
        Auth::requireRole('advertiser', 'shop_owner');
        $ad = null;
        if (isset($_GET['id'])) {
            $ad = $this->findMine((int) $_GET['id']) ?? abort(404);
            $this->onlyPending($ad);
        }
        $this->render('owner/ad-form', [
            'title' => ($ad ? 'Edit an ad' : 'Create an ad') . ' | SKYDROP Back office',
            'ad' => $ad,
            'placements' => (new AdminPlacementController())->active(),
            'today' => date('Y-m-d'),
            'maxDays' => self::MAX_DAYS,
            'pageStyles' => ['css/ads.css'],
            'pageScripts' => ['js/ads.js'],
        ] + $this->formState());
    }

    public function save(): void
    {
        Auth::requireRole('advertiser', 'shop_owner');
        Csrf::check();
        $id = (int) ($_POST['id'] ?? 0);
        $existing = $id > 0 ? ($this->findMine($id) ?? abort(404)) : null;
        if ($existing) {
            $this->onlyPending($existing);
        }

        $v = (new Validator($_POST))
            ->required('placement_id')
            ->required('title')->minLen('title', 5)->maxLen('title', 80)
            ->required('body')->minLen('body', 10)->maxLen('body', 300)
            ->maxLen('link', 255)
            ->required('starts_at')
            ->required('ends_at');
        $errors = $v->errors();

        // The placement must be one of the active placements of the list.
        $placement = null;
        if (!isset($errors['placement_id'])) {
            $placement = (new AdminPlacementController())->findById((int) $v->get('placement_id'));
            if ($placement === null || !$placement->isActive()) {
                $errors['placement_id'] = 'Choose a placement from the list.';
            }
        }

        // Link: empty, or a web address that starts with http:// or https://.
        $link = $v->get('link');
        if (!isset($errors['link']) && $link !== ''
            && (!preg_match('#^https?://\S+$#', $link) || filter_var($link, FILTER_VALIDATE_URL) === false)) {
            $errors['link'] = 'Write a link that starts with http:// or https://, or leave it empty.';
        }

        // Dates: the start today or later, the end on or after the start, at most MAX_DAYS days.
        $start = $this->date($v->get('starts_at'));
        $end = $this->date($v->get('ends_at'));
        if (!isset($errors['starts_at']) && $start === null) {
            $errors['starts_at'] = 'Write a date, for example 2026-12-01.';
        }
        if (!isset($errors['ends_at']) && $end === null) {
            $errors['ends_at'] = 'Write a date, for example 2026-12-01.';
        }
        $today = new \DateTimeImmutable('today');
        if (!isset($errors['starts_at']) && $start < $today) {
            $errors['starts_at'] = 'The start must be today or later.';
        }
        $days = 0;
        if ($start !== null && $end !== null && !isset($errors['ends_at'])) {
            $days = (int) $start->diff($end)->format('%r%a') + 1;
            if ($end < $start) {
                $errors['ends_at'] = 'The end must be on or after the start.';
            } elseif ($days > self::MAX_DAYS) {
                $errors['ends_at'] = 'An ad runs at most ' . self::MAX_DAYS . ' days.';
            }
        }

        if ($errors) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = array_intersect_key($_POST, array_flip(['placement_id', 'title', 'body', 'link', 'starts_at', 'ends_at']));
            $this->redirect($existing ? route('owner-ad-form', ['id' => $id]) : route('owner-ad-form'));
        }

        // Rule: the price is computed on the server, from the database price of the placement.
        $total = round($days * $placement->getPricePerDay(), 3);
        $args = [
            'placement' => $placement->getId(),
            'title' => $v->get('title'),
            'body' => $v->get('body'),
            'link' => $link !== '' ? $link : null,
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
            'price' => $total,
            'me' => Auth::id(),
        ];
        if ($existing) {
            $stmt = $this->db->prepare("UPDATE ads SET placement_id = :placement, title = :title, body = :body, link = :link,
                starts_at = :start, ends_at = :end, total_price = :price
                WHERE id = :id AND advertiser_id = :me AND status = 'pending'");
            $stmt->execute($args + ['id' => $id]);
        } else {
            $stmt = $this->db->prepare("INSERT INTO ads (advertiser_id, placement_id, title, body, link, starts_at, ends_at, total_price, status)
                VALUES (:me, :placement, :title, :body, :link, :start, :end, :price, 'pending')");
            $stmt->execute($args);
        }
        flash('success', sprintf('Your ad "%s" is saved: %d day%s x %s TND = %s TND. An administrator checks it before it is shown.',
            $args['title'], $days, $days === 1 ? '' : 's', number_format($placement->getPricePerDay(), 3), number_format($total, 3)));
        $this->redirect(route('owner-ads'));
    }

    /** Rule: only a pending or rejected ad can be deleted (an approved ad is paid and planned). */
    public function delete(string $id): void
    {
        Auth::requireRole('advertiser', 'shop_owner');
        Csrf::check();
        $ad = $this->findMine((int) $id) ?? abort(404);
        if ($ad->getStatus() === 'approved') {
            flash('error', 'The ad "' . $ad->getTitle() . '" is approved, so it cannot be deleted. Only a pending or rejected ad can be deleted.');
        } else {
            $stmt = $this->db->prepare("DELETE FROM ads WHERE id = :id AND advertiser_id = :me AND status IN ('pending', 'rejected')");
            $stmt->execute(['id' => $ad->getId(), 'me' => Auth::id()]);
            flash('success', 'The ad "' . $ad->getTitle() . '" is deleted.');
        }
        $this->redirect(route('owner-ads'));
    }

    // =================================================================================================

    /** One of MY ads, or null (the ad of another advertiser is "not found", never shown). */
    private function findMine(int $id): ?AdModel
    {
        $stmt = $this->db->prepare(AdController::SELECT . ' WHERE a.id = :id AND a.advertiser_id = :me');
        $stmt->execute(['id' => $id, 'me' => Auth::id()]);
        $row = $stmt->fetch();
        return $row ? AdModel::fromArray($row) : null;
    }

    /** Rule: a pending ad can be edited, an approved or rejected one not. */
    private function onlyPending(AdModel $ad): void
    {
        if ($ad->getStatus() !== 'pending') {
            flash('error', 'The ad "' . $ad->getTitle() . '" is ' . strtolower($ad->getStatusLabel())
                . ', so it cannot be changed. Only a pending ad can be edited.');
            $this->redirect(route('owner-ads'));
        }
    }

    /** "2026-12-01" -> a date, or null when the text is not a real date in this format. */
    private function date(string $value): ?\DateTimeImmutable
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value ? $date : null;
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
