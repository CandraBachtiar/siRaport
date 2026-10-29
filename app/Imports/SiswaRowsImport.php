<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;

class SiswaRowsImport extends DefaultValueBinder implements ToArray, WithCustomValueBinder
{
    /** @var array<int, array<int, mixed>> */
    public array $rows = [];

    /** @param array<int, mixed> $array */
    public function array(array $array): void
    {
        $this->rows[] = $array;
    }

    public function bindValue(Cell $cell, mixed $value): bool
    {
        if (in_array($cell->getColumn(), ['A', 'B', 'E'], true) && is_numeric($value)) {
            $value = (string) $value;
        }

        return parent::bindValue($cell, $value);
    }
}
