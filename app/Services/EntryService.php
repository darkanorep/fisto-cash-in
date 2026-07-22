<?php

namespace App\Services;

use App\Models\Entry;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class EntryService
{
    private Entry $entry;

    public function __construct(Entry $entry) {
        $this->entry = $entry;
    }

    public function getEntry() {
        return $this->entry->with(['accountTitles'])->orderBy('updated_at', 'desc')->useFilters()->dynamicPaginate();;
    }

    public function createEntry(array $data): Entry
    {
        return DB::transaction(function () use ($data) {
            $accountTitles = collect(Arr::pull($data, 'account_titles'))
                ->keyBy('account_title_id')
                ->map(fn (array $row) => Arr::only($row, [
                    'code',
                    'title',
                    'account_type',
                    'account_group',
                    'sub_group',
                    'financial_statement',
                    'normal_balances',
                    'unit',
                    'allocation',
                ]))
                ->all();

            $entry = $this->entry->create($data); // only 'description'

            $entry->accountTitles()->attach($accountTitles);

            return $entry;
        });
    }

    public function getEntryById($entryId) {
        return $this->entry->with(['accountTitles'])->find($entryId);
    }

    public function updateEntry(Entry $entry, array $data): Entry
    {
        return DB::transaction(function () use ($entry, $data) {
            $accountTitles = collect(Arr::pull($data, 'account_titles'))
                ->keyBy('account_title_id')
                ->map(fn (array $row) => Arr::only($row, [
                    'code',
                    'title',
                    'account_type',
                    'account_group',
                    'sub_group',
                    'financial_statement',
                    'normal_balances',
                    'unit',
                    'allocation',
                ]))
                ->all();

            $entry->update($data); // only 'description'

            $entry->accountTitles()->sync($accountTitles);

            return $entry;
        });
    }

    public function changeStatus($id) {
        $entry = $this->entry->getEntryById($id);

        if ($entry->trashed()) {
            $entry->restore();
        } else {
            $entry->delete();
        }

        return $entry;
    }

}
