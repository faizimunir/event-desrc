<?php

namespace App\Services\RegistrationImport;

use RuntimeException;

/** Satu baris gagal disimpan (kuota habis, duplikat, tidak eligible, dst.); baris lain tetap diproses. */
class RegistrationImportRowException extends RuntimeException {}
