<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class UpdateModelAvailableColumns extends Command
{
    protected $signature = 'model:update-available-columns {model}';
    protected $description = 'Update model dengan semua kolom yang tersedia dari database';

    public function handle()
    {
        $modelName = $this->argument('model');
        $modelClass = "App\\Models\\$modelName";

        if (!class_exists($modelClass)) {
            $this->error("Model $modelClass tidak ditemukan.");
            return;
        }

        $model = new $modelClass();
        $table = $model->getTable();
        $columns = Schema::getColumnListing($table);

        if (empty($columns)) {
            $this->error("Tabel '$table' tidak memiliki kolom atau tidak ditemukan.");
            return;
        }

        // **Memastikan setiap elemen memiliki petik satu yang benar**
        $formattedColumns = array_map(fn($col) => "'$col'", $columns);
        $columnsString = "[\n        " . implode(",\n        ", $formattedColumns) . "\n    ];";

        $modelPath = app_path("Models/$modelName.php");

        if (!file_exists($modelPath)) {
            $this->error("File model $modelPath tidak ditemukan.");
            return;
        }

        $content = file_get_contents($modelPath);

        // **Cek apakah properti $availableColumns sudah ada**
        if (!preg_match('/public static array \$availableColumns\s*=\s*\[.*?\];/s', $content)) {
            $this->warn("❌ Properti '\$availableColumns' belum ditemukan di model.");
            $this->warn("Silakan tambahkan baris berikut ke dalam model '$modelName' secara manual terlebih dahulu:");
            $this->line("\n    public static array \$availableColumns = [];\n");
            return;
        }

        // **Update dengan daftar kolom yang benar**
        $newContent = preg_replace(
            '/public static array \$availableColumns\s*=\s*\[.*?\];/s',
            "public static array \$availableColumns = $columnsString",
            $content
        );

        file_put_contents($modelPath, $newContent);

        $this->info("✅ Model $modelName berhasil diperbarui dengan daftar kolom yang terformat rapi.");
    }
}
