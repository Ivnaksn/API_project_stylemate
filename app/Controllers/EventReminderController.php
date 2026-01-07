<?php

namespace App\Controllers;

use App\Models\EventModel;
use CodeIgniter\Controller;

class EventReminderController extends Controller
{
    public function sendH1Reminder($secret = null)
    {
        if ($secret !== env('CRON_SECRET')) {
            return $this->response->setStatusCode(403)->setJSON([
                'status' => false,
                'message' => 'Unauthorized CRON access'
            ]);
        }

        date_default_timezone_set('Asia/Jakarta');

        if (!env('FCM_SERVER_KEY')) {
            log_message('error', 'FCM_SERVER_KEY belum diset');
            return $this->response->setJSON([
                'status' => false,
                'message' => 'FCM key missing'
            ]);
        }

        $eventModel = new EventModel();
        $db = \Config\Database::connect();

        $tomorrow = date('Y-m-d', strtotime('+1 day'));

        $events = $eventModel
            ->where('date', $tomorrow)
            ->where('reminder_sent', 0)
            ->findAll();

        if (empty($events)) {
            return $this->response->setJSON([
                'status' => true,
                'message' => 'Tidak ada event H-1'
            ]);
        }

        foreach ($events as $event) {

            $tokens = $db->table('user_tokens')
                ->where('user_id', $event['user_id'])
                ->get()
                ->getResult();

            if (!$tokens) continue;

            foreach ($tokens as $token) {
                $this->sendFCM(
                    $token->fcm_token,
                    $event['name'],
                    $event['event_id']
                );
            }

            $eventModel->update($event['event_id'], [
                'reminder_sent' => 1
            ]);
        }

        return $this->response->setJSON([
            'status' => true,
            'message' => 'Reminder H-1 berhasil dikirim'
        ]);
    }

    private function sendFCM($token, $eventName, $eventId)
    {
        $payload = [
            'to' => $token,
            'notification' => [
                'title' => 'Pengingat Event',
                'body'  => "Besok ada event: {$eventName}"
            ],
            'data' => [
                'type'     => 'event',
                'event_id' => (string) $eventId
            ]
        ];

        $ch = curl_init('https://fcm.googleapis.com/fcm/send');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: key=' . env('FCM_SERVER_KEY'),
                'Content-Type: application/json'
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_POSTFIELDS     => json_encode($payload)
        ]);

        curl_exec($ch);
        curl_close($ch);
    }
}
