<?php

/**
 * S4 Zamel - Shop owner back office: the orders of MY shop (status filter), one order with its lines, and the move
 * to the next status (placed -> preparing -> ready -> delivered, one step forward only).
 * Auth::requireRole('shop_owner') is the first line of every action. "My shop" is read through Mili's shops table:
 * every query has "AND s.owner_id = :me", the UPDATE too, so an owner never sees or changes another shop's order.
 */
final class OwnerOrderController extends Controller
{
    protected string $layout = 'owner';
    private \PDO $db;

    public function __construct()
    {
        $this->db = \Database::getConnexion();
    }

    /** GET /owner/orders: the orders of my shop, newest first, optional ?status= filter. */
    public function index(): void
    {
        Auth::requireRole('shop_owner');
        $status = (string) ($_GET['status'] ?? '');
        $status = isset(OrderModel::STATUSES[$status]) ? $status : '';
        $ready = $this->shopsReady();
        $orders = [];
        if ($ready) {
            $stmt = $this->db->prepare('SELECT o.*, u.full_name AS customer_name, s.name AS shop_name, COALESCE(SUM(i.quantity), 0) AS item_count
                FROM orders o
                JOIN shops s ON s.id = o.shop_id
                JOIN users u ON u.id = o.customer_id
                LEFT JOIN order_items i ON i.order_id = o.id
                WHERE s.owner_id = :me' . ($status !== '' ? ' AND o.status = :status' : '') . '
                GROUP BY o.id
                ORDER BY o.placed_at DESC, o.id DESC');
            $args = ['me' => Auth::id()];
            if ($status !== '') {
                $args['status'] = $status;
            }
            $stmt->execute($args);
            $orders = array_map([OrderModel::class, 'fromArray'], $stmt->fetchAll());
        }
        $this->render('owner/orders', [
            'title' => 'Orders | SKYDROP Owner',
            'orders' => $orders,
            'status' => $status,
            'statuses' => OrderModel::STATUSES,
            'shopsReady' => $ready,
        ]);
    }

    /** GET /owner/orders/{id}: one order of my shop with its lines; another shop's order is "not found". */
    public function show(string $id): void
    {
        Auth::requireRole('shop_owner');
        $order = $this->findMine((int) $id) ?? abort(404, 'This order does not exist.');
        $this->render('owner/order', [
            'title' => 'Order #' . $order->getId() . ' | SKYDROP Owner',
            'activeMenu' => 'owner-orders',
            'order' => $order,
            'items' => (new OrderController())->items($order->getId()),
            'next' => OrderController::NEXT[$order->getStatus()] ?? null,
        ]);
    }

    /** POST /owner/orders/{id}/status: status = the next status only. */
    public function advance(string $id): void
    {
        Auth::requireRole('shop_owner');
        Csrf::check();
        $order = $this->findMine((int) $id) ?? abort(404, 'This order does not exist.');
        $back = route('owner-order', ['id' => $order->getId()]);
        $v = (new Validator($_POST))->required('status')->in('status', array_values(OrderController::NEXT));
        if ($v->fails()) {
            flash('error', 'Status: ' . $v->errors()['status']);
            $this->redirect($back);
        }
        $wanted = $v->get('status');
        $next = OrderController::NEXT[$order->getStatus()] ?? null;
        if ($next === null) {
            flash('error', 'Order #' . $order->getId() . ' is ' . strtolower($order->getStatusLabel()) . ': its status cannot change any more.');
            $this->redirect($back);
        }
        if ($wanted !== $next) {
            flash('error', 'An order moves forward one step at a time: placed, preparing, ready, delivered. The next step of this order is '
                . strtolower(OrderModel::STATUSES[$next]) . '.');
            $this->redirect($back);
        }
        // "AND s.owner_id = :me" (my shop only) and "AND o.status = :current" (nobody changed it meanwhile).
        $stmt = $this->db->prepare('UPDATE orders o JOIN shops s ON s.id = o.shop_id SET o.status = :next
            WHERE o.id = :id AND s.owner_id = :me AND o.status = :current');
        $stmt->execute(['next' => $next, 'id' => $order->getId(), 'me' => Auth::id(), 'current' => $order->getStatus()]);
        flash($stmt->rowCount() === 1 ? 'success' : 'error', $stmt->rowCount() === 1
            ? 'Order #' . $order->getId() . ' is now ' . strtolower(OrderModel::STATUSES[$next]) . '.'
            : 'Order #' . $order->getId() . ' changed meanwhile (the customer may have cancelled it). Look at it again.');
        $this->redirect($back);
    }

    /** One order of MY shop (JOIN shops ... AND s.owner_id = :me), with the customer name and its number of articles. */
    private function findMine(int $id): ?OrderModel
    {
        if (!$this->shopsReady()) {
            return null;
        }
        $stmt = $this->db->prepare('SELECT o.*, u.full_name AS customer_name, s.name AS shop_name, COALESCE(SUM(i.quantity), 0) AS item_count
            FROM orders o
            JOIN shops s ON s.id = o.shop_id
            JOIN users u ON u.id = o.customer_id
            LEFT JOIN order_items i ON i.order_id = o.id
            WHERE o.id = :id AND s.owner_id = :me
            GROUP BY o.id');
        $stmt->execute(['id' => $id, 'me' => Auth::id()]);
        $row = $stmt->fetch();
        return $row ? OrderModel::fromArray($row) : null;
    }

    /** True once Mili's shops table exists (the shop of an owner is only known through it). */
    private function shopsReady(): bool
    {
        return (new OrderController())->shopJoin()[1] !== '';
    }
}
