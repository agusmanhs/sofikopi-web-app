<?php

namespace App\Repositories\MitraPos;

use App\Interfaces\Repositories\MitraPos\AkuntansiJournalEntryRepositoryInterface;
use App\Models\AkuntansiJournalEntry;
use App\Repositories\BaseRepository;

class AkuntansiJournalEntryRepository extends BaseRepository implements AkuntansiJournalEntryRepositoryInterface
{
    public function __construct(AkuntansiJournalEntry $model)
    {
        $this->model = $model;
    }
}
