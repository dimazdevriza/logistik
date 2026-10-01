<?php

namespace App\Traits;

trait WithTableSorting
{
    public function sortBy(string $field): void
    {
        if (! array_key_exists($field, $this->sortableColumns())) {
            return;
        }

        [$currentField, $currentDirection] = $this->tableSortParts();
        $nextDirection = $currentField === $field && $currentDirection === 'asc' ? 'desc' : 'asc';

        $this->sort = $field . '_' . $nextDirection;
        $this->resetPage();
    }

    protected function applyTableSort($query)
    {
        [$field, $direction] = $this->tableSortParts();
        $sorter = $this->sortableColumns()[$field];

        if ($sorter instanceof \Closure) {
            $sorter($query, $direction);
        } else {
            $query->orderBy($sorter, $direction);
        }

        return $query;
    }

    protected function tableSortParts(): array
    {
        $columns = $this->sortableColumns();
        $defaultField = array_key_first($columns);

        if (preg_match('/^(.+)_(asc|desc)$/', (string) $this->sort, $matches)
            && array_key_exists($matches[1], $columns)) {
            return [$matches[1], $matches[2]];
        }

        return [$defaultField, 'asc'];
    }

    abstract protected function sortableColumns(): array;
}
