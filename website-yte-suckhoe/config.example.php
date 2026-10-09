<?php
// Sao chép thành config.local.php rồi sửa theo máy bạn. Không đưa config.local.php lên Git.
return [
    "db_host" => "127.0.0.1",
    "db_port" => "3306",
    "db_name" => "healthconnect_moi",
    "db_user" => "root",
    "db_pass" => "",
    // Chọn chuỗi bí mật riêng (ít nhất 16 ký tự), dùng một lần ở setup.php.
    "setup_key" => "",
    // URL Ollama trên MÁY CHẠY PHP, không phải máy khách.
    "ollama_url" => "http://127.0.0.1:11434/api/chat",
    "ollama_model" => "qwen2.5:3b",
    // Dùng để tạo link email đặt lại mật khẩu; sửa nếu thư mục/cổng khác.
    "app_url" => "http://localhost/HealthConnect/website-yte-suckhoe",
    "mail_from" => "no-reply@example.com",
];
