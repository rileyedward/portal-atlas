<?php

namespace App\Services\DataExchange;

final class ImportResult
{
    public int $created = 0;

    public int $updated = 0;

    public int $unchanged = 0;

    /** @var list<string> */
    public array $errors = [];

    /** @var list<string> */
    public array $warnings = [];

    public function __construct(public bool $dryRun) {}

    public function ok(): bool
    {
        return $this->errors === [];
    }

    /**
     * @return array{dry_run: bool, created: int, updated: int, unchanged: int, errors: list<string>, warnings: list<string>}
     */
    public function toArray(): array
    {
        return [
            'dry_run' => $this->dryRun,
            'created' => $this->created,
            'updated' => $this->updated,
            'unchanged' => $this->unchanged,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
        ];
    }
}
