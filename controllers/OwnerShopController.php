<?php

/**
 * S2 Mili - Shop owner back office: create my shop when I have none, otherwise edit it.
 * owner_id is always Auth::id(), never a value of the form, and every UPDATE has "AND owner_id = :me".
 * Rules: name 3-80 and unique, one shop per owner, a new shop is pending, closes_at after opens_at,
 * Tunisian phone, an active plan; renaming an approved shop sends it back to pending.
 */
final class OwnerShopController extends Controller
{
    protected string $layout = 'owner';
    private \PDO $db;

    /** Same pattern and message in the view (data-pattern) and here. */
    public const PHONE_PATTERN = '^(\+216)?[0-9]{8}$';
    public const PHONE_MESSAGE = 'Write 8 digits, optionally after +216, for example 71123456.';
    public const TIME_PATTERN = '^([01][0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$';
    public const TIME_MESSAGE = 'Write a time such as 08:30.';
    public const HOURS_MESSAGE = 'The closing time must be after the opening time.';

    private const FIELDS = ['name', 'description', 'address', 'phone', 'opens_at', 'closes_at', 'lat', 'lng', 'plan_id'];

    public function __construct()
    {
        $this->db = \Database::getConnexion();
    }

    /** GET /owner/shop: the form, empty for a new shop or filled with my shop, plus its status and review note. */
    public function form(): void
    {
        Auth::requireRole('shop_owner');
        $shop = (new ShopController())->ofOwner((int) Auth::id());
        $this->render('owner/shop', [
            'title' => ($shop ? 'My shop' : 'Create my shop') . ' | SKYDROP Back office',
            'shop' => $shop,
            'plans' => $this->activePlans(),
            'phonePattern' => self::PHONE_PATTERN,
            'phoneMessage' => self::PHONE_MESSAGE,
            'timePattern' => self::TIME_PATTERN,
            'timeMessage' => self::TIME_MESSAGE,
            'pageScripts' => ['js/shops.js'],
        ] + $this->formState());
    }

    /** POST /owner/shop: validate, then INSERT my shop (pending) or UPDATE it. */
    public function save(): void
    {
        Auth::requireRole('shop_owner');
        Csrf::check();
        $me = (int) Auth::id();
        $shop = (new ShopController())->ofOwner($me); // one shop per owner: when I have one, this is an edit
        $plans = $this->activePlans();

        $v = (new Validator($_POST))
            ->required('name')->minLen('name', 3)->maxLen('name', 80)
            ->maxLen('description', 500)
            ->required('address')->minLen('address', 5)->maxLen('address', 160)
            ->required('phone')
            ->required('opens_at')
            ->required('closes_at')
            ->required('lat')->decimal('lat', -90, 90)
            ->required('lng')->decimal('lng', -180, 180)
            ->required('plan_id')->in('plan_id', array_map(static fn (ShopPlanModel $p): string => (string) $p->getId(), $plans));
        $errors = $v->errors();

        // Same messages as forms.js: it adds the range to "Number out of range" when there is a min and a max.
        foreach (['lat' => '(-90 to 90)', 'lng' => '(-180 to 180)'] as $field => $range) {
            if (($errors[$field] ?? '') === 'Number out of range.') {
                $errors[$field] = "Number out of range $range.";
            }
        }
        if (!isset($errors['phone']) && !preg_match('/' . self::PHONE_PATTERN . '/', $v->get('phone'))) {
            $errors['phone'] = self::PHONE_MESSAGE;
        }
        foreach (['opens_at', 'closes_at'] as $field) {
            if (!isset($errors[$field]) && !preg_match('/' . self::TIME_PATTERN . '/', $v->get($field))) {
                $errors[$field] = self::TIME_MESSAGE;
            }
        }
        $opens = substr($v->get('opens_at'), 0, 5);
        $closes = substr($v->get('closes_at'), 0, 5);
        if (!isset($errors['opens_at']) && !isset($errors['closes_at']) && $closes <= $opens) {
            $errors['closes_at'] = self::HOURS_MESSAGE;
        }
        if (!isset($errors['name']) && $this->nameTaken($v->get('name'), $shop ? $shop->getId() : 0)) {
            $errors['name'] = 'Another shop already uses this name.';
        }
        if ($errors) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = array_intersect_key($_POST, array_flip(self::FIELDS));
            $this->redirect(route('owner-shop'));
        }

        $args = [
            'name' => $v->get('name'),
            'description' => $v->get('description') !== '' ? $v->get('description') : null,
            'address' => $v->get('address'),
            'phone' => $v->get('phone'),
            'opens' => $opens,
            'closes' => $closes,
            'lat' => (float) $v->get('lat'),
            'lng' => (float) $v->get('lng'),
            'plan' => (int) $v->get('plan_id'),
        ];

        if ($shop === null) {
            // A new shop is always pending: it appears in the front office once an administrator approves it.
            $stmt = $this->db->prepare("INSERT INTO shops (owner_id, plan_id, name, description, address, phone, opens_at, closes_at, lat, lng, status)
                VALUES (:me, :plan, :name, :description, :address, :phone, :opens, :closes, :lat, :lng, 'pending')");
            $stmt->execute($args + ['me' => $me]);
            flash('success', $args['name'] . ' is created. An administrator checks it before it appears in the shop list.');
            $this->redirect(route('owner-shop'));
        }

        // Renaming an approved shop sends it back to the administrator.
        $renamed = mb_strtolower($args['name']) !== mb_strtolower($shop->getName());
        $backToPending = $shop->getStatus() === 'approved' && $renamed;
        $status = $backToPending ? 'pending' : $shop->getStatus();
        $stmt = $this->db->prepare('UPDATE shops SET plan_id = :plan, name = :name, description = :description, address = :address,
                phone = :phone, opens_at = :opens, closes_at = :closes, lat = :lat, lng = :lng, status = :status
            WHERE id = :id AND owner_id = :me');
        $stmt->execute($args + ['status' => $status, 'id' => $shop->getId(), 'me' => $me]);

        flash('success', $backToPending
            ? 'Your shop is saved. Its new name must be approved again: it is hidden from the shop list until then.'
            : 'Your shop is saved.');
        $this->redirect(route('owner-shop'));
    }

    /** @return ShopPlanModel[] the plans an owner may choose (is_active = 1), cheapest first. */
    private function activePlans(): array
    {
        $stmt = $this->db->prepare('SELECT * FROM shop_plans WHERE is_active = :active ORDER BY rank_weight ASC, name ASC');
        $stmt->execute(['active' => 1]);
        return array_map([ShopPlanModel::class, 'fromArray'], $stmt->fetchAll());
    }

    private function nameTaken(string $name, int $exceptId): bool
    {
        $stmt = $this->db->prepare('SELECT id FROM shops WHERE name = :name AND id <> :id LIMIT 1');
        $stmt->execute(['name' => $name, 'id' => $exceptId]);
        return (bool) $stmt->fetch();
    }

    private function formState(): array
    {
        $errors = $_SESSION['errors'] ?? [];
        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['errors'], $_SESSION['old']);
        return ['errors' => $errors, 'old' => $old];
    }
}
