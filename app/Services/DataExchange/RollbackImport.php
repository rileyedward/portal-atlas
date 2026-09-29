<?php

namespace App\Services\DataExchange;

use RuntimeException;

/**
 * Thrown inside the import transaction to roll back a dry run or a failed import.
 */
final class RollbackImport extends RuntimeException {}
