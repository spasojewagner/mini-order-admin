<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\StockMovementType;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Six months of a small wholesale distributor: ~40 business customers,
 * ~40 products, ~700 orders in every status, the stock history behind
 * them, invoices and an inbox. Deterministic (fixed random seed), and the
 * stock of every product equals the sum of its movements.
 */
class DemoDataSeeder extends Seeder
{
    private const DAYS = 180;

    private Carbon $now;

    /** @var array<int, int> product id => running stock */
    private array $stock = [];

    /** @var list<array> */
    private array $movements = [];

    /** @var list<int> products that run short at the end, so "Reorder" has something to show */
    private array $scarce = [];

    public function run(): void
    {
        mt_srand(20261010);
        $this->now = now()->startOfMinute();

        $staff = [
            'sales' => User::firstWhere('role', 'sales'),
            'warehouse' => User::firstWhere('role', 'warehouse'),
            'admin' => User::firstWhere('role', 'admin'),
        ];

        Settings::flush();

        DB::transaction(function () use ($staff) {
            $products = $this->products();
            $customers = $this->customers();
            $orders = $this->orders($customers, $products, $staff);
            $this->writeStock($products, $staff['warehouse']);
            $this->invoices();
            $this->conversations($customers, $orders, $staff['sales']);
            $this->portalUser($customers->first());
        });
    }

    // -- catalog --------------------------------------------------------------

    private function products()
    {
        $catalog = [
            'Beverages' => [
                ['Still water 1.5 L, 6-pack', 390, 40], ['Sparkling water 1.5 L, 6-pack', 420, 30],
                ['Orange juice 1 L, 12-pack', 2280, 15], ['Apple juice 1 L, 12-pack', 2160, 15],
                ['Cola 0.5 L, 24-pack', 2640, 20], ['Peach iced tea 1.5 L, 6-pack', 900, 15],
                ['Energy drink 0.25 L, 24-pack', 3120, 10], ['Tonic water 0.25 L, 24-pack', 2400, 10],
            ],
            'Coffee & tea' => [
                ['Espresso beans 1 kg', 2450, 20], ['Ground coffee 500 g', 980, 25],
                ['Instant coffee 200 g', 1150, 10], ['Black tea, 100 bags', 620, 10],
                ['Green tea, 100 bags', 690, 10], ['Sugar sticks, 1000 pcs', 1350, 10],
                ['Coffee creamer 10 g, 100 pcs', 980, 10],
            ],
            'Snacks' => [
                ['Potato chips 150 g, 20-pack', 3400, 10], ['Salted peanuts 500 g, 10-pack', 2300, 8],
                ['Milk chocolate 100 g, 24-pack', 3360, 10], ['Butter biscuits 200 g, 20-pack', 2200, 10],
                ['Pretzel sticks 250 g, 15-pack', 1650, 8], ['Granola bars, 48 pcs', 3840, 6],
            ],
            'Dairy' => [
                ['UHT milk 1 L, 12-pack', 1560, 25], ['Yogurt 1 L, 10-pack', 1350, 15],
                ['Butter 250 g, 20-pack', 5200, 8], ['Gouda cheese 1 kg', 1190, 10],
                ['Cream cheese 200 g, 12-pack', 2520, 8], ['Cooking cream 200 ml, 24-pack', 2880, 8],
            ],
            'Household' => [
                ['Dish soap 1 L, 12-pack', 1800, 10], ['Laundry detergent 3 kg', 1490, 10],
                ['All-purpose cleaner 750 ml, 12-pack', 2160, 8], ['Paper towels, 8 rolls', 720, 15],
                ['Toilet paper, 24 rolls', 1560, 15], ['Garbage bags 120 L, 10 pcs', 390, 15],
                ['Nitrile gloves, 100 pcs', 650, 10],
            ],
            'Personal care' => [
                ['Liquid hand soap 500 ml, 12-pack', 1920, 8], ['Shampoo 400 ml, 12-pack', 3000, 6],
                ['Toothpaste 75 ml, 24-pack', 3120, 6], ['Wet wipes, 72 pcs', 240, 20],
            ],
        ];

        $prefix = ['Beverages' => 'BEV', 'Coffee & tea' => 'CAF', 'Snacks' => 'SNK', 'Dairy' => 'DAI', 'Household' => 'HOM', 'Personal care' => 'PCA'];
        $products = collect();

        foreach ($catalog as $category => $items) {
            foreach ($items as $i => [$name, $price, $reorder]) {
                $product = Product::create([
                    'name' => $name,
                    'sku' => sprintf('%s-%03d', $prefix[$category], $i + 1),
                    'category' => $category,
                    'price' => $price,
                    'reorder_level' => $reorder,
                    'is_active' => true,
                ]);
                // Fast movers sell more often.
                $product->setAttribute('weight', $reorder >= 20 ? 5 : ($reorder >= 10 ? 3 : 1));
                $products->push($product);
                $this->stock[$product->id] = 0;
            }
        }

        // One discontinued product: never ordered, hidden from the portal.
        Product::create(['name' => 'Seasonal eggnog 1 L, 6-pack', 'sku' => 'BEV-090', 'category' => 'Beverages', 'price' => 1740, 'reorder_level' => 0, 'is_active' => false]);

        $this->scarce = $products->whereIn('sku', ['CAF-001', 'DAI-004', 'SNK-006', 'HOM-002', 'BEV-007', 'PCA-002'])->pluck('id')->all();

        return $products;
    }

