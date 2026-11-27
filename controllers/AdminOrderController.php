<?php

/**
 * S4 Zamel - Administrator back office: every order (JOIN users and shops, filters by status and by date), one order
 * with its lines, and the delete of a CANCELLED order only (its lines go with it: ON DELETE CASCADE).
 * Auth::requireRole('admin') is the first line of every action; every query is a prepared statement.
 */
final class AdminOrderController extends Controller
{
    protected string $layout = 'admin';
    private \PDO $db;

    /** Same message as forms.js for data-after. */
    public const DATE_ORDER_MESSAGE = 'The end must be on or after the start.';
    public const DATE_MESSAGE = 'Write a date as YYYY-MM-DD.';

    public function __construct()
    {
        $this->db = \Database::getConnexion();
    }

    /** GET /admin/orders: ?status=, ?from=YYYY-MM-DD, ?to=YYYY-MM-DD. */
    public function index(): void
    {
        Auth::requireRole('admin');
        [$filters, $errors] = $this->filters();
        [$select, $join] = (new OrderController())->shopJoin();
        $sql = 'SELECT o.*, u.full_name AS customer_name, ' . $select . ', COALESCE(SUM(i.quantity), 0) AS item_count
            FROM orders o
            JOIN users u ON u.id = o.customer_id
            ' . $join . '
            LEFT JOIN order_items i ON i.order_id = o.id
            WHERE 1 = 1';
        $args = [];
        if ($filters['status'] !== '') {
            $sql .= ' AND o.status = :status';
            $args['status'] = $filters['status'];
        }
        if ($filters['from'] !== '') {
            $sql .= ' AND o.placed_at >= :from';
            $args['from'] = $filters['from'] . ' 00:00:00';
        }
        if ($filters['to'] !== '') {
            $sql .= ' AND o.placed_at <= :to';
            $args['to'] = $filters['to'] . ' 23:59:59';
        }
        $stmt = $this->db->prepare($sql . ' GROUP BY o.id ORDER BY o.placed_at DESC, o.id DESC');
        $stmt->execute($args);
        $this->render('admin/orders', [
            'title' => 'Orders | SKYDROP Admin',
            'orders' => array_map([OrderModel::class, 'fromArray'], $stmt->fetchAll()),
            'filters' => $filters,
            'statuses' => OrderModel::STATUSES,
            'errors' => $errors,
        ]);
    }

    /** GET /admin/orders/{id}: one order with its lines. */
    public function show(string $id): void
    {
        Auth::requireRole('admin');
        $controller = new OrderController();
        $order = $controller->find((int) $id) ?? abort(404, 'This order does not exist.');
        $this->render('admin/order', [
            'title' => 'Order #' . $order->getId() . ' | SKYDROP Admin',
            'activeMenu' => 'admin-orders',
            'order' => $order,
            'items' => $controller->items($order->getId()),
        ]);
    }

    /** POST /admin/orders/{id}/delete: only a cancelled order (the button asks first: data-confirm). */
    public function delete(string $id): void
    {
        Auth::requireRole('admin');
        Csrf::check();
        $order = (new OrderController())->find((int) $id) ?? abort(404, 'This order does not exist.');
        if ($order->getStatus() !== 'cancelled') {
            flash('error', 'Order #' . $order->getId() . ' is ' . strtolower($order->getStatusLabel())
                . ': only a cancelled order can be deleted. A live order stays in the history of the shop and the customer.');
            $this->redirect(route('admin-order', ['id' => $order->getId()]));
        }
        // "AND status = 'cancelled'" in the DELETE itself; order_items rows go with it (ON DELETE CASCADE).
        $stmt = $this->db->prepare("DELETE FROM orders WHERE id = :id AND status = 'cancelled'");
        $stmt->execute(['id' => $order->getId()]);
        flash('success', 'Order #' . $order->getId() . ' and its lines are deleted.');
        $this->redirect(route('admin-orders'));
    }

    /** Filters of the list from the query string, checked like the form does in forms.js. Returns [filters, errors]. */
    private function filters(): array
    {
        $status = (string) ($_GET['status'] ?? '');
        $filters = [
            'status' => isset(OrderModel::STATUSES[$status]) ? $status : '',
            'from' => trim((string) ($_GET['from'] ?? '')),
            'to' => trim((string) ($_GET['to'] ?? '')),
        ];
        $errors = [];
        foreach (['from', 'to'] as $key) {
            if ($filters[$key] !== '' && !$this->isDate($filters[$key])) {
                $errors[$key] = self::DATE_MESSAGE;
            }
        }
        if (!$errors && $filters['from'] !== '' && $filters['to'] !== '' && $filters['to'] < $filters['from']) {
            $errors['to'] = self::DATE_ORDER_MESSAGE;
        }
        // A wrong date is shown under its field and not used in the SQL.
        foreach (array_keys($errors) as $key) {
            $filters[$key . '_typed'] = $filters[$key];
            $filters[$key] = '';
        }
        return [$filters, $errors];
    }

    private function isDate(string $value): bool
    {
        $d = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $d !== false && $d->format('Y-m-d') === $value;
    }
}
