<?php

namespace App\Application\Employees\Imports;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class EmployeeImportChunkReadFilter implements IReadFilter
{
    public function __construct(
        private readonly int $startRow,
        private readonly int $endRow,
    ) {
    }

    public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
    {
        return $row === 1 || ($row >= $this->startRow && $row <= $this->endRow);
    }
}
