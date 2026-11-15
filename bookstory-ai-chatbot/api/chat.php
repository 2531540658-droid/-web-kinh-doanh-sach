<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/answers.php';
bb_require_post();

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) bb_json(['error' => 'Dữ liệu gửi lên không hợp lệ.'], 400);
if (!is_string($input['message'] ?? null)) bb_json(['error' => 'Câu hỏi phải là văn bản.'], 422);
$message = trim($input['message']);
if ($message === '') bb_json(['error' => 'Bạn hãy nhập câu hỏi trước nhé.'], 422);
if (mb_strlen($message) > 1500) bb_json(['error' => 'Câu hỏi tối đa 1.500 ký tự.'], 422);

try {
    bb_rate_limit();
    bb_save_message('user', $message);
    $reply = bb_chat_reply($message);
    bb_save_message('assistant', $reply['answer']);
    bb_json($reply);
} catch (Throwable $error) {
    error_log('[BookBot chat] ' . $error->getMessage());
    bb_json(['error' => 'Chatbot tạm thời chưa sẵn sàng. Bạn vui lòng thử lại hoặc liên hệ cửa hàng.'], 503);
}
