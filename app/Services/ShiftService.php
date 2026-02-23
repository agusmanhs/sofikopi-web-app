<?php

namespace App\Services;

use App\Interfaces\Repositories\ShiftRepositoryInterface;
use Illuminate\Support\Facades\Log;

class ShiftService extends BaseService
{
    public function __construct(ShiftRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function getAllShifts($filters = [])
    {
        return $this->repository->getAllWithRelations($filters);
    }

    public function getAktif()
    {
        return $this->repository->getAktif();
    }

    public function storeShift(array $data)
    {
        return $this->create($data);
    }

    public function updateShift($shiftId, array $data)
    {
        return $this->update($shiftId, $data);
    }

    public function deleteShift($shiftId)
    {
        return $this->delete($shiftId);
    }

    public function getShiftsByDivisi($divisiId)
    {
        return $this->repository->getByDivisi($divisiId);
    }
}
