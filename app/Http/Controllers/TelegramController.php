<?php

namespace App\Http\Controllers;

use Telegram\Bot\Api;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Student;
class TelegramController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $telegram = new Api(config('telegram.bots.default.token'));
        $update = $telegram->getWebhookUpdate();

        $chatId = null;
        $username = null;
        $text = null;

        if ($update->isType('message')) {
            $message = $update->getMessage();
            $chatId = $message->getChat()->getId();
            $username = $message->getChat()->getUsername();
            $text = $message->getText();
        } elseif ($update->isType('callback_query')) {
            $callback = $update->getCallbackQuery();
            $chatId = $callback->getMessage()->getChat()->getId();
            $username = $callback->getFrom()->getUsername();
            $text = null; // callbackda text bo‘lmaydi
        }

        // ❗ Fallback tekshiruv: chatId bo‘lmasa chiqib ketamiz
        if (!$chatId) {
            \Log::warning('Telegram update: chatId null', ['update' => $update]);
            return response()->json(['error' => 'chatId null'], 400);
        }

        // Foydalanuvchini bazaga yozamiz
        $user = User::firstOrCreate(
            ['telegram_id' => $chatId],
            ['username' => $username]
        );

        if ($text === '/start') {
            $this->sendChannelJoinRequest($telegram, $chatId);
        } elseif ($update->isType('callback_query')) {
            $data = $update->getCallbackQuery()->getData();
            if ($data === 'check_channels') {
                $this->handleChannelVerification($telegram, $user);
            } elseif (str_starts_with($data, 'vote_')) {
                $studentId = str_replace('vote_', '', $data);
                $this->handleVoting($telegram, $user, $studentId);
            }
        }

        return response()->json(['ok']);
    }
    private function sendChannelJoinRequest($telegram, $chatId)
    {
        $channels = [
            '@mansanbuuuu',
            '@mansanbuuu',
            '@mansanbuu',
            '@mansanbu',
        ];

        $text = "Quyidagi kanallarga a'zo bo‘ling:\n\n";
        foreach ($channels as $ch) {
            $text .= "➡️ $ch\n";
        }

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '✅ Tasdiqlash', 'callback_data' => 'check_channels']
                ]
            ]
        ];

        $telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => json_encode($keyboard),
        ]);
    }
    private function handleChannelVerification($telegram, $user)
    {
        $channels = ['@mansanbuuuu', '@mansanbuuu', '@mansanbuu', '@mansanbu'];
        $notJoined = [];

        foreach ($channels as $channel) {
            $res = $telegram->getChatMember([
                'chat_id' => $channel,
                'user_id' => $user->telegram_id
            ]);

            if ($res->getStatus() === 'left') {
                $notJoined[] = $channel;
            }
        }

        if (count($notJoined)) {
            $text = "Siz quyidagi kanallarga hali qo‘shilmadingiz:\n" . implode("\n", $notJoined);
        } else {
            $user->is_verified = true;
            $user->save();
            $text = "✅ Hammasi joyida! Endi siz ovoz bera olasiz.";
            $this->sendStudentList($telegram, $user);
        }

        $telegram->sendMessage([
            'chat_id' => $user->telegram_id,
            'text' => $text
        ]);
    }

    private function sendStudentList($telegram, $user)
    {
        $students = Student::take(10)->get();
        $keyboard = ['inline_keyboard' => []];

        foreach ($students as $student) {
            $keyboard['inline_keyboard'][] = [
                ['text' => "{$student->first_name} {$student->last_name}", 'callback_data' => 'vote_' . $student->id]
            ];
        }

        $telegram->sendMessage([
            'chat_id' => $user->telegram_id,
            'text' => "Ovoz bermoqchi bo‘lgan o‘quvchini tanlang:",
            'reply_markup' => json_encode($keyboard),
        ]);
    }
    private function handleVoting($telegram, $user, $studentId)
    {
        if ($user->voted_student_id) {
            $telegram->sendMessage([
                'chat_id' => $user->telegram_id,
                'text' => "Siz allaqachon ovoz bergansiz!"
            ]);
            return;
        }

        $student = Student::find($studentId);
        if (!$student) {
            $telegram->sendMessage([
                'chat_id' => $user->telegram_id,
                'text' => "Bunday o‘quvchi topilmadi!"
            ]);
            return;
        }

        $user->voted_student_id = $student->id;
        $user->save();

        $telegram->sendMessage([
            'chat_id' => $user->telegram_id,
            'text' => "✅ Siz {$student->first_name} {$student->last_name} uchun muvaffaqiyatli ovoz berdingiz!"
        ]);
    }
}

