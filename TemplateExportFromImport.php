<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class TemplateExportFromImport implements FromArray, WithHeadings, ShouldAutoSize
{
    protected $headings;
    protected $data;

    //cara pemakaian
    // Export template kosong (3 baris kosong)
    // return Excel::download(new TemplateExportFromImport(SalesmanRecordImport::class), 'template_sales.xlsx');

    // // Export template dengan satu baris data (dari array)
    // return Excel::download(new TemplateExportFromImport(SalesmanRecordImport::class, [
    //     'no_invoice' => 'INV001',
    //     'id_sales' => 'S123',
    //     'id_supervisor' => 'SP456',
    // ]), 'template_sales_filled.xlsx');

    // contoh function examples di class import nanti
    // public function examples(): array
    // {
    //     return [
    //         // Example 1
    //         [
    //             'id' => 'SLS01',
    //             'nama' => 'Budi Santoso',
    //             'id_grade' => 'S1',
    //             'id_supervisor' => 'SPV01',
    //             'id_cabang' => 'MRM01',
    //             'status' => 'aktif',
    //             'periode_masuk' => '2023-01',
    //             // 'changes' => '{"field": "status", "old": "inactive", "new": "aktif"}', // Example JSON structure
    //         ],

    //         // Example 2
    //         [
    //             'id' => 'SLS02',
    //             'nama' => 'Rina Dewi',
    //             'id_grade' => 'C2',
    //             'id_supervisor' => 'SPV02',
    //             'id_cabang' => 'MRM01',
    //             'status' => 'nonaktif',
    //             'periode_masuk' => '2022-08',
    //             // 'changes' => '{"field": "status", "old": "aktif", "new": "nonaktif"}', // Example JSON structure
    //         ]
    //     ];
    // }

    /**
     * Konstruktor untuk menyiapkan template export berdasarkan class import yang diberikan.
     *
     * @param string $importClass Nama lengkap dari class import (harus mengimplementasikan method 'rules')
     * @param array|null $data Data opsional yang akan dimasukkan langsung ke dalam template.
     *                          Jika tidak diberikan, template akan diisi dengan contoh data atau data dummy.
     *
     * Penjelasan:
     * - Jika $data diberikan, data tersebut akan dimasukkan ke dalam template export sebagai satu baris.
     * - Jika $data tidak diberikan, dan class import memiliki method `examples()`, maka data dari `examples()` akan digunakan.
     * - Jika tidak ada data yang diberikan, template akan diisi dengan 3 baris data dummy yang dihasilkan berdasarkan aturan dalam class import.
     */
    public function __construct(string $importClass, array $data = null)
    {
        if (!class_exists($importClass) || !method_exists($importClass, 'rules')) {
            throw new \InvalidArgumentException("Import class must exist and implement 'rules' method.");
        }

        $importInstance = new $importClass;
        $rules = $importInstance->rules();
        $this->headings = array_keys($rules);

        // PRIORITAS 1: Gunakan data dari parameter
        if ($data) {
            $this->data = [array_map(fn($key) => $data[$key] ?? '', $this->headings)];
            return;
        }

        // PRIORITAS 2: Gunakan method examples() jika tersedia
        if (method_exists($importInstance, 'examples')) {
            $examples = $importInstance->examples();

            // Jika satu row (associative array)
            if ($this->isAssoc($examples)) {
                $this->data = [array_map(fn($key) => $examples[$key] ?? '', $this->headings)];
                return;
            }

            // Jika multiple rows (array of associative array)
            if (is_array($examples)) {
                $this->data = array_map(function ($row) {
                    return array_map(fn($key) => $row[$key] ?? '', $this->headings);
                }, $examples);
                return;
            }
        }

        // PRIORITAS 3: Generate dummy data berdasarkan rules
        $row = [];
        foreach ($this->headings as $field) {
            $ruleSet = $rules[$field] ?? [];
            $ruleSet = is_string($ruleSet) ? explode('|', $ruleSet) : $ruleSet;
            $row[] = $this->generateExampleValue($ruleSet, $field);
        }

        $this->data = array_fill(0, 3, $row);
    }

    /**
     * Generate dummy example value from rules.
     */
    protected function generateExampleValue(array $rules, string $field)
    {
        foreach ($rules as $rule) {
            $rule = is_string($rule) ? $rule : (is_object($rule) ? get_class($rule) : '');

            if (str_contains($rule, 'numeric') || str_contains($rule, 'integer')) {
                return rand(1000, 9999);
            }

            if (str_contains($rule, 'email')) {
                return 'example@domain.com';
            }

            if (str_contains($rule, 'date')) {
                return now()->toDateString();
            }

            if (str_contains($rule, 'boolean')) {
                return 'true';
            }

            if (str_contains($rule, 'string')) {
                return strtoupper($field) . '_SAMPLE';
            }
        }

        return '';
    }

    /**
     * Check if an array is associative.
     */
    protected function isAssoc(array $arr): bool
    {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        return $this->headings;
    }
}