    private function customers()
    {
        $rows = [
            // [company, contact, city, size]
            ['Kafe Sunce', 'Jelena Marković', 'Novi Sad', 3],
            ['Market Lipa d.o.o.', 'Dragan Nikolić', 'Novi Sad', 5],
            ['Hotel Dunav', 'Milica Stojanović', 'Novi Sad', 5],
            ['Restoran Stara kuća', 'Stefan Pavlović', 'Sremska Kamenica', 3],
            ['Pekara Zrno', 'Ivana Đorđević', 'Novi Sad', 2],
            ['Mini market Kod Mike', 'Milan Kostić', 'Futog', 2],
            ['Caffe bar Tvrđava', 'Nemanja Ilić', 'Petrovaradin', 3],
            ['Hostel Centar', 'Teodora Lazić', 'Novi Sad', 1],
            ['Market Plus Bačka', 'Aleksandar Popović', 'Bačka Palanka', 4],
            ['Kiosk Centar', 'Vesna Ristić', 'Novi Sad', 1],
            ['Hotel Fruška', 'Jovan Mitrović', 'Vrdnik', 4],
            ['Pizzeria Napoli NS', 'Luka Živković', 'Novi Sad', 2],
            ['Gym Bar Fit', 'Sara Tomić', 'Novi Sad', 1],
            ['Market Zora', 'Bojan Stanković', 'Zrenjanin', 3],
            ['Caffe Galerija', 'Ana Simić', 'Zrenjanin', 2],
            ['Restoran Salaš 84', 'Petar Obradović', 'Čenej', 3],
            ['Market Kolo d.o.o.', 'Marija Vasić', 'Sombor', 4],
            ['Bistro Dva štapića', 'Đorđe Milić', 'Novi Sad', 1],
            ['Hotel Panorama', 'Katarina Jović', 'Beograd', 5],
            ['Caffe Dorćol', 'Filip Antić', 'Beograd', 2],
            ['Market Vračar', 'Tamara Ćirić', 'Beograd', 3],
            ['Restoran Ušće', 'Uroš Radović', 'Beograd', 3],
            ['Kafeterija Zemun', 'Nina Bogdanović', 'Zemun', 2],
            ['Pekara Kifla', 'Vladimir Kovačević', 'Pančevo', 2],
            ['Market Šumadija', 'Jelena Todorović', 'Kragujevac', 4],
            ['Caffe Lounge 34', 'Dušan Matić', 'Kragujevac', 1],
            ['Hotel Morava', 'Snežana Lukić', 'Čačak', 3],
            ['Market Ibar', 'Goran Jakovljević', 'Kraljevo', 3],
            ['Restoran Kod Ćire', 'Miloš Pešić', 'Niš', 2],
            ['Caffe Tvrđava Niš', 'Andrea Savić', 'Niš', 2],
            ['Market Nišava', 'Zoran Petković', 'Niš', 4],
            ['Hotel Zlatibor Vista', 'Ivana Gajić', 'Zlatibor', 4],
            ['Kafana Stari most', 'Nikola Đukić', 'Valjevo', 1],
            ['Market Podrinje', 'Branka Ivanović', 'Šabac', 2],
            ['Vrtić Pčelica', 'Maja Rakić', 'Novi Sad', 1],
            ['Coworking Hub NS', 'Igor Mladenović', 'Novi Sad', 1],
            ['Catering Bela kuhinja', 'Jasmina Kecman', 'Novi Sad', 3],
        ];

        $streets = ['Bulevar oslobođenja', 'Zmaj Jovina', 'Kralja Petra I', 'Njegoševa', 'Karađorđeva', 'Cara Dušana', 'Vojvode Mišića', 'Svetog Save'];
        $customers = collect();

        foreach ($rows as $i => [$company, $contact, $city, $size]) {
            $slug = str($company)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', '-')->trim('-');
            $customer = Customer::create([
                'type' => 'company',
                'name' => $contact,
                'company_name' => $company,
                'tax_id' => (string) (100000000 + ($i * 7919 + 104729) % 899999999),
                'email' => "nabavka@{$slug}.example",
                'phone' => sprintf('+381 6%d %03d %04d', mt_rand(0, 9), mt_rand(100, 999), mt_rand(1000, 9999)),
                'address' => $streets[$i % count($streets)].' '.mt_rand(1, 120).', '.$city,
            ]);
            $customer->setAttribute('weight', $size);
            $customers->push($customer);
        }

        foreach ([['Petar Savić', 'Novi Sad'], ['Milena Đurić', 'Beograd']] as [$name, $city]) {
            $customer = Customer::create([
                'type' => 'individual',
                'name' => $name,
                'email' => str($name)->ascii()->lower()->replace(' ', '.').'@mail.example',
                'phone' => sprintf('+381 6%d %03d %04d', mt_rand(0, 9), mt_rand(100, 999), mt_rand(1000, 9999)),
                'address' => $streets[mt_rand(0, 7)].' '.mt_rand(1, 60).', '.$city,
            ]);
            $customer->setAttribute('weight', 1);
            $customers->push($customer);
        }

        return $customers;
    }

