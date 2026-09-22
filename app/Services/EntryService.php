<?php

namespace App\Services;

use App\Models\Entry;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class EntryService
{
    private Entry $entry;

    public function __construct(Entry $entry) {
        $this->entry = $entry;
    }

    public function getEntry()
    {
        $entries = $this->entry
            ->orderBy('updated_at', 'desc')
            ->useFilters()
            ->dynamicPaginate();

        $collection = $entries instanceof LengthAwarePaginator
            ? $entries->getCollection()
            : $entries;

        $entryIds = $collection->pluck('id');

        $accountTitleEntriesByEntryId = [];

        if ($entryIds->isNotEmpty()) {
            $accountTitleEntryRows = DB::table('account_title_entry')
                ->whereIn('entry_id', $entryIds)
                ->get();

            foreach ($accountTitleEntryRows as $row) {
                $accountTitleEntriesByEntryId[$row->entry_id][] = $row;
            }
        }

        $collection->transform(function ($entry) use ($accountTitleEntriesByEntryId) {
            $entry->account_title_entries = collect($accountTitleEntriesByEntryId[$entry->id] ?? []);
            return $entry;
        });

        return $entries;
    }

    public function createEntry(array $data): Entry
    {
        return DB::transaction(function () use ($data) {
            $accountTitles = collect(Arr::pull($data, 'account_titles'))
                ->map(fn (array $row) => Arr::only($row, [
                    'account_title_id',
                    'code',
                    'title',
                    'account_type',
                    'account_group',
                    'sub_group',
                    'financial_statement',
                    'normal_balance',
                    'unit',
                    'allocation',
                ]));

            $entry = $this->entry->create($data);

            $now = now();

            $rows = $accountTitles
                ->map(fn (array $row) => $row + [
                        'entry_id'   => $entry->id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])
                ->all();

            DB::table('account_title_entry')->insert($rows);

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
                ->map(fn (array $row) => Arr::only($row, [
                    'account_title_id',
                    'code',
                    'title',
                    'account_type',
                    'account_group',
                    'sub_group',
                    'financial_statement',
                    'normal_balance',
                    'unit',
                    'allocation',
                ]));

            $entry->update($data); // only 'description'

            DB::table('account_title_entry')
                ->where('entry_id', $entry->id)
                ->delete();

            if ($accountTitles->isNotEmpty()) {
                $now = now();

                $rows = $accountTitles
                    ->map(fn (array $row) => $row + [
                            'entry_id'   => $entry->id,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ])
                    ->all();

                DB::table('account_title_entry')->insert($rows);
            }

            return $entry;
        });
    }

    public function changeStatus($id) {
        $entry = $this->entry->where('id', $id)->withTrashed()->first();

        if ($entry->trashed()) {
            $entry->restore();
        } else {
            $entry->delete();
        }

        return $entry;
    }

}
