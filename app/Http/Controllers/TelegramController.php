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
            $text = null;
        }

        if (!$chatId) {
            \Log::warning('Telegram update: chatId null', ['update' => $update->toArray()]);
            return;
        }

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
            } elseif ($data === 'reload') {
                $this->sendStudentList($telegram, $user);
            }
        }

        return response()->json(['ok']);
    }

    private function sendChannelJoinRequest($telegram, $chatId)
    {
        $channels = ['@parvozstudy', '@parvozk1ds', '@parvoz_pmt', '@kanstovarparvoz', '@xitoydanzakazparvoz'];

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

    private function getChatMember($telegram, $chatId, $userId)
    {
        try {
            $response = $telegram->getChatMember([
                'chat_id' => $chatId,
                'user_id' => $userId
            ]);

            \Log::info('getChatMember response for chatId ' . $chatId . ' and userId ' . $userId . ': ' . json_encode($response));

            $status = $response->getStatus() ?? 'unknown';
            \Log::info("User status for $chatId: $status");

            return (object) ['status' => $status];
        } catch (\Exception $e) {
            \Log::error("Exception in getChatMember for chatId $chatId and userId $userId: " . $e->getMessage());
            return (object) ['status' => 'error'];
        }
    }

    private function handleChannelVerification($telegram, $user)
    {
        $channels = ['@parvozstudy', '@parvozk1ds', '@parvoz_pmt', '@kanstovarparvoz', '@xitoydanzakazparvoz'];
        $notJoined = [];
        $joinedChannels = [];

        foreach ($channels as $channel) {
            $member = $this->getChatMember($telegram, $channel, $user->telegram_id);
            \Log::info("Check status for $channel: " . $member->status);

            if ($member->status === 'error' || !in_array($member->status, ['member', 'administrator', 'creator'])) {
                $notJoined[] = $channel;
            } else {
                $joinedChannels[] = $channel;
            }
        }

        $user->joined_channels = implode(', ', $joinedChannels);

//        if (count($notJoined) > 0) {
//            $text = "❗ Siz quyidagi kanallarga hali qo‘shilmadingiz:\n\n" . implode("\n", $notJoined);
//            $keyboard = [
//                'inline_keyboard' => [
//                    [
//                        ['text' => '✅ Tasdiqlash', 'callback_data' => 'check_channels']
//                    ]
//                ]
//            ];
//            $telegram->sendMessage([
//                'chat_id' => $user->telegram_id,
//                'text' => $text,
//                'reply_markup' => json_encode($keyboard),
//            ]);
//        } else {
            $user->is_verified = true;
            $telegram->sendMessage([
                'chat_id' => $user->telegram_id,
                'text' => "✅ Hammasi joyida! Endi siz ovoz bera olasiz."
            ]);
            $this->sendStudentList($telegram, $user);
//        }

        $user->save();
    }
//    private function handleChannelVerification($telegram, $user)
//    {
//        $channels = ['@parvozstudy', '@parvozk1ds', '@parvoz_pmt', '@kanstovarparvoz', '@xitoydanzakazparvoz'];
//        $joinedChannels = [];
//
//        foreach ($channels as $channel) {
//            $member = $this->getChatMember($telegram, $channel, $user->telegram_id);
//
//            if (in_array($member->status, ['member', 'administrator', 'creator'])) {
//                $joinedChannels[] = $channel;
//            }
//        }
//
//        // joined_channelsni yozamiz yoki null bo‘lib qoladi
//        $user->joined_channels = count($joinedChannels) ? implode(', ', $joinedChannels) : null;
//        $user->save();
//
//        // ⚠️ Agar joined_channels null bo‘lsa, chiqib ketamiz
//        if (is_null($user->joined_channels)) {
//            $telegram->sendMessage([
//                'chat_id' => $user->telegram_id,
//                'text' => "❗ Siz hali barcha kanallarga qo‘shilmadingiz. Iltimos, ularni to‘liq tasdiqlang."
//            ]);
//            return;
//        }
//
//        // ✅ A'zo bo‘lsa — tasdiqlaymiz va ovoz berishga ruxsat
//        $user->is_verified = true;
//        $user->save();
//
//        $telegram->sendMessage([
//            'chat_id' => $user->telegram_id,
//            'text' => "✅ Hammasi joyida! Endi siz ovoz bera olasiz."
//        ]);
//
//        $this->sendStudentList($telegram, $user);
//    }



    private function sendStudentList($telegram, $user)
    {
        $students = Student::select('students.*')
            ->leftJoin('users', 'students.id', '=', 'users.voted_student_id')
            ->groupBy('students.id', 'students.first_name', 'students.last_name', 'students.created_at', 'students.updated_at')
            ->selectRaw('students.*, COUNT(users.voted_student_id) as vote_count')
            ->orderBy('vote_count', 'desc')
            ->take(20)
            ->get();

        $keyboard = ['inline_keyboard' => []];

        foreach ($students as $student) {
            $voteCount = $student->vote_count;
            $keyboard['inline_keyboard'][] = [
                ['text' => "{$student->first_name} {$student->last_name} - [$voteCount]", 'callback_data' => 'vote_' . $student->id]
            ];
        }

        $keyboard['inline_keyboard'][] = [
            ['text' => '🔄 yangilash', 'callback_data' => 'reload']
        ];

        $telegram->sendMessage([
            'chat_id' => $user->telegram_id,
            'text' => "Ovoz bermoqchi bo‘lgan o‘quvchingizni tanlang:",
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