    // -- orders ---------------------------------------------------------------

    /** @return list<array> the seeded orders (id, customer_id, status, created_at) */
    private function orders($customers, $products, array $staff): array
    {
        $plans = [];
        for ($daysAgo = self::DAYS; $daysAgo >= 0; $daysAgo--) {
            $day = $this->now->copy()->subDays($daysAgo)->startOfDay();
            $count = $day->isWeekend() ? mt_rand(0, 2) : mt_rand(3, 6);
            for ($n = 0; $n < $count; $n++) {
                $created = $day->copy()->addMinutes(mt_rand(7 * 60, 19 * 60));
                // The last 48 hours are filled by workload() with a known mix of statuses.
                if ($created->gt($this->now->copy()->subHours(48))) {
                    continue;
                }
                $plans[] = $this->plan($created, $customers, $products);
            }
        }

        array_push($plans, ...$this->workload($customers, $products));
        usort($plans, fn ($a, $b) => $a['created_at'] <=> $b['created_at']);

        $orders = [];
        $items = [];
        $events = [];

        foreach ($plans as $plan) {
            $id = DB::table('orders')->insertGetId([
                'customer_id' => $plan['customer_id'],
                'status' => $plan['status']->value,
                'source' => $plan['source'],
                'total_amount' => $plan['total'],
                'note' => $plan['note'],
                'created_at' => $plan['created_at'],
                'updated_at' => $plan['updated_at'],
                'confirmed_at' => $plan['confirmed_at'],
                'picking_at' => $plan['picking_at'],
                'shipped_at' => $plan['shipped_at'],
                'delivered_at' => $plan['delivered_at'],
                'cancelled_at' => $plan['cancelled_at'],
            ]);

            foreach ($plan['lines'] as $line) {
                $items[] = ['order_id' => $id, ...$line, 'created_at' => $plan['created_at'], 'updated_at' => $plan['created_at']];
            }

            foreach ($this->timeline($plan, $staff) as $event) {
                $events[] = ['order_id' => $id, ...$event];
            }

            // Stock leaves when the order is confirmed and comes back if it is cancelled afterwards.
            if ($plan['confirmed_at']) {
                foreach ($plan['lines'] as $line) {
                    $this->queueMovement($plan['confirmed_at'], $line['product_id'], -$line['quantity'], StockMovementType::Sale, $id, $staff['sales']->id);
                }
                if ($plan['cancelled_at']) {
                    foreach ($plan['lines'] as $line) {
                        $this->queueMovement($plan['cancelled_at'], $line['product_id'], $line['quantity'], StockMovementType::Cancellation, $id, $staff['sales']->id, 'Customer cancelled');
                    }
                }
            }

            $orders[] = ['id' => $id, 'customer_id' => $plan['customer_id'], 'status' => $plan['status'], 'created_at' => $plan['created_at']];
        }

        foreach (array_chunk($items, 500) as $chunk) {
            DB::table('order_items')->insert($chunk);
        }
        foreach (array_chunk($events, 500) as $chunk) {
            DB::table('order_events')->insert($chunk);
        }

        return $orders;
    }

