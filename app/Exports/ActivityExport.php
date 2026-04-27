<?php

namespace App\Exports;

use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Spatie\Activitylog\Models\Activity;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ActivityExport implements FromCollection, WithHeadings, WithStyles, WithColumnFormatting
{
    /**
     * @var mixed|null
     */
    private mixed $dateFrom;
    /**
     * @var mixed|null
     */
    private mixed $dateTo;
    /**
     * @var mixed|null
     */
    private mixed $state;
    private mixed $status;
    /**
     * @var mixed|null
     */
    private mixed $mode_of_payment;
    private mixed $user_id;

    /**
    * @return array[]
     */

    public function __construct($dateFrom = null, $dateTo = null, $state = null, $status = null, $user_id = null, $mode_of_payment = null)
    {
        $this->dateFrom = $dateFrom ? Carbon::createFromFormat('Y-m-d', $dateFrom)->startOfDay() : null;
        $this->dateTo = $dateTo ? Carbon::createFromFormat('Y-m-d', $dateTo)->endOfDay() : null;
        $this->state = $state ?? null;
        $this->status = $status ?? null;
        $this->user_id = $user_id ?? null;
        $this->mode_of_payment = $mode_of_payment ?? null;
    }

    public function columnFormats(): array
    {
        return [
            'D' => 'yyyy-mm-dd hh:mm AM/PM',
            'E' => 'yyyy-mm-dd hh:mm AM/PM'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF4472C4'],
                ],
            ],
        ];
    }

    public function headings(): array {
        return [
            'Type',
            'Category',
            'Reference No',
            'Transaction Date',
            'Payment Date',
            'Customer',
            'Mode of Payment',
            'Bank',
            'Check No',
            'Check Date',
            'Amount',
            'Charging',
            'Remarks',
            'Deposit Date',
            'Bank Deposit',
            'Deposit Remarks',
            'Tag No'
        ];
    }

    public function collection()
    {
        $query = Activity::leftJoin('transactions', 'transactions.id', '=', 'activity_log.subject_id')
            ->select(
                'transactions.type',
                'transactions.category',
                'transactions.reference_no',
                'transactions.transaction_date',
                'transactions.payment_date',
                'transactions.customer_name',
                'transactions.mode_of_payment',
                'transactions.bank_name',
                'transactions.check_no',
                'transactions.check_date',
                'transactions.amount',
                'transactions.charge_name',
                'transactions.remarks',
                'transactions.deposit_date',
                'transactions.bank_deposit',
                'transactions.deposit_remarks',
                'transactions.tag_number',
            )
            ->when(isset($this->dateFrom), function ($query) {
                $query->whereDate('activity_log.created_at', '>=', $this->dateFrom);
            })
            ->when(isset($this->dateTo), function ($query) {
                $query->whereDate('activity_log.created_at', '<=', $this->dateTo);
            })
//            ->where('transactions.mode_of_payment', $this->mode_of_payment)
            ->when(isset($this->mode_of_payment), function ($query) {
                $query->where('transactions.mode_of_payment', $this->mode_of_payment);
            })
            ->when($this->state && $this->status !== null, function ($query) {
                $query->where('activity_log.event', $this->state . ':' . $this->status);
            })
            ->when(isset($this->user_id), function ($query) {
                $query->where('activity_log.causer_id', $this->user_id)
                    ->where('activity_log.description', 'Transaction Created');
            })
            ->get();

        return $query->map(function ($item) {
            if ($item->transaction_date || $item->payment_date ) {
                $item->transaction_date = Carbon::parse($item->transaction_date)->format('Y-m-d h:i A');
                $item->payment_date = Carbon::parse($item->payment_date)->format('Y-m-d h:i A');
            }
            return $item;
        });
    }

}
