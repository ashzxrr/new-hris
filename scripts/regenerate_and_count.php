<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;


$payrollId = $argv[1] ?? null;
if (!$payrollId) { echo "Usage: php scripts/regenerate_and_count.php <payroll_id>\n"; exit(2); }

$controller = $app->make(App\Http\Controllers\PayrollController::class);

// Call generateGrandTotal with force=true
$request = Request::create('/', 'POST', ['force' => '1']);
try {
    echo "Calling generateGrandTotal(force=1) for payroll {$payrollId}...\n";
    $controller->generateGrandTotal($request, $payrollId);
    echo "generateGrandTotal finished.\n";
} catch (Throwable $e) {
    echo "generateGrandTotal error: " . $e->getMessage() . "\n";
}

// Call generatePengajuan
try {
    echo "Calling generatePengajuan for payroll {$payrollId}...\n";
    $controller->generatePengajuan($payrollId);
    echo "generatePengajuan finished.\n";
} catch (Throwable $e) {
    echo "generatePengajuan error: " . $e->getMessage() . "\n";
}

$db = $app->make('db');
$counts = $db->select('select coalesce(section, "(null)") as section, count(*) as cnt from payroll_pengajuan where payroll_id = ? group by section', [$payrollId]);
echo "Section counts:\n";
echo json_encode($counts, JSON_PRETTY_PRINT) . "\n";

// sample Cuci Kotor rows
$cuci = $db->select('select nip, jenis, section from payroll_pengajuan where payroll_id = ? and jenis = ? limit 10', [$payrollId, 'Cuci Kotor']);
echo "Sample 'Cuci Kotor' rows:\n";
echo json_encode($cuci, JSON_PRETTY_PRINT) . "\n";

// compute total_gram for borongan section types
$sumCabut = $db->selectOne('select sum(br.total_gram) as sum_gram from borongan_rekap br join borongan_imports bi on bi.id = br.borongan_import_id where bi.payroll_id = ? and bi.jenis = ? and br.nip in (select nip from payroll_pengajuan where payroll_id = ? and section = ?)', [$payrollId, 'cabut', $payrollId, 'cabut']);
$sumHcr = $db->selectOne('select sum(br.total_gram) as sum_gram from borongan_rekap br join borongan_imports bi on bi.id = br.borongan_import_id where bi.payroll_id = ? and bi.jenis = ? and br.nip in (select nip from payroll_pengajuan where payroll_id = ? and section = ?)', [$payrollId, 'hcr', $payrollId, 'hcr']);
$sumMoulding = $db->selectOne('select sum(br.total_gram) as sum_gram from borongan_rekap br join borongan_imports bi on bi.id = br.borongan_import_id where bi.payroll_id = ? and bi.jenis = ? and br.nip in (select nip from payroll_pengajuan where payroll_id = ? and section = ?)', [$payrollId, 'moulding', $payrollId, 'moulding']);

echo "Borongan total_gram sums:\n";
echo "cabut: " . ($sumCabut->sum_gram ?? 0) . "\n";
echo "hcr: " . ($sumHcr->sum_gram ?? 0) . "\n";
echo "moulding: " . ($sumMoulding->sum_gram ?? 0) . "\n";

echo "Done.\n";