    private function lines(Customer $customer, $products): array
    {
        $size = $customer->weight;
        $lines = [];
        foreach ($this->weightedMany($products, mt_rand(2, min(7, 2 + $size))) as $product) {
            $quantity = mt_rand(1, 3 + $size * 2);
            $lines[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'unit_price' => $product->price,
                'quantity' => $quantity,
                'subtotal' => round((float) $product->price * $quantity, 2),
            ];
        }

        return $lines;
    }

    /**
     * What is on the desk right now, whatever day the seeder runs: orders
     * waiting for confirmation (two of them for too long), work for the
     * warehouse, recent shipments and a couple of drafts.
     */
    private function workload($customers, $products): array
    {
        $h = fn (float $hours) => $this->now->copy()->subMinutes((int) round($hours * 60));
        $plan = function (float $ageHours, OrderStatus $status, string $source, array $steps = []) use ($customers, $products, $h) {
            $customer = $this->weighted($customers);
            $created = $h($ageHours);
            $lines = $this->lines($customer, $products);
            $stamps = ['confirmed_at' => null, 'picking_at' => null, 'shipped_at' => null, 'delivered_at' => null, 'cancelled_at' => null];
            $at = $created->copy();
            foreach ($steps as $column => $afterHours) {
                $at = $at->copy()->addMinutes((int) round($afterHours * 60));
                $stamps[$column] = $at;
            }

            return [
                'customer_id' => $customer->id, 'source' => $source, 'status' => $status, 'note' => null,
                'lines' => $lines, 'total' => round(array_sum(array_column($lines, 'subtotal')), 2),
                'created_at' => $created, 'updated_at' => collect($stamps)->filter()->max() ?? $created, ...$stamps,
            ];
        };

        return [
            $plan(0.4, OrderStatus::New, 'portal'),
            $plan(1.5, OrderStatus::New, 'portal'),
            $plan(3.2, OrderStatus::New, 'admin'),
            $plan(6, OrderStatus::New, 'portal'),
            $plan(57, OrderStatus::New, 'portal'),           // overdue: nobody confirmed it
            $plan(76, OrderStatus::New, 'admin'),            // overdue
            $plan(0.7, OrderStatus::Draft, 'admin'),
            $plan(2.5, OrderStatus::Draft, 'mcp'),
            $plan(8, OrderStatus::Confirmed, 'portal', ['confirmed_at' => 1.5]),
            $plan(19, OrderStatus::Confirmed, 'admin', ['confirmed_at' => 2]),
            $plan(80, OrderStatus::Confirmed, 'portal', ['confirmed_at' => 3]),   // overdue in the warehouse
            $plan(11, OrderStatus::Picking, 'portal', ['confirmed_at' => 2, 'picking_at' => 3]),
            $plan(24, OrderStatus::Picking, 'admin', ['confirmed_at' => 1, 'picking_at' => 5]),
            $plan(30, OrderStatus::Picking, 'portal', ['confirmed_at' => 2, 'picking_at' => 6]),
            $plan(27, OrderStatus::Shipped, 'portal', ['confirmed_at' => 1, 'picking_at' => 4, 'shipped_at' => 3]),
            $plan(33, OrderStatus::Shipped, 'admin', ['confirmed_at' => 2, 'picking_at' => 5, 'shipped_at' => 2]),
            $plan(41, OrderStatus::Shipped, 'portal', ['confirmed_at' => 3, 'picking_at' => 6, 'shipped_at' => 4]),
            $plan(46, OrderStatus::Delivered, 'portal', ['confirmed_at' => 1, 'picking_at' => 4, 'shipped_at' => 3, 'delivered_at' => 20]),
            $plan(44, OrderStatus::Cancelled, 'portal', ['cancelled_at' => 2]),
        ];
    }

