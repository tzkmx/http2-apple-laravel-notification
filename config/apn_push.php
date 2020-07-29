<?php

return [
    'user_agent' =>             env('APN_HTTP2_USER_AGENT', 'ApantlePush/http2'),
    'apns_topic' =>             env('APN_HTTP2_TOPIC', 'work.apantle.app'),
    'apns_production' =>        env('APN_HTTP2_PRODUCTION', false),
    'certificates_dir' =>       env('APN_HTTP2_CERTIFICATES_PATH', base_path('certificates')),
    'certificate_file' =>       env('APN_HTTP2_CERTIFICATE', 'apn_cert.pem'),
    'cert_decrypt_password' =>  env('APN_HTTP2_CERT_PASSWORD', 'Aw3$0m3!'),
];