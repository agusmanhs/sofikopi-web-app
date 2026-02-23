<?php

namespace App\Services;

use App\Interfaces\Repositories\BaseRepositoryInterface;

abstract class BaseService
{
    protected BaseRepositoryInterface $repository;

    public function __construct(BaseRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get all records
     */
    public function all()
    {
        return $this->repository->all();
    }

    /**
     * Find record by ID
     */
    public function find($id)
    {
        return $this->repository->find($id);
    }

    /**
     * Create new record
     */
    public function create(array $data)
    {
        return $this->repository->create($data);
    }

    /**
     * Update record by ID
     */
    public function update($id, array $data)
    {
        return $this->repository->update($id, $data);
    }

    /**
     * Delete record by ID
     */
    public function delete($id)
    {
        return $this->repository->delete($id);
    }

    /**
     * Get paginated records
     */
    public function paginate($perPage = 10)
    {
        return $this->repository->paginate($perPage);
    }
}
