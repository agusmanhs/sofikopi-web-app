<?php

namespace App\Http\Requests\MitraPos\Concerns;

trait NormalizesRupiah
{
    /**
     * Input uang dari form berformat Indonesia ("15.000.000" atau "1.500,50"):
     * titik = pemisah ribuan, koma = pemisah desimal. Nilai berpola "6000.00"
     * (1-2 digit setelah titik, tanpa titik lain) adalah desimal dari database
     * atau API, bukan ribuan — jangan membuang titiknya, kalau tidak 6000.00
     * menjadi 600000.
     */
    protected function normalizeRupiah(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $value = trim($value);

        if ($value === '' || preg_match('/^\d+\.\d{1,2}$/', $value) === 1) {
            return $value;
        }

        return str_replace(',', '.', str_replace('.', '', $value));
    }
}
