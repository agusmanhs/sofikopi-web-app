<?php

namespace App\Services;

use App\Models\SalesOrderLog;
use App\Repositories\DeliveryOrderRepository;
use Illuminate\Support\Facades\DB;

class DeliveryOrderService extends BaseService
{
    public function __construct(DeliveryOrderRepository $repository)
    {
        parent::__construct($repository);
    }

    public function getByAssignedUser($userId)
    {
        return $this->repository->getByAssignedUser($userId);
    }

    public function reassignOrder($id, $assignedTo, $userId, $notes = null)
    {
        $do = $this->repository->find($id);
        if (! $do) {
            throw new \Exception('Delivery Order tidak ditemukan.');
        }
        if ($do->status === 'delivered') {
            throw new \Exception('Tidak dapat menugaskan ulang kurir untuk order yang sudah sampai.');
        }

        $do->update([
            'assigned_to' => $assignedTo,
            'assigned_by' => $userId,
            'assigned_at' => now(),
            'notes' => $notes ?? $do->notes,
            'status' => 'assigned',
        ]);

        return $do;
    }

    public function startDelivery($id)
    {
        $do = $this->repository->find($id);
        if (! $do) {
            throw new \Exception('Delivery Order tidak ditemukan.');
        }
        if ($do->status !== 'pending' && $do->status !== 'assigned') {
            throw new \Exception('Order tidak dalam status untuk mulai dikirim.');
        }

        $do->update(['status' => 'in_delivery']);

        return $do;
    }

    public function completePickup($id, string $receivedByName)
    {
        return DB::transaction(function () use ($id, $receivedByName) {
            $do = $this->repository->find($id);
            if (! $do) {
                throw new \Exception('Delivery Order tidak ditemukan.');
            }
            if ($do->delivery_type !== 'self_pickup') {
                throw new \Exception('Delivery Order ini bukan tipe ambil di store.');
            }
            if ($do->status === 'delivered') {
                throw new \Exception('Delivery Order sudah diselesaikan.');
            }

            $do->update([
                'status' => 'delivered',
                'delivery_date' => now()->toDateString(),
                'delivered_at' => now(),
                'received_by_name' => $receivedByName,
            ]);

            $salesOrder = $do->salesOrder;
            if ($salesOrder) {
                $fromStatus = $salesOrder->status;
                $salesOrder->update(['status' => 'completed']);

                SalesOrderLog::create([
                    'sales_order_id' => $salesOrder->id,
                    'user_id' => auth()->id() ?? 1,
                    'from_status' => $fromStatus,
                    'to_status' => 'completed',
                    'notes' => 'Order picked up at store. Received by: '.$receivedByName,
                ]);
            }

            return $do;
        });
    }

    public function completeDelivery($id, array $data)
    {
        $result = DB::transaction(function () use ($id, $data) {
            $do = $this->repository->find($id);
            if (! $do) {
                throw new \Exception('Delivery Order tidak ditemukan.');
            }
            if ($do->status === 'delivered') {
                throw new \Exception('Delivery Order sudah diselesaikan.');
            }

            $updateData = [
                'status' => 'delivered',
                'delivery_date' => now()->toDateString(),
                'received_by_name' => $data['received_by_name'] ?? null,
                'notes' => $data['notes'] ?? $do->notes,
                'delivered_at' => now(),
            ];

            if (! empty($data['proof_photo'])) {
                $updateData['proof_photo'] = $data['proof_photo'];
            }
            if (isset($data['proof_latitude'])) {
                $updateData['proof_latitude'] = $data['proof_latitude'];
            }
            if (isset($data['proof_longitude'])) {
                $updateData['proof_longitude'] = $data['proof_longitude'];
            }

            $do->update($updateData);

            $salesOrder = $do->salesOrder;
            if ($salesOrder) {
                $fromStatus = $salesOrder->status;
                $salesOrder->update(['status' => 'completed']);

                SalesOrderLog::create([
                    'sales_order_id' => $salesOrder->id,
                    'user_id' => auth()->id() ?? 1,
                    'from_status' => $fromStatus,
                    'to_status' => 'completed',
                    'notes' => 'Delivery Order completed by courier. Received by: '.($data['received_by_name'] ?? '-'),
                ]);
            }

            return $do;
        });

        try {
            app(\App\Services\TelegramService::class)->notifyDeliveryCompleted($result);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Telegram notification error: '.$e->getMessage());
        }

        return $result;
    }
}
