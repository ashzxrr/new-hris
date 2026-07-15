<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PayrollPengajuan;

$payrollId = $argv[1] ?? null;
if (!$payrollId) { echo "Usage: php scripts/real_regen_and_export.php <payroll_id>\n"; exit(2); }

// delete existing pengajuan rows for payroll
PayrollPengajuan::where('payroll_id', $payrollId)->delete();
echo "Deleted existing payroll_pengajuan rows for payroll {$payrollId}\n";

// call real controller generatePengajuan
$controller = $app->make(App\Http\Controllers\PayrollController::class);
try {
    echo "Calling real generatePengajuan({$payrollId})...\n";
    $controller->generatePengajuan($payrollId);
    echo "generatePengajuan completed.\n";
} catch (Throwable $e) {
    echo "generatePengajuan error: " . $e->getMessage() . "\n";
}

// report counts per section
$db = $app->make('db');
$counts = $db->select('select coalesce(section, "(null)") as section, count(*) as cnt from payroll_pengajuan where payroll_id = ? group by section', [$payrollId]);
echo "Section counts after real generatePengajuan:\n" . json_encode($counts, JSON_PRETTY_PRINT) . "\n";

// call exportPengajuan to generate XLSX (file saved to sys temp dir)
try {
    echo "Calling exportPengajuan({$payrollId}) to generate XLSX...\n";
    $response = $controller->exportPengajuan($payrollId);
    echo "exportPengajuan returned.\n";
} catch (Throwable $e) {
    echo "exportPengajuan error: " . $e->getMessage() . "\n";
}

// attempt to locate temp file
$payroll = App\Models\Payroll::find($payrollId);
$fileNameSafe = preg_replace('/[\/\\\s]+/', '_', $payroll->periode);
$fileName = "Pengajuan_Gaji_{$fileNameSafe}.xlsx";
$tmpFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $fileName;
if (file_exists($tmpFile)) {
    echo "Export file created at: {$tmpFile}\n";
} else {
    echo "Export file not found at expected path: {$tmpFile}\n";
}

echo "Done.\n";
