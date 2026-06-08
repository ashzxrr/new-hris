<?php

namespace App\Services;

class FingerprintService
{
    private function sendSoap(string $ip, int $port, string $xml): string|false
    {
        $timeout = config('fingerprint.timeout', 10);
        $socket = @fsockopen($ip, $port, $errno, $errstr, $timeout);

        if (! $socket) {
            return false;
        }

        $body = trim($xml);
        $request = "POST /iWsService HTTP/1.1\r\n";
        $request .= "Host: {$ip}:{$port}\r\n";
        $request .= "Content-Type: text/xml\r\n";
        $request .= "Content-Length: " . strlen($body) . "\r\n";
        $request .= "Connection: Close\r\n\r\n";
        $request .= $body;

        fwrite($socket, $request);

        $response = '';
        while (! feof($socket)) {
            $response .= fgets($socket, 1024);
        }

        fclose($socket);

        return $response;
    }

    public function sendSoapPublic(string $ip, int $port, string $xml): string|false
    {
        return $this->sendSoap($ip, $port, $xml);
    }

    private function isReachable(string $ip, int $port): bool
    {
        $socket = @fsockopen($ip, $port, $errno, $errstr, 5);

        if (! $socket) {
            return false;
        }

        fclose($socket);

        return true;
    }

    public function getUsers(): array
    {
        $machines = config('fingerprint.machines', []);
        $users = [];

        foreach ($machines as $machine) {
            if (empty($machine['active'])) {
                continue;
            }

            $ip = $machine['ip'];
            $port = $machine['port'];
            $key = $machine['key'];

            if (! $this->isReachable($ip, $port)) {
                continue;
            }

            $xml = "<GetAllUserInfo><ArgComKey xsi:type=\"xsd:integer\">{$key}</ArgComKey></GetAllUserInfo>";
            $response = $this->sendSoap($ip, $port, $xml);

            if (! $response) {
                continue;
            }

            $body = $this->extractXmlBody($response);
            $rows = $this->extractRows($body);

            foreach ($rows as $row) {
                $pin = $this->normalizePin($this->extractTag($row, 'PIN2'));
                $name = trim($this->extractTag($row, 'Name'));

                if ($pin === '' || isset($users[$pin])) {
                    continue;
                }

                $users[$pin] = $name;
            }
        }

        return $users;
    }

    public function getAttendanceRange(string $tanggalDari, string $tanggalSampai, array $users = []): array
    {
        $allAttendance = [];
        $machines = config('fingerprint.machines', []);

        $timestampDari = strtotime($tanggalDari);
        $timestampSampai = strtotime($tanggalSampai . ' 23:59:59');

        foreach ($machines as $machine) {
            if (empty($machine['active'])) {
                continue;
            }

            $ip = $machine['ip'];
            $port = $machine['port'];
            $key = $machine['key'];
            $machineName = $machine['name'] ?? "{$ip}:{$port}";

            if (! $this->isReachable($ip, $port)) {
                continue;
            }

            $soap = "<GetAttLog>
                <ArgComKey xsi:type=\"xsd:integer\">{$key}</ArgComKey>
                <Arg><PIN xsi:type=\"xsd:integer\">All</PIN></Arg>
            </GetAttLog>";

            $response = $this->sendSoap($ip, $port, $soap);
            if (! $response || strpos($response, '<Row>') === false) {
                continue;
            }

            $rows = $this->extractRows($response);

            foreach ($rows as $row) {
                $pin = $this->normalizePin($this->extractTag($row, 'PIN'));
                $waktu = trim($this->extractTag($row, 'DateTime'));
                $verified = trim($this->extractTag($row, 'Verified'));
                $statusCode = trim($this->extractTag($row, 'Status'));

                if ($waktu === '') {
                    continue;
                }

                $timestampRecord = strtotime($waktu);
                if ($timestampRecord === false) {
                    continue;
                }

                if ($timestampRecord < $timestampDari || $timestampRecord > $timestampSampai) {
                    continue;
                }

                $pinVal = $this->normalizePin($pin);
                $namaVal = $users[$pinVal] ?? '(Tidak Diketahui)';
                $statusText = $statusCode === '0' ? 'IN' : 'OUT';
                $tanggal = substr($waktu, 0, 10);

                $dedupeKey = $pinVal . '_' . $waktu;
                if (! isset($allAttendance[$dedupeKey])) {
                    $allAttendance[$dedupeKey] = [
                        'pin' => $pinVal,
                        'nama' => $namaVal,
                        'datetime' => $waktu,
                        'tanggal' => $tanggal,
                        'verified' => $verified !== '' ? $verified : '-',
                        'status' => $statusText,
                        'machine_name' => $machineName,
                    ];
                }
            }
        }

        usort($allAttendance, fn ($a, $b) => strtotime($b['datetime']) - strtotime($a['datetime']));

        return array_values($allAttendance);
    }

    public function normalizePin(string $pin): string
    {
        $pin = trim($pin);

        if ($pin === '') {
            return '';
        }

        return is_numeric($pin) ? (string) intval($pin) : $pin;
    }

    public function testConnections(): array
    {
        $machines = config('fingerprint.machines', []);
        $statuses = [];

        foreach ($machines as $machine) {
            $online = $this->isReachable($machine['ip'], $machine['port']);

            $statuses[] = [
                'name' => $machine['name'] ?? null,
                'ip' => $machine['ip'],
                'port' => $machine['port'],
                'online' => $online,
            ];
        }

        return $statuses;
    }

    private function extractXmlBody(string $response): string
    {
        if (preg_match('/<\?xml.*$/s', $response, $matches)) {
            return $matches[0];
        }

        return $response;
    }

    private function extractRows(string $xml): array
    {
        if (! preg_match_all('/<Row>(.*?)<\/Row>/is', $xml, $matches)) {
            return [];
        }

        return $matches[1];
    }

    private function extractTag(string $xml, string $tag): string
    {
        if (preg_match('/<' . preg_quote($tag, '/') . '>(.*?)<\/' . preg_quote($tag, '/') . '>/is', $xml, $matches)) {
            return trim($matches[1]);
        }

        return '';
    }
}
