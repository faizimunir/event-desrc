<?php

namespace App\Services\RegistrationImport;

use RuntimeException;

/** Kesalahan tingkat file (bukan per baris): format salah, header hilang, event tidak cocok, dst. */
class RegistrationImportException extends RuntimeException {}
