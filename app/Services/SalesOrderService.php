<?php
 
 namespace App\Services;
 
 use App\Repositories\SalesOrderRepository;
 use Illuminate\Support\Facades\DB;
 
 class SalesOrderService extends BaseService
 {
     public function __construct(SalesOrderRepository $repository)
     {
         parent::__construct($repository);
     }
 
     public function generateOrderNumber()
     {
         $year = now()->format('Y');
         $prefix = "SO/{$year}/";
         
         $latestOrder = \App\Models\SalesOrder::where('order_number', 'like', $prefix . '%')
             ->orderBy('order_number', 'desc')
             ->first();
             
         $nextNumber = 1;
         if ($latestOrder && preg_match('/SO\/\d{4}\/(\d+)/', $latestOrder->order_number, $matches)) {
             $nextNumber = intval($matches[1]) + 1;
         }
         
         return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
     }
 
     public function createWithItems(array $data)
     {
         return DB::transaction(function () use ($data) {
             $data['order_date'] = now();
             $data['status'] = 'draft';
             
             if (!empty($data['mitra_id'])) {
                 $mitra = \App\Models\Mitra::find($data['mitra_id']);
                 if ($mitra) {
                     $data['customer_name'] = $mitra->code . ' - ' . $mitra->name;
                     if (empty($data['customer_phone'])) {
                         $data['customer_phone'] = $mitra->phone;
                     }
                     if (empty($data['customer_address'])) {
                         $data['customer_address'] = $mitra->address;
                     }
                 }
             }
             
             $subtotal = 0;
             $discountTotal = 0;
             $taxTotal = 0;
             $items = $data['items'] ?? [];
             unset($data['items']);
             
             $order = $this->repository->create($data);
             
             foreach ($items as $item) {
                 $product = \App\Models\Products::find($item['product_id']);
                 if (!$product) {
                     continue;
                 }
                 
                 $unitPrice = $product->selling_price;
                 $qty = $item['quantity'];
                 
                 $discPercent = isset($item['discount_percent']) ? floatval($item['discount_percent']) : 0;
                 $discAmount = isset($item['discount_amount']) ? intval($item['discount_amount']) : 0;
                 if ($discPercent > 0 && $discAmount == 0) {
                     $discAmount = ($unitPrice * $qty) * ($discPercent / 100);
                 }
                 
                 $lineTotalBeforeTax = ($unitPrice * $qty) - $discAmount;
                 
                 $taxPercent = 0;
                 $taxAmount = 0;
                 if (!empty($data['use_tax'])) {
                     $taxPercent = 11;
                     $taxAmount = $lineTotalBeforeTax * 0.11;
                 }
                 
                 $lineTotal = $lineTotalBeforeTax + $taxAmount;
                 
                 $subtotal += ($unitPrice * $qty);
                 $discountTotal += $discAmount;
                 $taxTotal += $taxAmount;
                 
                 \App\Models\SalesOrderItem::create([
                     'sales_order_id' => $order->id,
                     'product_id' => $product->id,
                     'quantity' => $qty,
                     'unit_price' => $unitPrice,
                     'discount_percent' => $discPercent,
                     'discount_amount' => $discAmount,
                     'tax_percent' => $taxPercent,
                     'tax_amount' => $taxAmount,
                     'line_total' => $lineTotal
                 ]);
             }
             
             $additionalDiscount = isset($data['additional_discount']) ? intval($data['additional_discount']) : 0;
             $grandTotal = ($subtotal - $discountTotal - $additionalDiscount) + $taxTotal;
             if ($grandTotal < 0) {
                 $grandTotal = 0;
             }
             
             $order->update([
                 'subtotal' => $subtotal,
                 'discount_total' => $discountTotal,
                 'additional_discount' => $additionalDiscount,
                 'tax_amount' => $taxTotal,
                 'grand_total' => $grandTotal
             ]);
             
             return $order;
         });
     }
 
     public function updateWithItems($id, array $data)
     {
         return DB::transaction(function () use ($id, $data) {
             $order = $this->repository->find($id);
             if (!$order) {
                 throw new \Exception("Order tidak ditemukan.");
             }
             
             if ($order->status !== 'draft' && $order->status !== 'submitted') {
                 throw new \Exception("Order yang sudah diproses tidak dapat diubah.");
             }
             
             if (!empty($data['mitra_id'])) {
                 $mitra = \App\Models\Mitra::find($data['mitra_id']);
                 if ($mitra) {
                     $data['customer_name'] = $mitra->code . ' - ' . $mitra->name;
                     if (empty($data['customer_phone'])) {
                         $data['customer_phone'] = $mitra->phone;
                     }
                     if (empty($data['customer_address'])) {
                         $data['customer_address'] = $mitra->address;
                     }
                 }
             }
             
             $subtotal = 0;
             $discountTotal = 0;
             $taxTotal = 0;
             $items = $data['items'] ?? [];
             unset($data['items']);
             
             $order->update($data);
             $order->items()->delete();
             
             foreach ($items as $item) {
                 $product = \App\Models\Products::find($item['product_id']);
                 if (!$product) {
                     continue;
                 }
                 
                 $unitPrice = $product->selling_price;
                 $qty = $item['quantity'];
                 
                 $discPercent = isset($item['discount_percent']) ? floatval($item['discount_percent']) : 0;
                 $discAmount = isset($item['discount_amount']) ? intval($item['discount_amount']) : 0;
                 if ($discPercent > 0 && $discAmount == 0) {
                     $discAmount = ($unitPrice * $qty) * ($discPercent / 100);
                 }
                 
                 $lineTotalBeforeTax = ($unitPrice * $qty) - $discAmount;
                 
                 $taxPercent = 0;
                 $taxAmount = 0;
                 if (!empty($data['use_tax'])) {
                     $taxPercent = 11;
                     $taxAmount = $lineTotalBeforeTax * 0.11;
                 }
                 
                 $lineTotal = $lineTotalBeforeTax + $taxAmount;
                 
                 $subtotal += ($unitPrice * $qty);
                 $discountTotal += $discAmount;
                 $taxTotal += $taxAmount;
                 
                 \App\Models\SalesOrderItem::create([
                     'sales_order_id' => $order->id,
                     'product_id' => $product->id,
                     'quantity' => $qty,
                     'unit_price' => $unitPrice,
                     'discount_percent' => $discPercent,
                     'discount_amount' => $discAmount,
                     'tax_percent' => $taxPercent,
                     'tax_amount' => $taxAmount,
                     'line_total' => $lineTotal
                 ]);
             }
             
             $additionalDiscount = isset($data['additional_discount']) ? intval($data['additional_discount']) : 0;
             $grandTotal = ($subtotal - $discountTotal - $additionalDiscount) + $taxTotal;
             if ($grandTotal < 0) {
                 $grandTotal = 0;
             }
             
             $order->update([
                 'subtotal' => $subtotal,
                 'discount_total' => $discountTotal,
                 'additional_discount' => $additionalDiscount,
                 'tax_amount' => $taxTotal,
                 'grand_total' => $grandTotal
             ]);
             
             return $order;
         });
     }
 
     public function submitOrder($id)
     {
         return DB::transaction(function () use ($id) {
             $order = $this->repository->find($id);
             if (!$order) {
                 throw new \Exception("Order tidak ditemukan.");
             }
             if ($order->status !== 'draft') {
                 throw new \Exception("Hanya order berstatus Draft yang dapat disubmit.");
             }
             
             $orderNumber = $this->generateOrderNumber();
             $order->update([
                 'status' => 'submitted',
                 'order_number' => $orderNumber
             ]);
             
             \App\Models\SalesOrderLog::create([
                 'sales_order_id' => $order->id,
                 'user_id' => auth()->id() ?? 1,
                 'from_status' => 'draft',
                 'to_status' => 'submitted',
                 'notes' => 'Sales order submitted.'
             ]);
             
             return $order;
         });
     }
 
     public function approveOrder($id, $userId)
     {
         return DB::transaction(function () use ($id, $userId) {
             $order = $this->repository->find($id);
             if (!$order) {
                 throw new \Exception("Order tidak ditemukan.");
             }
             if ($order->status !== 'submitted') {
                 throw new \Exception("Order tidak ditemukan atau status bukan submitted.");
             }
             
             $order->update([
                 'status' => 'approved',
                 'approved_by' => $userId,
                 'approved_at' => now(),
             ]);
             
             foreach ($order->items as $item) {
                 $product = $item->product;
                 if ($product) {
                     $product->current_stock = max(0, $product->current_stock - $item->quantity);
                     $product->save();
                 }
             }
             
             $year = now()->format('Y');
             $doPrefix = "DO/{$year}/";
             $latestDO = \App\Models\DeliveryOrder::where('do_number', 'like', $doPrefix . '%')
                 ->orderBy('do_number', 'desc')
                 ->first();
             $nextDoNum = 1;
             if ($latestDO && preg_match('/DO\/\d{4}\/(\d+)/', $latestDO->do_number, $matches)) {
                 $nextDoNum = intval($matches[1]) + 1;
             }
             $doNumber = $doPrefix . str_pad($nextDoNum, 4, '0', STR_PAD_LEFT);
             
             $looperRole = \App\Models\Role::where('slug', 'looper')->first();
             $defaultLooper = $looperRole ? \App\Models\User::where('role_id', $looperRole->id)->first() : null;
             
             \App\Models\DeliveryOrder::create([
                 'do_number' => $doNumber,
                 'sales_order_id' => $order->id,
                 'assigned_to' => $defaultLooper?->id,
                 'delivery_type' => $order->delivery_type,
                 'status' => 'pending',
             ]);
             
             $monthYear = now()->format('ym');
             $invPrefix = "INV/{$monthYear}/";
             $latestInv = \App\Models\Invoice::where('invoice_number', 'like', $invPrefix . '%')
                 ->orderBy('invoice_number', 'desc')
                 ->first();
             $nextInvNum = 1;
             if ($latestInv && preg_match('/INV\/\d{4}\/(\d+)/', $latestInv->invoice_number, $matches)) {
                 $nextInvNum = intval($matches[1]) + 1;
             }
             $invoiceNumber = $invPrefix . str_pad($nextInvNum, 4, '0', STR_PAD_LEFT);
             
             \App\Models\Invoice::create([
                 'invoice_number' => $invoiceNumber,
                 'sales_order_id' => $order->id,
                 'created_by' => $userId,
                 'invoice_date' => now(),
                 'due_date' => now()->addDays(14),
                 'subtotal' => $order->subtotal,
                 'discount_total' => $order->discount_total,
                 'tax_total' => $order->tax_amount,
                 'grand_total' => $order->grand_total,
                 'status' => 'belum_lunas',
             ]);
             
             \App\Models\SalesOrderLog::create([
                 'sales_order_id' => $order->id,
                 'user_id' => $userId,
                 'from_status' => 'submitted',
                 'to_status' => 'approved',
                 'notes' => 'Order approved, DO & Invoice generated.'
             ]);
             
             return $order;
         });
     }
 
     public function rejectOrder($id, $reason, $userId)
     {
         return DB::transaction(function () use ($id, $reason, $userId) {
             $order = $this->repository->find($id);
             if (!$order) {
                 throw new \Exception("Order tidak ditemukan.");
             }
             if ($order->status !== 'submitted') {
                 throw new \Exception("Order tidak ditemukan atau status bukan submitted.");
             }
             
             $order->update([
                 'status' => 'rejected',
                 'rejected_reason' => $reason
             ]);
             
             \App\Models\SalesOrderLog::create([
                 'sales_order_id' => $order->id,
                 'user_id' => $userId,
                 'from_status' => 'submitted',
                 'to_status' => 'rejected',
                 'notes' => 'Order rejected: ' . $reason
             ]);
             
             return $order;
         });
     }
 }

