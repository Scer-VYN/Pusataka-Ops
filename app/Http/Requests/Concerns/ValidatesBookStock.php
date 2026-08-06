<?php

namespace App\Http\Requests\Concerns;

trait ValidatesBookStock
{
    /**
     * @return array<string, string>
     */
    public function inventoryErrors(int $activeBorrowingCount): array
    {
        $totalStock = (int) $this->input('total_stock');
        $availableStock = (int) $this->input('available_stock');
        $errors = [];

        if ($totalStock < $activeBorrowingCount) {
            $errors['total_stock'] = "Total stock cannot be lower than the {$activeBorrowingCount} active borrowing(s).";
        }

        if ($availableStock > $totalStock - $activeBorrowingCount) {
            $remainingStock = max(0, $totalStock - $activeBorrowingCount);
            $errors['available_stock'] = "Available stock cannot exceed {$remainingStock}; active borrowings must remain covered.";
        }

        return $errors;
    }
}
