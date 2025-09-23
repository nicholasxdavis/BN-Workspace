<?php
// api/integrations/encryption.php
require_once 'config.php';

function encrypt_token($data) {
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(ENCRYPTION_CIPHER));
    $encrypted = openssl_encrypt($data, ENCRYPTION_CIPHER, ENCRYPTION_KEY, 0, $iv);
    return base64_encode($encrypted . '::' . $iv);
}

function decrypt_token($data) {
    list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
    return openssl_decrypt($encrypted_data, ENCRYPTION_CIPHER, ENCRYPTION_KEY, 0, $iv);
}
?>