    private function plan(Carbon $created, $customers, $products): array
    {
        $customer = $this->weighted($customers);
        $lines = $this->lines($customer, $products);

        $r = mt_rand(1, 100);
        $source = $r <= 55 ? 'portal' : ($r <= 95 ? 'admin' : 'mcp');
        $ageHours = $created->diffInMinutes($this->now) / 60;

        // Where the order would be if nothing went wrong.
        // People work 7:00-20:00, so every step lands in working hours.
        $at = [
            'confirmed' => $this->workHours($created->copy()->addMinutes(mt_rand(30, 20 * 60))),
        ];
        $at['picking'] = $this->workHours($at['confirmed']->copy()->addMinutes(mt_rand(2 * 60, 20 * 60)));
        $at['shipped'] = $this->workHours($at['picking']->copy()->addMinutes(mt_rand(60, 8 * 60)));
        $at['delivered'] = $this->workHours($at['shipped']->copy()->addMinutes(mt_rand(16 * 60, 40 * 60)));

        $status = OrderStatus::New;
        $stamps = ['confirmed_at' => null, 'picking_at' => null, 'shipped_at' => null, 'delivered_at' => null, 'cancelled_at' => null];

        if ($source === 'admin' && $ageHours < 30 && mt_rand(1, 100) <= 25) {
            $status = OrderStatus::Draft;
        } elseif ($ageHours > 24 && mt_rand(1, 100) <= 6) {
            $status = OrderStatus::Cancelled;
            if (mt_rand(0, 1) && $at['confirmed']->lt($this->now)) {
                $stamps['confirmed_at'] = $at['confirmed'];
                $stamps['cancelled_at'] = $this->clamp($at['confirmed']->copy()->addMinutes(mt_rand(60, 10 * 60)));
            } else {
                $stamps['cancelled_at'] = $this->clamp($created->copy()->addMinutes(mt_rand(30, 12 * 60)));
            }
        } else {
            // A few orders between two and five days old got stuck: this is what "Needs attention" is for.
            $stuckAt = ($ageHours >= 50 && $ageHours <= 120 && mt_rand(1, 100) <= 10)
                ? (mt_rand(1, 100) <= 60 ? 'new' : 'confirmed')
                : null;

            foreach (['confirmed', 'picking', 'shipped', 'delivered'] as $step) {
                if ($stuckAt === 'new' || $at[$step]->gt($this->now)) {
                    break;
                }
                $stamps["{$step}_at"] = $at[$step];
                $status = match ($step) {
                    'confirmed' => OrderStatus::Confirmed,
                    'picking' => OrderStatus::Picking,
                    'shipped' => OrderStatus::Shipped,
                    'delivered' => OrderStatus::Delivered,
                };
                if ($stuckAt === $step) {
                    break;
                }
            }
        }

        $notes = [null, null, null, null, 'Deliver before 10:00, please.', 'Back entrance, ring the bell.', 'Call 30 min before delivery.', 'Invoice to the head office.'];

        return [
            'customer_id' => $customer->id,
            'source' => $source,
            'status' => $status,
            'note' => $notes[mt_rand(0, count($notes) - 1)],
            'lines' => $lines,
            'total' => round(array_sum(array_column($lines, 'subtotal')), 2),
            'created_at' => $created,
            'updated_at' => collect($stamps)->filter()->max() ?? $created,
            ...$stamps,
        ];
    }

