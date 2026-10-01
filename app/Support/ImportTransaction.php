<?php

namespace App\Support;

use DateTimeImmutable;
use DateTimeInterface;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use RuntimeException;

final class ImportTransaction
{
    public static function date(mixed $value, int $row, string $item): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_numeric($value) && (float) $value > 20_000 && (float) $value < 100_000) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }

        $value = trim((string) $value);
        foreach ([
            'Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d',
            'Y-m-d H:i', 'Y-m-d H:i:s',
            'd/m/Y H:i', 'd/m/Y H:i:s',
            'd-m-Y H:i', 'd-m-Y H:i:s',
            'Y/m/d H:i', 'Y/m/d H:i:s',
        ] as $format) {
            $date = DateTimeImmutable::createFromFormat('!'.$format, $value);
            $errors = DateTimeImmutable::getLastErrors();

            if ($date && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0)) && $date->format($format) === $value) {
                return $date->format('Y-m-d');
            }
        }

        throw new RuntimeException("Baris {$row} ({$item}): Tanggal tidak valid. Gunakan YYYY-MM-DD, DD/MM/YYYY, atau tanggal Excel.");
    }

    public static function dateTime(mixed $value, int $row, string $item): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('H:i:s') === '00:00:00' ? null : $value->format('Y-m-d H:i:s');
        }

        if (is_numeric($value) && (float) $value > 20_000 && (float) $value < 100_000) {
            if ((float) $value === (float) (int) $value) {
                return null;
            }

            return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d H:i:s');
        }

        $value = trim((string) $value);
        if ($value === '' || ! preg_match('/\d{1,2}:\d{2}/', $value)) {
            return null;
        }

        foreach ([
            'Y-m-d H:i', 'Y-m-d H:i:s',
            'd/m/Y H:i', 'd/m/Y H:i:s',
            'd-m-Y H:i', 'd-m-Y H:i:s',
            'Y/m/d H:i', 'Y/m/d H:i:s',
        ] as $format) {
            $date = DateTimeImmutable::createFromFormat('!'.$format, $value);
            $errors = DateTimeImmutable::getLastErrors();

            if ($date && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0)) && $date->format($format) === $value) {
                return $date->format('Y-m-d H:i:s');
            }
        }

        throw new RuntimeException("Baris {$row} ({$item}): Waktu penerimaan tidak valid.");
    }

    public static function code(string $prefix, mixed $provided, array $identity, int $row, string $item): string
    {
        $provided = trim((string) $provided);
        if ($provided !== '') {
            $code = strtoupper($provided);

            if (strlen($code) > 30 || ! preg_match('/^[A-Z0-9_-]+$/', $code)) {
                throw new RuntimeException("Baris {$row} ({$item}): Kode Transaksi hanya boleh berisi huruf, angka, garis bawah, atau tanda minus (maksimal 30 karakter).");
            }

            return $code;
        }

        return $prefix.'-'.strtoupper(substr(hash('sha256', serialize($identity)), 0, 26));
    }
}
