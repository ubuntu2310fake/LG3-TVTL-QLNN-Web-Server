<?php
// includes/totp.php — TOTP RFC 6238 Helper Class for 2FA

class TOTP {
    private static $base32Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Tạo Secret Key ngẫu nhiên chuẩn Base32
     */
    public static function generateSecret($length = 16): string {
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= self::$base32Chars[random_int(0, 31)];
        }
        return $secret;
    }

    /**
     * Giải mã chuỗi Base32 thành binary
     */
    private static function base32Decode($base32): string {
        $base32 = strtoupper($base32);
        $buffer = 0;
        $bitsLeft = 0;
        $binary = '';

        for ($i = 0; $i < strlen($base32); $i++) {
            $ch = $base32[$i];
            $val = strpos(self::$base32Chars, $ch);
            if ($val === false) continue;

            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $binary .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }
        return $binary;
    }

    /**
     * Tính toán mã OTP 6 chữ số tại thời điểm $timeSlice
     */
    public static function getCode($secret, $timeSlice = null): string {
        if ($timeSlice === null) {
            $timeSlice = floor(time() / 30);
        }

        $secretKey = self::base32Decode($secret);
        $time = pack('N*', 0) . pack('N*', $timeSlice);
        $hmac = hash_hmac('sha1', $time, $secretKey, true);
        $offset = ord(substr($hmac, -1)) & 0x0F;

        $hashPart = substr($hmac, $offset, 4);
        $value = unpack('N', $hashPart)[1] & 0x7FFFFFFF;

        $modulo = $value % 1000000;
        return str_pad((string)$modulo, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Xác minh mã OTP do người dùng nhập với Secret (cho phép lệch ± $discrepancy x 30s)
     */
    public static function verifyCode($secret, $code, $discrepancy = 1): bool {
        if (empty($secret) || empty($code)) return false;
        $code = trim($code);
        if (strlen($code) !== 6 || !ctype_digit($code)) return false;

        $currentTimeSlice = floor(time() / 30);

        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $calculatedCode = self::getCode($secret, $currentTimeSlice + $i);
            if (hash_equals($calculatedCode, $code)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Tạo URI theo chuẩn otpauth:// cho Google Authenticator / Authy
     */
    public static function getProvisioningUri($secret, $accountName, $issuer = 'THPT LG3'): string {
        $label = rawurlencode($issuer) . ':' . rawurlencode($accountName);
        return sprintf(
            'otpauth://totp/%s?secret=%s&issuer=%s&period=30&digits=6',
            $label,
            $secret,
            rawurlencode($issuer)
        );
    }

    /**
     * Render mã QR Code thành chuỗi Data URI (SVG/PNG) để hiển thị thẻ <img src="...">
     */
    public static function getQRCodeDataUri($secret, $accountName, $issuer = 'THPT LG3'): string {
        $otpUri = self::getProvisioningUri($secret, $accountName, $issuer);

        // Thử dùng chillerlan\QRCode nếu có trong vendor
        if (class_exists('\chillerlan\QRCode\QRCode')) {
            try {
                return (new \chillerlan\QRCode\QRCode())->render($otpUri);
            } catch (\Exception $e) {}
        }

        // Thử dùng Endroid\QrCode nếu có
        if (class_exists('\Endroid\QrCode\QrCode')) {
            try {
                $qrCode = \Endroid\QrCode\QrCode::create($otpUri);
                $writer = new \Endroid\QrCode\Writer\PngWriter();
                $result = $writer->write($qrCode);
                return $result->getDataUri();
            } catch (\Exception $e) {}
        }

        // Fallback dùng dịch vụ Google Chart / QuickChart QR API an toàn
        return 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($otpUri);
    }
}
?>