    private function timeline(array $plan, array $staff): array
    {
        $by = fn (string $who) => $staff[$who]->id;
        $via = $plan['source'];
        $events = [[
            'user_id' => $via === 'portal' ? null : $by('sales'),
            'type' => 'created',
            'from_status' => null,
            'to_status' => ($plan['status'] === OrderStatus::Draft ? OrderStatus::Draft : ($via === 'portal' ? OrderStatus::New : OrderStatus::Draft))->value,
            'via' => $via,
            'description' => null,
            'created_at' => $plan['created_at'],
        ]];

        $previous = $events[0]['to_status'];
        $step = function (?Carbon $at, OrderStatus $to, int $userId, ?string $note = null) use (&$events, &$previous) {
            if (! $at) {
                return;
            }
            $events[] = ['user_id' => $userId, 'type' => 'status_changed', 'from_status' => $previous, 'to_status' => $to->value,
                'via' => 'admin', 'description' => $note, 'created_at' => $at];
            $previous = $to->value;
        };

        if ($plan['status'] !== OrderStatus::Draft && $previous === OrderStatus::Draft->value && ! $plan['confirmed_at'] && ! $plan['cancelled_at']) {
            $step($plan['created_at']->copy()->addMinutes(5), OrderStatus::New, $by('sales'));
        }

        $step($plan['confirmed_at'], OrderStatus::Confirmed, $by('sales'));
        $step($plan['picking_at'], OrderStatus::Picking, $by('warehouse'));
        $step($plan['shipped_at'], OrderStatus::Shipped, $by('warehouse'));
        $step($plan['delivered_at'], OrderStatus::Delivered, $by('warehouse'));
        $step($plan['cancelled_at'], OrderStatus::Cancelled, $by('sales'), 'Customer cancelled');

        return $events;
    }

    // -- stock ----------------------------------------------------------------

    private function queueMovement(Carbon $at, int $productId, int $quantity, StockMovementType $type, ?int $orderId, ?int $userId, ?string $note = null): void
    {
        $this->movements[] = compact('at', 'productId', 'quantity', 'type', 'orderId', 'userId', 'note');
    }

    /**
     * Replays the year: an opening balance, then sales and returns in time order,
     * with deliveries from suppliers whenever a product runs low. The products in
     * $scarce get only small emergency deliveries in the last two months, so some stock is low today.
     */
    private function writeStock($products, User $warehouse): void
    {
        // A stocktake correction now and then, as in any real warehouse.
        foreach ($products->random(5) as $product) {
            $this->queueMovement($this->now->copy()->subDays(mt_rand(3, 40))->setTime(17, 30), $product->id, -mt_rand(1, 3), StockMovementType::Adjustment, null, $warehouse->id, 'Stocktake: damaged packaging');
        }

        usort($this->movements, fn ($a, $b) => $a['at'] <=> $b['at']);
        $start = $this->now->copy()->subDays(self::DAYS + 1)->setTime(7, 0);
        $rows = [];

        foreach ($products as $product) {
            $opening = $product->reorder_level * mt_rand(8, 14);
            $this->stock[$product->id] = $opening;
            $rows[] = $this->row($start, $product->id, $opening, StockMovementType::Opening, null, $warehouse->id, 'Opening balance');
        }

        $byId = $products->keyBy('id');

        foreach ($this->movements as $m) {
            $product = $byId[$m['productId']];

            if ($m['type'] === StockMovementType::Adjustment) {
                if ($this->stock[$product->id] + $m['quantity'] >= 0) {
                    $this->stock[$product->id] += $m['quantity'];
                    $rows[] = $this->row($m['at'], $product->id, $m['quantity'], $m['type'], null, $m['userId'], $m['note']);
                }

                continue;
            }

            $balance = $this->stock[$product->id];
            $needed = -$m['quantity'];
            $lastWeeks = $m['at']->gt($this->now->copy()->subDays(60));
            $scarce = in_array($product->id, $this->scarce, true);

            if ($m['quantity'] < 0 && $balance - $needed < $product->reorder_level * 2 && ! ($lastWeeks && $scarce)) {
                $received = max($needed, $product->reorder_level * mt_rand(6, 12));
                $receivedAt = $m['at']->copy()->subMinutes(mt_rand(30, 180));
                $this->stock[$product->id] += $received;
                $rows[] = $this->row($receivedAt, $product->id, $received, StockMovementType::Receipt, null, $warehouse->id, 'Supplier delivery');
            } elseif ($m['quantity'] < 0 && $balance < $needed) {
                // The supplier of the scarce ones is late: only small emergency deliveries, so they stay low.
                $received = $needed - $balance + mt_rand(0, intdiv($product->reorder_level, 2));
                $this->stock[$product->id] += $received;
                $rows[] = $this->row($m['at']->copy()->subMinutes(45), $product->id, $received, StockMovementType::Receipt, null, $warehouse->id, 'Urgent partial delivery');
            }

            $this->stock[$product->id] += $m['quantity'];
            $rows[] = $this->row($m['at'], $product->id, $m['quantity'], $m['type'], $m['orderId'], $m['userId'], $m['note']);
        }

        // Balances in time order per product, then write them all.
        usort($rows, fn ($a, $b) => [$a['product_id'], $a['created_at']] <=> [$b['product_id'], $b['created_at']]);
        $running = [];
        foreach ($rows as &$row) {
            $running[$row['product_id']] = ($running[$row['product_id']] ?? 0) + $row['quantity'];
            $row['balance_after'] = $running[$row['product_id']];
        }
        unset($row);

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('stock_movements')->insert($chunk);
        }

        foreach ($running as $productId => $balance) {
            DB::table('products')->where('id', $productId)->update(['stock_quantity' => $balance]);
        }
    }

    private function row(Carbon $at, int $productId, int $quantity, StockMovementType $type, ?int $orderId, ?int $userId, ?string $note): array
    {
        return [
            'product_id' => $productId,
            'type' => $type->value,
            'quantity' => $quantity,
            'balance_after' => 0,
            'order_id' => $orderId,
            'user_id' => $userId,
            'note' => $note,
            'created_at' => $at,
        ];
    }

    // -- invoices, inbox, portal ------------------------------------------------

    private function invoices(): void
    {
        $prefix = Settings::get('invoice.prefix');
        $sequence = [];

        DB::table('orders')->whereNotNull('shipped_at')->orderBy('shipped_at')->get(['id', 'shipped_at'])
            ->each(function ($order) use (&$sequence, $prefix) {
                $year = Carbon::parse($order->shipped_at)->format('Y');
                $sequence[$year] = ($sequence[$year] ?? 0) + 1;
                $number = sprintf('%s-%s-%05d', $prefix, $year, $sequence[$year]);

                DB::table('orders')->where('id', $order->id)->update(['invoice_number' => $number, 'invoiced_at' => $order->shipped_at]);
                DB::table('order_events')->insert([
                    'order_id' => $order->id, 'user_id' => null, 'type' => 'invoiced', 'from_status' => null, 'to_status' => null,
                    'via' => 'system', 'description' => "Invoice {$number}", 'created_at' => $order->shipped_at,
                ]);
            });
    }

    private function conversations($customers, array $orders, User $sales): void
    {
        $latest = fn (int $customerId, array $statuses) => collect($orders)
            ->where('customer_id', $customerId)
            ->filter(fn ($o) => in_array($o['status'], $statuses, true))
            ->sortByDesc('created_at')->first();

        $scenarios = [
            // [customer index, statuses of the order it is about, channel, status, subject, messages: [sender, text, minutes ago]]
            [0, [OrderStatus::Confirmed, OrderStatus::Picking], 'viber', 'open', 'Delivery before Friday?', [
                ['customer', 'Dobar dan, da li je porudžbina {order} krenula? Treba nam roba do petka za vikend.', 50],
            ]],
            [1, [OrderStatus::New], 'email', 'open', 'Add 2 packs of coffee', [
                ['customer', 'Pozdrav, možete li u porudžbinu {order} da dodate još 2 pakovanja mlevene kafe? Hvala!', 95],
            ]],
            [2, [OrderStatus::Delivered], 'phone', 'waiting', 'Missing carton of juice', [
                ['customer', 'Stigla je isporuka {order}, ali fali jedan karton soka od pomorandže.', 60 * 26],
                ['admin', 'Izvinjavamo se, proveravamo sa magacinom i javljamo se danas do 15h.', 60 * 25],
            ]],
            [8, [OrderStatus::Shipped, OrderStatus::Delivered], 'email', 'closed', 'Invoice to accounting', [
                ['customer', 'Molim vas da nam fakturu za {order} pošaljete na mejl računovodstva.', 60 * 50],
                ['admin', 'Poslato, fakturu možete preuzeti i na portalu u odeljku Moje porudžbine.', 60 * 49],
                ['customer', 'Super, hvala!', 60 * 48],
            ]],
            [10, [OrderStatus::Delivered], 'whatsapp', 'open', 'Espresso beans back in stock?', [
                ['customer', 'Kada ćete ponovo imati espresso u zrnu od 1 kg? Za vikend nam treba bar 10 kg.', 180],
            ]],
            [18, [OrderStatus::Picking, OrderStatus::Confirmed, OrderStatus::Shipped], 'email', 'open', 'Delivery time', [
                ['customer', 'Hello, this is Katarina from Hotel Panorama. Could you confirm the delivery time for {order}? Our receiving dock closes at 14:00.', 35],
            ]],
            [24, [OrderStatus::Delivered], 'web', 'closed', 'Thank you', [
                ['customer', 'Sve je stiglo uredno, hvala na brzoj isporuci.', 60 * 70],
                ['admin', 'Hvala Vama, uvek nam je drago!', 60 * 69],
            ]],
            [13, [OrderStatus::Cancelled, OrderStatus::Delivered], 'phone', 'waiting', 'Why was the order cancelled?', [
                ['customer', 'Zašto je otkazana porudžbina {order}? Nismo tražili otkazivanje.', 60 * 30],
                ['admin', 'Proverićemo i javiti Vam se u toku dana.', 60 * 29],
            ]],
        ];

        foreach ($scenarios as [$index, $statuses, $channel, $status, $subject, $messages]) {
            $customer = $customers[$index] ?? null;
            if (! $customer) {
                continue;
            }
            $order = $latest($customer->id, $statuses);
            $number = $order ? 'SO-'.str_pad((string) $order['id'], 5, '0', STR_PAD_LEFT) : 'our last order';

            $lastAt = $this->now->copy()->subMinutes(end($messages)[2]);
            $conversation = Conversation::create([
                'customer_id' => $customer->id,
                'channel' => $channel,
                'status' => $status,
                'subject' => $subject,
            ]);

            foreach ($messages as [$sender, $text, $minutesAgo]) {
                $at = $this->now->copy()->subMinutes($minutesAgo);
                $conversation->messages()->create([
                    'sender' => $sender,
                    'user_id' => $sender === 'admin' ? $sales->id : null,
                    'body' => strtr($text, ['{order}' => $number]),
                    'ai_generated' => false,
                ])->forceFill(['created_at' => $at, 'updated_at' => $at])->saveQuietly();
            }

            $conversation->forceFill(['created_at' => $this->now->copy()->subMinutes($messages[0][2]), 'updated_at' => $lastAt])->saveQuietly();
        }
    }

    private function portalUser(Customer $customer): void
    {
        User::updateOrCreate(['email' => 'shop@demo.test'], [
            'name' => $customer->name,
            'role' => 'customer',
            'customer_id' => $customer->id,
            'password' => UserSeeder::PASSWORD,
            'email_verified_at' => now(),
        ]);
    }

    // -- helpers --------------------------------------------------------------

    private function workHours(Carbon $at): Carbon
    {
        if ($at->hour < 7) {
            return $at->setTime(7, mt_rand(0, 59));
        }
        if ($at->hour >= 20) {
            return $at->addDay()->setTime(7, mt_rand(0, 59));
        }

        return $at;
    }

    private function clamp(Carbon $at): Carbon
    {
        return $at->gt($this->now) ? $this->now->copy()->subMinutes(mt_rand(5, 60)) : $at;
    }

    private function weighted($items)
    {
        $total = $items->sum('weight');
        $pick = mt_rand(1, $total);
        foreach ($items as $item) {
            $pick -= $item->weight;
            if ($pick <= 0) {
                return $item;
            }
        }

        return $items->last();
    }

    private function weightedMany($items, int $count): array
    {
        $chosen = [];
        $guard = 0;
        while (count($chosen) < $count && $guard++ < 100) {
            $item = $this->weighted($items);
            $chosen[$item->id] = $item;
        }

        return array_values($chosen);
    }
}
